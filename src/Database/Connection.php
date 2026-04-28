<?php

declare(strict_types=1);

namespace App\Database;

use RuntimeException;

/**
 * Conexão singleton com PostgreSQL via pg_connect.
 * Todos os queries DEVEM usar prepared statements via execute().
 */
class Connection
{
    private static ?self $instance = null;

    /** @var resource */
    private $conn;

    private function __construct()
    {
        $cfg = require ROOT_PATH . '/config/database.php';

        $dsn = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s sslmode=%s connect_timeout=%d',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['username'],
            $cfg['password'],
            $cfg['sslmode'],
            $cfg['connect_timeout'] ?? 10,
        );

        $this->conn = @pg_connect($dsn);

        if (! $this->conn) {
            throw new RuntimeException('Falha ao conectar com o banco de dados.');
        }

        pg_set_client_encoding($this->conn, $cfg['charset'] ?? 'UTF8');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // -----------------------------------------------------------------
    // Query helpers — sempre prepared statements
    // -----------------------------------------------------------------

    /** Executa query e retorna resource ou lança exceção. */
    public function execute(string $sql, array $params = []): mixed
    {
        $result = empty($params)
            ? pg_query($this->conn, $sql)
            : pg_query_params($this->conn, $sql, $params);

        if ($result === false) {
            throw new RuntimeException('DB Error: ' . pg_last_error($this->conn));
        }

        return $result;
    }

    /** Retorna todas as linhas como array associativo. */
    public function fetchAll(string $sql, array $params = []): array
    {
        return pg_fetch_all($this->execute($sql, $params)) ?: [];
    }

    /** Retorna a primeira linha ou null. */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = pg_fetch_assoc($this->execute($sql, $params));
        return $row ?: null;
    }

    /** Retorna o valor da primeira coluna da primeira linha. */
    public function fetchScalar(string $sql, array $params = []): mixed
    {
        $result = $this->execute($sql, $params);
        $value = pg_fetch_result($result, 0, 0);
        return $value !== false ? $value : null;
    }

    /**
     * INSERT genérico com RETURNING id.
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(
            fn(int $i) => '$' . ($i + 1),
            array_keys(array_values($data))
        ));

        $sql = "INSERT INTO {$table} ({$cols}) VALUES ({$placeholders}) RETURNING id";
        $row = pg_fetch_assoc($this->execute($sql, array_values($data)));

        return (int) ($row['id'] ?? 0);
    }

    /**
     * UPDATE genérico.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public function update(string $table, array $data, array $where): int
    {
        $i = 1;
        $sets = [];
        $params = [];

        foreach ($data as $col => $val) {
            $sets[] = "{$col} = \${$i}";
            $params[] = $val;
            $i++;
        }

        $conditions = [];
        foreach ($where as $col => $val) {
            $conditions[] = "{$col} = \${$i}";
            $params[] = $val;
            $i++;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $sets)
             . ' WHERE ' . implode(' AND ', $conditions);

        $result = $this->execute($sql, $params);
        return pg_affected_rows($result);
    }

    // -----------------------------------------------------------------
    // Transações
    // -----------------------------------------------------------------

    public function beginTransaction(): void
    {
        pg_query($this->conn, 'BEGIN');
    }

    public function commit(): void
    {
        pg_query($this->conn, 'COMMIT');
    }

    public function rollback(): void
    {
        pg_query($this->conn, 'ROLLBACK');
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // Prevent cloning / unserialization
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}
