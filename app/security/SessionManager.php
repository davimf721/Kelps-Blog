<?php
/**
 * Gerenciador de Sessões Seguro
 * 
 * Implementa práticas de segurança para gerenciamento de sessões.
 */

namespace App\Security;

class SessionManager
{
    private static bool $started = false;
    
    /**
     * Inicia sessão com configurações seguras
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        
        // Configurações de segurança do cookie de sessão
        $isHttps = self::isHttps();
        
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        // Configurações de tempo de vida
        $config = self::getConfig();
        $lifetime = $config['session_lifetime'] ?? 7200;
        
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        
        session_set_cookie_params([
            'lifetime' => 0, // Expira ao fechar o browser
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        
        session_start();
        self::$started = true;
        
        // Verificar e regenerar sessão se necessário
        self::validateSession();
    }
    
    /**
     * Regenera ID da sessão (usar após login/logout)
     */
    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        
        session_regenerate_id(true);
        $_SESSION['session_created'] = time();
        $_SESSION['session_regenerated'] = time();
    }
    
    /**
     * Destrói sessão completamente
     */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Limpar variáveis de sessão
        $_SESSION = [];
        
        // Deletar cookie de sessão
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 3600,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Strict',
                ]
            );
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Valida sessão e regenera se necessário
     */
    private static function validateSession(): void
    {
        // Inicializar timestamps se não existirem
        if (!isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = time();
            $_SESSION['session_regenerated'] = time();
            $_SESSION['last_activity'] = time();
            return;
        }
        
        $now = time();
        $config = self::getConfig();
        $lifetime = $config['session_lifetime'] ?? 7200;
        
        // Verificar inatividade
        if (($now - ($_SESSION['last_activity'] ?? 0)) > $lifetime) {
            self::destroy();
            self::start();
            return;
        }
        
        // Atualizar última atividade
        $_SESSION['last_activity'] = $now;
        
        // Regenerar ID a cada 30 minutos
        if (($now - ($_SESSION['session_regenerated'] ?? 0)) > 1800) {
            self::regenerate();
        }
        
        // Verificar fingerprint (opcional, pode causar problemas em mobile)
        if (isset($_SESSION['user_fingerprint'])) {
            $currentFingerprint = self::generateFingerprint();
            if (!hash_equals($_SESSION['user_fingerprint'], $currentFingerprint)) {
                // Possível session hijacking
                self::destroy();
                self::start();
                return;
            }
        }
    }
    
    /**
     * Define fingerprint do usuário (chamar após login)
     */
    public static function setFingerprint(): void
    {
        $_SESSION['user_fingerprint'] = self::generateFingerprint();
    }
    
    /**
     * Gera fingerprint do navegador
     */
    private static function generateFingerprint(): string
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        ];
        
        return hash('sha256', implode('|', $data));
    }
    
    /**
     * Define dados do usuário na sessão
     */
    public static function setUser(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = ($user['is_admin'] ?? false) === true || $user['is_admin'] === 't';
        $_SESSION['logged_in_at'] = time();
        
        self::setFingerprint();
    }
    
    /**
     * Obtém dados do usuário da sessão
     */
    public static function getUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'is_admin' => $_SESSION['is_admin'] ?? false,
        ];
    }
    
    /**
     * Verifica se usuário está logado
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }
    
    /**
     * Verifica se usuário é admin
     */
    public static function isAdmin(): bool
    {
        return !empty($_SESSION['is_admin']);
    }
    
    /**
     * Obtém ID do usuário
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Define mensagem flash
     */
    public static function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
    
    /**
     * Obtém e remove mensagem flash
     */
    public static function getFlash(string $key): ?string
    {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }
        
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        
        return $message;
    }
    
    /**
     * Verifica se é HTTPS
     */
    private static function isHttps(): bool
    {
        // Railway e outros hosts definem isso
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        // Behind proxy/load balancer
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtém configurações de segurança
     */
    private static function getConfig(): array
    {
        static $config = null;
        
        if ($config === null) {
            $configFile = __DIR__ . '/../../config/app.php';
            if (file_exists($configFile)) {
                $appConfig = require $configFile;
                $config = $appConfig['security'] ?? [];
            } else {
                $config = [];
            }
        }
        
        return $config;
    }
}
