<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class CommentRepository
{
    public function __construct(private Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT c.*, u.username FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = $1',
            [$id]
        );
    }

    public function findByPost(int $postId): array
    {
        return $this->db->fetchAll(
            'SELECT c.id, c.content, c.created_at, c.user_id, u.username,
                    COALESCE(u.is_admin, FALSE) AS is_admin
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = $1
             ORDER BY c.created_at ASC',
            [$postId]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('comments', $data);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM comments WHERE id = $1', [$id]);
    }

    public function count(): int
    {
        return (int) $this->db->fetchScalar('SELECT COUNT(*) FROM comments');
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT c.id, c.content, c.created_at, c.post_id, c.user_id,
                    u.username, p.title AS post_title
             FROM comments c
             JOIN users u ON u.id = c.user_id
             JOIN posts p ON p.id = c.post_id
             ORDER BY c.created_at DESC
             LIMIT $1 OFFSET $2',
            [$limit, $offset]
        );
    }
}
