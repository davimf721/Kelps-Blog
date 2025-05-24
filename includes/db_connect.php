<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Usa apenas variáveis de ambiente
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    $dbconn = pg_connect($conn_string);

    if (!$dbconn) {
        throw new Exception("Falha ao conectar com o banco de dados PostgreSQL");
    }

    pg_set_client_encoding($dbconn, "UTF8");

} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>