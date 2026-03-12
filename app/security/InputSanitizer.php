<?php
/**
 * Sanitização de Inputs
 * 
 * Fornece métodos seguros para limpar e validar entradas do usuário.
 */

namespace App\Security;

class InputSanitizer
{
    /**
     * Sanitiza string removendo HTML e trimando
     */
    public static function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitiza e valida email
     * 
     * @return string|null Email válido ou null
     */
    public static function sanitizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }
    
    /**
     * Sanitiza para inteiro
     */
    public static function sanitizeInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitiza para inteiro positivo
     */
    public static function sanitizePositiveInt($input): int
    {
        $value = self::sanitizeInt($input);
        return max(0, $value);
    }
    
    /**
     * Sanitiza ID (inteiro positivo ou null)
     */
    public static function sanitizeId($input): ?int
    {
        $value = self::sanitizeInt($input);
        return $value > 0 ? $value : null;
    }
    
    /**
     * Sanitiza username
     * 
     * @return string|null Username válido ou null
     */
    public static function sanitizeUsername(?string $username): ?string
    {
        if ($username === null) {
            return null;
        }
        
        $username = trim($username);
        
        // Permite apenas letras, números e underscore
        // Mínimo 3, máximo 30 caracteres
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return null;
        }
        
        return $username;
    }
    
    /**
     * Sanitiza conteúdo Markdown
     * Remove scripts e atributos perigosos mas mantém markdown válido
     */
    public static function sanitizeMarkdown(?string $content): string
    {
        if ($content === null) {
            return '';
        }
        
        $content = trim($content);
        
        // Remove tags script
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        
        // Remove handlers de eventos (onclick, onerror, etc)
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s*on\w+\s*=\s*[^\s>]+/i', '', $content);
        
        // Remove javascript: em hrefs
        $content = preg_replace('/javascript\s*:/i', '', $content);
        
        // Remove data: URIs em imagens (pode conter scripts)
        $content = preg_replace('/src\s*=\s*["\']?\s*data\s*:/i', 'src="', $content);
        
        // Remove tags perigosas
        $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button'];
        foreach ($dangerousTags as $tag) {
            $content = preg_replace('/<\/?'.$tag.'[^>]*>/i', '', $content);
        }
        
        // Remove style inline com expression (IE)
        $content = preg_replace('/style\s*=\s*["\'][^"\']*expression[^"\']*["\']/i', '', $content);
        
        return $content;
    }
    
    /**
     * Sanitiza para exibição segura em HTML
     */
    public static function escapeHtml(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitiza para uso em atributo HTML
     */
    public static function escapeAttribute(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    }
    
    /**
     * Sanitiza URL
     * 
     * @return string|null URL válida ou null
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Só permitir http e https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array(strtolower($scheme), ['http', 'https'])) {
            return null;
        }
        
        return $url;
    }
    
    /**
     * Sanitiza array de IDs
     */
    public static function sanitizeIdArray(array $ids): array
    {
        return array_filter(
            array_map([self::class, 'sanitizeId'], $ids),
            fn($id) => $id !== null
        );
    }
    
    /**
     * Valida e sanitiza senha (não altera, apenas valida)
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        $config = require __DIR__ . '/../../config/app.php';
        $rules = $config['security']['password'] ?? [];
        
        $minLength = $rules['min_length'] ?? 8;
        
        if (strlen($password) < $minLength) {
            $errors[] = "A senha deve ter pelo menos {$minLength} caracteres";
        }
        
        if (($rules['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (($rules['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
        }
        
        if (($rules['require_number'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número';
        }
        
        if (($rules['require_special'] ?? false) && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial';
        }
        
        return $errors;
    }
    
    /**
     * Remove múltiplos espaços e quebras de linha
     */
    public static function normalizeWhitespace(string $input): string
    {
        // Substitui múltiplos espaços por um
        $input = preg_replace('/[ \t]+/', ' ', $input);
        
        // Substitui múltiplas quebras de linha por duas (máximo)
        $input = preg_replace('/\n{3,}/', "\n\n", $input);
        
        return trim($input);
    }
    
    /**
     * Trunca string de forma segura (não corta palavras)
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length);
        
        // Tentar não cortar no meio de uma palavra
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $length * 0.8) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . $suffix;
    }
    
    /**
     * Sanitiza filename para upload
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove caracteres perigosos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove pontos múltiplos
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Remove pontos no início
        $filename = ltrim($filename, '.');
        
        // Limita tamanho
        if (strlen($filename) > 200) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 190) . '.' . $ext;
        }
        
        return $filename ?: 'unnamed';
    }
}
