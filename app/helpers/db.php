<?php
/**
 * Conexão com o banco de dados PostgreSQL
 * 
 * Este arquivo estabelece a conexão com o banco de dados.
 * Em produção, os erros são logados e não exibidos.
 */

// Não reconfigurar ambiente se já foi feito pelo bootstrap
if (!defined('APP_PATH')) {
    // Detectar ambiente
    $isProduction = getenv('APP_ENV') === 'production' || 
                    !empty(getenv('RAILWAY_ENVIRONMENT')) ||
                    !empty(getenv('RAILWAY_PUBLIC_DOMAIN'));

    // Configurar tratamento de erros
    if ($isProduction) {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        
        // Criar diretório de logs se não existir
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        ini_set('error_log', $logDir . '/php_errors.log');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }
}

// Carregar configurações do banco
$configPath = defined('CONFIG_PATH') ? CONFIG_PATH : __DIR__ . '/../config';
$dbConfig = require $configPath . '/database.php';

// Estabelecer conexão
try {
    $conn_string = sprintf(
        "host=%s port=%s dbname=%s user=%s password=%s sslmode=%s connect_timeout=%d",
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database'],
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['sslmode'],
        $dbConfig['connect_timeout'] ?? 10
    );
    
    $dbconn = @pg_connect($conn_string);

    if (!$dbconn) {
        throw new Exception("Falha ao conectar com o banco de dados PostgreSQL");
    }

    pg_set_client_encoding($dbconn, $dbConfig['charset'] ?? 'UTF8');

} catch (Exception $e) {
    // Em produção, não exibir detalhes do erro
    if ($isProduction) {
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(503);
        die("Serviço temporariamente indisponível. Tente novamente em alguns minutos.");
    } else {
        die("Erro de conexão: " . $e->getMessage());
    }
}