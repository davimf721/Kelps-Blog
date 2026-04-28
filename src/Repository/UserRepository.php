<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class UserRepository
{
    public function __construct(private Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE id = $1',
            [$id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = $1',
            [strtolower($email)]
        );
    }

    public function findByUsernameOrEmail(string $identifier): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE (username = $1 OR email = $1) AND is_active = TRUE',
            [$identifier]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE username = $1',
            [$username]
        );
    }

    public function findByRememberToken(string $token): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE remember_token = $1 AND token_expires > NOW() AND is_active = TRUE",
            [$token]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update('users', $data, ['id' => $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = $1, password_changed_at = NOW() WHERE id = $2',
            [$hash, $id]
        );
    }

    public function setRememberToken(int $id, string $token, string $expires): void
    {
        $this->db->execute(
            'UPDATE users SET remember_token = $1, token_expires = $2 WHERE id = $3',
            [$token, $expires, $id]
        );
    }

    public function clearRememberToken(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = $1',
            [$id]
        );
    }

    public function setPasswordResetToken(int $id, string $token, string $expires): void
    {
        $this->db->execute(
            'UPDATE users SET reset_token = $1, reset_token_expires = $2 WHERE id = $3',
            [$token, $expires, $id]
        );
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE reset_token = $1 AND reset_token_expires > NOW()",
            [$token]
        );
    }

    public function clearResetToken(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = $1',
            [$id]
        );
    }

    public function recordLogin(int $id, string $ip): void
    {
        $this->db->execute(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = $1, failed_login_attempts = 0, locked_until = NULL WHERE id = $2',
            [$ip, $id]
        );
    }

    public function incrementFailedLogin(int $id): void
    {
        $this->db->execute(
            "UPDATE users SET
                failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE
                    WHEN failed_login_attempts >= 4 THEN NOW() + INTERVAL '15 minutes'
                    ELSE locked_until
                END
             WHERE id = $1",
            [$id]
        );
    }

    public function existsByEmail(string $email): bool
    {
        return (bool) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM users WHERE email = $1',
            [strtolower($email)]
        );
    }

    public function existsByUsername(string $username): bool
    {
        return (bool) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM users WHERE username = $1',
            [$username]
        );
    }

    public function ban(int $id): void
    {
        $this->db->execute('UPDATE users SET is_banned = TRUE WHERE id = $1', [$id]);
    }

    public function unban(int $id): void
    {
        $this->db->execute('UPDATE users SET is_banned = FALSE WHERE id = $1', [$id]);
    }

    public function makeAdmin(int $id): void
    {
        $this->db->execute('UPDATE users SET is_admin = TRUE WHERE id = $1', [$id]);
    }

    public function removeAdmin(int $id): void
    {
        $this->db->execute('UPDATE users SET is_admin = FALSE WHERE id = $1', [$id]);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM users WHERE id = $1', [$id]);
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT id, username, email, is_admin, is_banned, created_at, last_login_at
             FROM users ORDER BY created_at DESC LIMIT $1 OFFSET $2',
            [$limit, $offset]
        );
    }

    public function count(): int
    {
        return (int) $this->db->fetchScalar('SELECT COUNT(*) FROM users');
    }

    public function getStats(int $userId): array
    {
        return $this->db->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM posts WHERE user_id = $1) AS posts_count,
                (SELECT COUNT(*) FROM followers WHERE following_id = $1) AS followers_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = $1) AS following_count',
            [$userId]
        ) ?? ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
    }
}
