<?php

declare(strict_types=1);

namespace App\Security;

use App\Database\Connection;

/**
 * Rate limiting por IP + chave, armazenado em tabela PostgreSQL.
 */
class RateLimiter
{
    public function __construct(private Connection $db) {}

    /**
     * Verifica se o limite foi atingido.
     * @param string $key       Identificador da ação (ex: 'login', 'register')
     * @param int    $max       Número máximo de tentativas
     * @param int    $minutes   Janela de tempo em minutos
     */
    public function tooMany(string $key, int $max, int $minutes): bool
    {
        return $this->count($key, $minutes) >= $max;
    }

    /**
     * Registra uma tentativa e retorna o total na janela.
     */
    public function hit(string $key, int $minutes): int
    {
        $fullKey = $key . ':' . $this->ip();
        $decay   = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        // Limpa tentativas antigas
        $this->db->execute(
            'DELETE FROM rate_limits WHERE key = $1 AND created_at < $2',
            [$fullKey, $decay]
        );

        // Registra tentativa atual
        $this->db->execute(
            'INSERT INTO rate_limits (key, ip, created_at) VALUES ($1, $2, NOW())',
            [$fullKey, $this->ip()]
        );

        return $this->count($key, $minutes);
    }

    /** Limpa todas as tentativas de uma chave para o IP atual. */
    public function clear(string $key): void
    {
        $this->db->execute(
            'DELETE FROM rate_limits WHERE key = $1',
            [$key . ':' . $this->ip()]
        );
    }

    /** Retorna quanto tempo (segundos) falta para a janela expirar. */
    public function availableIn(string $key, int $minutes): int
    {
        $fullKey = $key . ':' . $this->ip();

        $oldest = $this->db->fetchScalar(
            'SELECT MIN(created_at) FROM rate_limits WHERE key = $1',
            [$fullKey]
        );

        if (! $oldest) {
            return 0;
        }

        $expireAt = strtotime($oldest) + ($minutes * 60);
        return max(0, $expireAt - time());
    }

    private function count(string $key, int $minutes): int
    {
        $fullKey = $key . ':' . $this->ip();
        $since   = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return (int) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM rate_limits WHERE key = $1 AND created_at >= $2',
            [$fullKey, $since]
        );
    }

    private function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            if (! empty($_SERVER[$h])) {
                return trim(explode(',', $_SERVER[$h])[0]);
            }
        }

        return '127.0.0.1';
    }
}
