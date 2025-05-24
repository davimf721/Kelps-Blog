<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$port = '5432';
$dbname = 'kelps_blog_db';
$user = 'postgres';
$password = 'postgres';

try {
    // Criar string de conexão
    $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    
    // Estabelecer conexão
    $dbconn = pg_connect($conn_string);
    
    // Verificar se a conexão foi bem-sucedida
    if (!$dbconn) {
        throw new Exception("Falha ao conectar com o banco de dados PostgreSQL");
    }
    
    // Configurar codificação UTF8
    pg_set_client_encoding($dbconn, "UTF8");
    
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>