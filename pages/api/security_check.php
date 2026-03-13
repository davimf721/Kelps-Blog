<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Permitir apenas requisições AJAX e de admin
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    die(json_encode(['error' => 'Requisição inválida']));
}

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    die(json_encode(['error' => 'Não autorizado']));
}

$issues = [];
$timestamp = time();

try {
    // ========== VERIFICAÇÃO 1: SQL INJECTION ==========
    // Verifica se há queries que usam ID sem prepared statements
    $sql_injection_risks = [];
    
    // Verifica por padrões perigosos nos uploads/logs
    $log_dir = '../../storage/logs/';
    if (is_dir($log_dir)) {
        $log_files = glob($log_dir . '*.log');
        foreach ($log_files as $log_file) {
            $content = file_get_contents($log_file);
            // Procura por padrões suspeitos de SQL injection
            if (preg_match('/(\$_GET|\$_POST|\$_REQUEST).*exec|pg_query|mysqli_query/i', $content)) {
                $sql_injection_risks[] = basename($log_file);
            }
        }
    }
    
    if (!empty($sql_injection_risks)) {
        $issues[] = [
            'type' => 'warning',
            'title' => 'Potencial risco de SQL Injection',
            'description' => 'Detectado uso suspeito de variáveis GET/POST em arquivos de log: ' . implode(', ', $sql_injection_risks),
            'severity' => 'high',
            'timestamp' => date('H:i:s')
        ];
    }
    
    // ========== VERIFICAÇÃO 2: SENHAS EM PLAIN TEXT ==========
    // Verifica se há usuários com senhas que não parecem ser hash
    $password_query = "SELECT COUNT(*) FROM users WHERE password_hash IS NULL OR password_hash = '' OR LENGTH(password_hash) < 20";
    $password_result = pg_query($dbconn, $password_query);
    if ($password_result) {
        $weak_passwords = pg_fetch_result($password_result, 0, 0);
        if ($weak_passwords > 0) {
            $issues[] = [
                'type' => 'warning',
                'title' => 'Senhas fraco ou sem hash',
                'description' => "$weak_passwords usuários com senhas potencialmente fracas ou não hasheadas",
                'severity' => 'critical',
                'timestamp' => date('H:i:s')
            ];
        }
        pg_free_result($password_result);
    }
    
    // ========== VERIFICAÇÃO 3: DADOS SENSÍVEIS EXPOSTOS ==========
    // Verifica permissões de arquivos sensíveis
    $sensitive_files = [
        '../../config/database.php',
        '../../includes/db_connect.php',
        '../../app/config/database.php'
    ];
    
    $exposed_files = [];
    foreach ($sensitive_files as $file) {
        $real_path = realpath($file);
        if (file_exists($file)) {
            // Verifica se o arquivo tem permissões de leitura amplas
            $perms = fileperms($file);
            $perm_string = substr(sprintf('%o', $perms), -4);
            
            // 644 é perigoso, 640 é melhor, 600 é ideal
            if ($perm_string[2] !== '0' && $perm_string[3] !== '0') {
                $exposed_files[] = basename($file) . " (perms: $perm_string)";
            }
        }
    }
    
    if (!empty($exposed_files)) {
        $issues[] = [
            'type' => 'warning',
            'title' => 'Arquivos sensíveis com permissões inadequadas',
            'description' => 'Encontrados arquivos de configuração com permissões potencialmente expostas: ' . implode(', ', $exposed_files),
            'severity' => 'high',
            'timestamp' => date('H:i:s')
        ];
    }
    
    // ========== VERIFICAÇÃO 4: UPLOADS DESPROTEGIDOS ==========
    // Verifica se a pasta de uploads existe e tem arquivos executáveis
    $upload_dir = '../../storage/uploads/';
    if (is_dir($upload_dir)) {
        $files = glob($upload_dir . '*');
        $executable_files = [];
        $dangerous_extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat'];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $dangerous_extensions)) {
                    $executable_files[] = basename($file);
                }
            }
        }
        
        if (!empty($executable_files)) {
            $issues[] = [
                'type' => 'warning',
                'title' => 'Arquivos executáveis na pasta de uploads',
                'description' => 'Encontrados ' . count($executable_files) . ' arquivos potencialmente perigosos',
                'severity' => 'critical',
                'timestamp' => date('H:i:s')
            ];
        }
    }
    
    // ========== VERIFICAÇÃO 5: HEADERS DE SEGURANÇA ==========
    $security_headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block'
    ];
    
    $missing_headers = [];
    foreach ($security_headers as $header => $expected) {
        if (!isset($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))])) {
            $missing_headers[] = $header;
        }
    }
    
    if (!empty($missing_headers)) {
        $issues[] = [
            'type' => 'info',
            'title' => 'Headers de segurança ausentes',
            'description' => 'Headers recomendados que não estão configurados: ' . implode(', ', $missing_headers),
            'severity' => 'medium',
            'timestamp' => date('H:i:s')
        ];
    }
    
    // ========== VERIFICAÇÃO 6: FUNÇÃO DE SANITIZAÇÃO ==========
    // Verifica se formulários estão usando sanitização
    $pages_dir = '../../pages/';
    $unsanitized_inputs = [0];
    
    $page_files = glob($pages_dir . '**/*.php', GLOB_RECURSIVE);
    foreach (array_slice($page_files, 0, 5) as $page_file) {
        $content = file_get_contents($page_file);
        // Procura por uso direto de $_GET, $_POST sem sanitização evidente
        if (preg_match('/\$_(?:GET|POST|REQUEST)\[.*?\](?!.*?(?:htmlspecialchars|sanitize|escape))/s', $content)) {
            $unsanitized_inputs[0]++;
        }
    }
    
    if ($unsanitized_inputs[0] > 0) {
        $issues[] = [
            'type' => 'info',
            'title' => 'Possível falta de sanitização em inputs',
            'description' => 'Encontrados potenciais inputs não sanitizados em algumas páginas (verificando amostra)',
            'severity' => 'medium',
            'timestamp' => date('H:i:s')
        ];
    }
    
    // ========== VERIFICAÇÃO 7: BANCO DE DADOS ==========
    // Verifica integridade das tabelas
    $tables_query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $tables_result = pg_query($dbconn, $tables_query);
    
    if ($tables_result) {
        while ($table = pg_fetch_assoc($tables_result)) {
            $table_name = $table['table_name'];
            $integrity_query = "SELECT COUNT(*) FROM $table_name";
            $integrity_result = pg_query($dbconn, $integrity_query);
            
            if ($integrity_result === false) {
                $issues[] = [
                    'type' => 'warning',
                    'title' => 'Possível corrupção de tabela',
                    'description' => "Erro ao verificar tabela: $table_name",
                    'severity' => 'high',
                    'timestamp' => date('H:i:s')
                ];
                break;
            }
            pg_free_result($integrity_result);
        }
        pg_free_result($tables_result);
    }
    
    // Se não teve problemas, retorna mensagem positiva
    if (empty($issues)) {
        $issues[] = [
            'type' => 'success',
            'title' => '✅ Sistema seguro',
            'description' => 'Nenhum problema de segurança detectado! Seu site está bem protegido.',
            'severity' => 'info',
            'timestamp' => date('H:i:s')
        ];
    }
    
} catch (Exception $e) {
    $issues[] = [
        'type' => 'warning',
        'title' => 'Erro na verificação',
        'description' => 'Ocorreu um erro ao verificar segurança: ' . $e->getMessage(),
        'severity' => 'medium',
        'timestamp' => date('H:i:s')
    ];
}

echo json_encode([
    'success' => true,
    'issues' => $issues,
    'timestamp' => $timestamp,
    'last_check' => date('d/m/Y H:i:s')
]);
?>
