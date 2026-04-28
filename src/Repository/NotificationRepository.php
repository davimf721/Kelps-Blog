<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class NotificationRepository
{
    public function __construct(private Connection $db) {}

    public function create(array $data): int
    {
        return $this->db->insert('notifications', $data);
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT n.*, u.username AS actor_username
             FROM notifications n
             LEFT JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = $1
             ORDER BY n.created_at DESC
             LIMIT $2',
            [$userId, $limit]
        );
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = $1 AND is_read = FALSE',
            [$userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications SET is_read = TRUE WHERE user_id = $1',
            [$userId]
        );

        // Atualiza contador na tabela users
        $this->db->execute(
            'UPDATE users SET unread_notifications = 0 WHERE id = $1',
            [$userId]
        );
    }

    public function delete(int $id, int $userId): void
    {
        $this->db->execute(
            'DELETE FROM notifications WHERE id = $1 AND user_id = $2',
            [$id, $userId]
        );
    }

    public function incrementUnread(int $userId): void
    {
        $this->db->execute(
            'UPDATE users SET unread_notifications = COALESCE(unread_notifications, 0) + 1 WHERE id = $1',
            [$userId]
        );
    }
}
