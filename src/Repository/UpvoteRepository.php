<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class UpvoteRepository
{
    public function __construct(private Connection $db) {}

    public function hasUpvoted(int $postId, int $userId): bool
    {
        return (bool) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM post_upvotes WHERE post_id = $1 AND user_id = $2',
            [$postId, $userId]
        );
    }

    public function add(int $postId, int $userId): void
    {
        $this->db->execute(
            'INSERT INTO post_upvotes (post_id, user_id) VALUES ($1, $2) ON CONFLICT DO NOTHING',
            [$postId, $userId]
        );
    }

    public function remove(int $postId, int $userId): void
    {
        $this->db->execute(
            'DELETE FROM post_upvotes WHERE post_id = $1 AND user_id = $2',
            [$postId, $userId]
        );
    }

    public function getCount(int $postId): int
    {
        return (int) $this->db->fetchScalar(
            'SELECT upvotes_count FROM posts WHERE id = $1',
            [$postId]
        );
    }
}
