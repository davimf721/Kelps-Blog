<?php
/**
 * Rate Limiter
 * 
 * Protege contra ataques de força bruta e abuso da API.
 */

namespace App\Security;

use App\Database\Connection;

class RateLimiter
{
    private Connection $db;
    private int $maxAttempts;
    private int $decayMinutes;
    
    /**
     * @param int $maxAttempts Número máximo de tentativas
     * @param int $decayMinutes Período de decaimento em minutos
     */
    public function __construct(?Connection $db = null, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->db = $db ?? Connection::getInstance();
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }
    
    /**
     * Cria instância com configuração pré-definida
     */
    public static function for(string $type): self
    {
        $config = require __DIR__ . '/../../config/app.php';
        $limits = $config['security']['rate_limits'][$type] ?? [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ];
        
        return new self(
            null,
            $limits['max_attempts'],
            $limits['decay_minutes']
        );
    }
    
    /**
     * Verifica se excedeu o limite
     */
    public function tooManyAttempts(string $key, ?string $identifier = null): bool
    {
        $identifier = $identifier ?? $this->getClientIdentifier();
        return $this->attempts($key, $identifier) >= $this->maxAttempts;
    }
    
    /**
     * Registra tentativa
     */
    public function hit(string $key, ?string $identifier = null): int
    {
        $identifier = $identifier ?? $this->getClientIdentifier();
        $fullKey = $this->resolveKey($key, $identifier);
        
        // Limpar tentativas antigas
        $this->cleanup($fullKey);
        
        // Registrar nova tentativa
        try {
            $this->db->execute(
                "INSERT INTO rate_limits (key, ip, created_at) VALUES ($1, $2, NOW())",
                [$fullKey, $this->getClientIp()]
            );
        } catch (\Exception $e) {
            // Tabela pode não existir ainda, usar fallback em sessão
            return $this->hitSession($fullKey);
        }
        
        return $this->attempts($key, $identifier);
    }
    
    /**
     * Limpa tentativas para uma chave
     */
    public function clear(string $key, ?string $identifier = null): void
    {
        $identifier = $identifier ?? $this->getClientIdentifier();
        $fullKey = $this->resolveKey($key, $identifier);
        
        try {
            $this->db->execute(
                "DELETE FROM rate_limits WHERE key = $1",
                [$fullKey]
            );
        } catch (\Exception $e) {
            // Fallback em sessão
            unset($_SESSION['rate_limits'][$fullKey]);
        }
    }
    
    /**
     * Obtém número de tentativas
     */
    public function attempts(string $key, ?string $identifier = null): int
    {
        $identifier = $identifier ?? $this->getClientIdentifier();
        $fullKey = $this->resolveKey($key, $identifier);
        $decayTime = time() - ($this->decayMinutes * 60);
        
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM rate_limits 
                 WHERE key = $1 AND created_at > to_timestamp($2)",
                [$fullKey, $decayTime]
            );
            
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            // Fallback em sessão
            return $this->attemptsSession($fullKey);
        }
    }
    
    /**
     * Retorna segundos restantes até poder tentar novamente
     */
    public function availableIn(string $key, ?string $identifier = null): int
    {
        $identifier = $identifier ?? $this->getClientIdentifier();
        $fullKey = $this->resolveKey($key, $identifier);
        
        try {
            $result = $this->db->fetchOne(
                "SELECT EXTRACT(EPOCH FROM MIN(created_at)) as first_attempt 
                 FROM rate_limits WHERE key = $1",
                [$fullKey]
            );
            
            if ($result && $result['first_attempt']) {
                $unlockTime = (int) $result['first_attempt'] + ($this->decayMinutes * 60);
                return max(0, $unlockTime - time());
            }
        } catch (\Exception $e) {
            // Ignorar
        }
        
        return 0;
    }
    
    /**
     * Limpa registros antigos
     */
    private function cleanup(string $key): void
    {
        $decayTime = time() - ($this->decayMinutes * 60);
        
        try {
            $this->db->execute(
                "DELETE FROM rate_limits WHERE key = $1 AND created_at < to_timestamp($2)",
                [$key, $decayTime]
            );
        } catch (\Exception $e) {
            // Ignorar
        }
    }
    
    /**
     * Resolve chave completa
     */
    private function resolveKey(string $key, string $identifier): string
    {
        return $key . ':' . $identifier;
    }
    
    /**
     * Obtém identificador do cliente (IP + User Agent hash)
     */
    private function getClientIdentifier(): string
    {
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return hash('sha256', $ip . '|' . $userAgent);
    }
    
    /**
     * Obtém IP real do cliente (considera proxies)
     */
    private function getClientIp(): string
    {
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        
        // Proxy/Load balancer
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    // ======== Fallback em sessão (se tabela não existir) ========
    
    private function hitSession(string $key): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        // Limpar antigas
        $decayTime = time() - ($this->decayMinutes * 60);
        $_SESSION['rate_limits'][$key] = array_filter(
            $_SESSION['rate_limits'][$key],
            fn($timestamp) => $timestamp > $decayTime
        );
        
        // Adicionar nova
        $_SESSION['rate_limits'][$key][] = time();
        
        return count($_SESSION['rate_limits'][$key]);
    }
    
    private function attemptsSession(string $key): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['rate_limits'][$key])) {
            return 0;
        }
        
        $decayTime = time() - ($this->decayMinutes * 60);
        
        return count(array_filter(
            $_SESSION['rate_limits'][$key],
            fn($timestamp) => $timestamp > $decayTime
        ));
    }
}
