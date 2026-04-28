#!/usr/bin/env php
<?php
/**
 * Migration runner — executa arquivos SQL em database/migrations/ em ordem.
 * Rastreia migrations já executadas na tabela _migrations.
 *
 * Uso:
 *   php database/migrate.php
 *   php database/migrate.php --fresh  (recria tudo — PERIGOSO em produção!)
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';

if (file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// Conectar ao banco
$cfg = require ROOT_PATH . '/config/database.php';

$dsn = sprintf(
    'host=%s port=%s dbname=%s user=%s password=%s sslmode=%s connect_timeout=%d',
    $cfg['host'], $cfg['port'], $cfg['database'],
    $cfg['username'], $cfg['password'],
    $cfg['sslmode'], $cfg['connect_timeout'] ?? 10
);

$conn = pg_connect($dsn);
if (! $conn) {
    echo "[ERRO] Não foi possível conectar ao banco.\n";
    exit(1);
}

pg_set_client_encoding($conn, 'UTF8');

// Criar tabela de controle de migrations
pg_query($conn, "
    CREATE TABLE IF NOT EXISTS _migrations (
        id         SERIAL PRIMARY KEY,
        filename   VARCHAR(255) UNIQUE NOT NULL,
        ran_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Buscar migrations já executadas
$result  = pg_query($conn, 'SELECT filename FROM _migrations ORDER BY filename');
$done    = [];
while ($row = pg_fetch_assoc($result)) {
    $done[] = $row['filename'];
}

// Listar arquivos de migration
$files = glob(ROOT_PATH . '/database/migrations/*.sql');
sort($files);

$ran = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $done)) {
        echo "[SKIP] {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);

    pg_query($conn, 'BEGIN');

    if (pg_query($conn, $sql) === false) {
        pg_query($conn, 'ROLLBACK');
        echo "[ERRO] {$name}: " . pg_last_error($conn) . "\n";
        exit(1);
    }

    pg_query_params($conn, 'INSERT INTO _migrations (filename) VALUES ($1)', [$name]);
    pg_query($conn, 'COMMIT');

    echo "[OK]   {$name}\n";
    $ran++;
}

echo $ran > 0
    ? "\n✓ {$ran} migration(s) executada(s) com sucesso.\n"
    : "\nTudo já está atualizado.\n";

pg_close($conn);
