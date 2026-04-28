<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class PostRepository
{
    public function __construct(private Connection $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT p.*, u.username AS author, u.is_admin AS author_is_admin
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = $1',
            [$id]
        );
    }

    /**
     * Lista paginada de posts com contagem de comentários e status de upvote.
     */
    public function paginate(int $limit = 10, int $offset = 0, ?int $viewerId = null): array
    {
        $upvoteExpr = $viewerId
            ? "(SELECT COUNT(*) > 0 FROM post_upvotes WHERE post_id = p.id AND user_id = {$viewerId})"
            : 'FALSE';

        return $this->db->fetchAll(
            "SELECT p.id, p.title, p.content, p.created_at, p.updated_at, p.user_id,
                    p.upvotes_count,
                    u.username AS author,
                    COALESCE(u.is_admin, FALSE) AS author_is_admin,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                    {$upvoteExpr} AS user_has_upvoted
             FROM posts p
             JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT \$1 OFFSET \$2",
            [$limit, $offset]
        );
    }

    /** Posts de um usuário específico. */
    public function findByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
             FROM posts p
             WHERE p.user_id = $1
             ORDER BY p.created_at DESC
             LIMIT $2 OFFSET $3',
            [$userId, $limit, $offset]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('posts', $data);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('posts', $data, ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM posts WHERE id = $1', [$id]);
    }

    public function count(): int
    {
        return (int) $this->db->fetchScalar('SELECT COUNT(*) FROM posts');
    }

    public function countByUser(int $userId): int
    {
        return (int) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM posts WHERE user_id = $1',
            [$userId]
        );
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT p.id, p.title, p.created_at, p.user_id, u.username AS author,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count
             FROM posts p
             JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT $1 OFFSET $2',
            [$limit, $offset]
        );
    }
}
