<?php
/**
 * Proteção CSRF (Cross-Site Request Forgery)
 * 
 * Gera e valida tokens CSRF para proteger formulários e requisições.
 */

namespace App\Security;

class Csrf
{
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 hora
    
    /**
     * Gera ou retorna token CSRF existente
     */
    public static function generateToken(): string
    {
        self::ensureSession();
        
        // Verificar se token existe e ainda é válido
        if (
            !empty($_SESSION[self::TOKEN_NAME]) &&
            !empty($_SESSION['csrf_token_time']) &&
            (time() - $_SESSION['csrf_token_time']) < self::TOKEN_LIFETIME
        ) {
            return $_SESSION[self::TOKEN_NAME];
        }
        
        // Gerar novo token
        return self::regenerateToken();
    }
    
    /**
     * Regenera token (usar após ações críticas)
     */
    public static function regenerateToken(): string
    {
        self::ensureSession();
        
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Valida token CSRF
     * 
     * @param string|null $token Token para validar
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        self::ensureSession();
        
        if (empty($token) || empty($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        
        // Verificar expiração
        if (
            empty($_SESSION['csrf_token_time']) ||
            (time() - $_SESSION['csrf_token_time']) > self::TOKEN_LIFETIME
        ) {
            self::regenerateToken();
            return false;
        }
        
        // Comparação segura contra timing attacks
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    /**
     * Valida token e lança exceção se inválido
     * 
     * @throws \Exception
     */
    public static function verify(?string $token): void
    {
        if (!self::validateToken($token)) {
            throw new \Exception('Token CSRF inválido ou expirado');
        }
    }
    
    /**
     * Retorna input HTML com token CSRF
     */
    public static function getInputField(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Retorna meta tag com token (para requisições AJAX)
     */
    public static function getMetaTag(): string
    {
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Obtém token do request (POST, header ou JSON)
     */
    public static function getTokenFromRequest(): ?string
    {
        // 1. Tentar POST
        if (!empty($_POST[self::TOKEN_NAME])) {
            return $_POST[self::TOKEN_NAME];
        }
        
        // 2. Tentar header X-CSRF-TOKEN
        $headers = getallheaders();
        if (!empty($headers['X-CSRF-TOKEN'])) {
            return $headers['X-CSRF-TOKEN'];
        }
        if (!empty($headers['X-Csrf-Token'])) {
            return $headers['X-Csrf-Token'];
        }
        
        // 3. Tentar JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (!empty($json[self::TOKEN_NAME])) {
                return $json[self::TOKEN_NAME];
            }
        }
        
        return null;
    }
    
    /**
     * Middleware: valida CSRF automaticamente em POST/PUT/DELETE
     */
    public static function middleware(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Ignorar métodos seguros
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }
        
        $token = self::getTokenFromRequest();
        
        if (!self::validateToken($token)) {
            http_response_code(403);
            
            if (self::isJsonRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token CSRF inválido ou expirado'
                ]);
            } else {
                // Redirecionar com mensagem de erro
                $_SESSION['flash_error'] = 'Sessão expirada. Por favor, tente novamente.';
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica se é requisição JSON
     */
    private static function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        return strpos($contentType, 'application/json') !== false ||
               strpos($accept, 'application/json') !== false;
    }
    
    /**
     * Garante que sessão está iniciada
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Retorna nome do campo de token
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }
}
