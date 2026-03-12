<?php
/**
 * Bootstrap - Inicialização da aplicação Kelps Blog
 * 
 * Este arquivo deve ser incluído em todas as páginas.
 * Ele configura paths, carrega helpers e inicializa a sessão.
 */

// Prevenir acesso direto
if (basename($_SERVER['PHP_SELF']) === 'bootstrap.php') {
    http_response_code(403);
    exit('Acesso negado');
}

// ============================================
// CONSTANTES DE PATHS
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('CONFIG_PATH', APP_PATH . '/config');
define('HELPERS_PATH', APP_PATH . '/helpers');
define('VIEWS_PATH', APP_PATH . '/views');
define('SECURITY_PATH', APP_PATH . '/security');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('DATABASE_PATH', ROOT_PATH . '/database');

// ============================================
// CONFIGURAÇÃO DE AMBIENTE
// ============================================
$env = getenv('APP_ENV') ?: 'production';
define('APP_ENV', $env);
define('APP_DEBUG', $env === 'development');

// Configuração de erros baseada no ambiente
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php_errors.log');
}

// ============================================
// AUTOLOADER SIMPLES
// ============================================
spl_autoload_register(function ($class) {
    // Classes de segurança
    $securityFile = SECURITY_PATH . '/' . $class . '.php';
    if (file_exists($securityFile)) {
        require_once $securityFile;
        return;
    }
    
    // Classes em app/
    $appFile = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($appFile)) {
        require_once $appFile;
        return;
    }
});

// ============================================
// CARREGAR DEPENDÊNCIAS
// ============================================

// Composer autoload (se existir)
$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Carregar variáveis de ambiente (.env)
if (class_exists('Dotenv\Dotenv') && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// ============================================
// CARREGAR HELPERS ESSENCIAIS
// ============================================
require_once HELPERS_PATH . '/db.php';
require_once HELPERS_PATH . '/auth.php';

// Helpers opcionais (carregar se existirem)
$optionalHelpers = ['notifications.php', 'email_config.php'];
foreach ($optionalHelpers as $helper) {
    $helperPath = HELPERS_PATH . '/' . $helper;
    if (file_exists($helperPath)) {
        require_once $helperPath;
    }
}

// ============================================
// INICIALIZAÇÃO DE SESSÃO SEGURA
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
    
    // Regenerar ID periodicamente para prevenir fixação de sessão
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

// ============================================
// FUNÇÕES HELPERS GLOBAIS
// ============================================

/**
 * Escape HTML para prevenir XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Retornar caminho de asset público
 */
function asset(string $path): string {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $basePath . '/public/' . ltrim($path, '/');
}

/**
 * Redirecionar para URL
 */
function redirect(string $url, int $statusCode = 302): void {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Retornar JSON response
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Flash messages
 */
function flash(string $key, ?string $message = null) {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

/**
 * Verificar se usuário está autenticado
 */
function auth(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Obter ID do usuário logado
 */
function user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obter username do usuário logado
 */
function username(): ?string {
    return $_SESSION['username'] ?? null;
}

/**
 * Verificar se usuário é admin
 */
function is_admin_user(): bool {
    return $_SESSION['is_admin'] ?? false;
}

/**
 * Requerer autenticação
 */
function require_auth(): void {
    if (!auth()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        flash('error', 'Você precisa estar logado para acessar esta página.');
        redirect('login.php');
    }
}

/**
 * Requerer que seja admin
 */
function require_admin(): void {
    require_auth();
    if (!is_admin_user()) {
        flash('error', 'Você não tem permissão para acessar esta área.');
        redirect('index.php');
    }
}

/**
 * Incluir view parcial
 */
function partial(string $name, array $data = []): void {
    extract($data);
    $partialPath = VIEWS_PATH . '/partials/' . $name . '.php';
    if (file_exists($partialPath)) {
        include $partialPath;
    }
}

/**
 * Obter configuração
 */
function config(string $key, $default = null) {
    static $configs = [];
    
    $parts = explode('.', $key);
    $file = $parts[0];
    
    if (!isset($configs[$file])) {
        $configPath = CONFIG_PATH . '/' . $file . '.php';
        if (file_exists($configPath)) {
            $configs[$file] = require $configPath;
        } else {
            return $default;
        }
    }
    
    $value = $configs[$file];
    for ($i = 1; $i < count($parts); $i++) {
        if (!isset($value[$parts[$i]])) {
            return $default;
        }
        $value = $value[$parts[$i]];
    }
    
    return $value;
}

/**
 * Gerar token CSRF
 */
function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Campo hidden com CSRF token
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validar CSRF token
 */
function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
