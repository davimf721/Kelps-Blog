<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Connection;

class FollowRepository
{
    public function __construct(private Connection $db) {}

    public function isFollowing(int $followerId, int $followingId): bool
    {
        return (bool) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM followers WHERE follower_id = $1 AND following_id = $2',
            [$followerId, $followingId]
        );
    }

    public function follow(int $followerId, int $followingId): void
    {
        $this->db->execute(
            'INSERT INTO followers (follower_id, following_id) VALUES ($1, $2) ON CONFLICT DO NOTHING',
            [$followerId, $followingId]
        );
    }

    public function unfollow(int $followerId, int $followingId): void
    {
        $this->db->execute(
            'DELETE FROM followers WHERE follower_id = $1 AND following_id = $2',
            [$followerId, $followingId]
        );
    }

    public function getFollowers(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.username, u.profile_picture
             FROM followers f
             JOIN users u ON u.id = f.follower_id
             WHERE f.following_id = $1
             ORDER BY f.created_at DESC',
            [$userId]
        );
    }

    public function getFollowing(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.username, u.profile_picture
             FROM followers f
             JOIN users u ON u.id = f.following_id
             WHERE f.follower_id = $1
             ORDER BY f.created_at DESC',
            [$userId]
        );
    }

    public function countFollowers(int $userId): int
    {
        return (int) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM followers WHERE following_id = $1',
            [$userId]
        );
    }

    public function countFollowing(int $userId): int
    {
        return (int) $this->db->fetchScalar(
            'SELECT COUNT(*) FROM followers WHERE follower_id = $1',
            [$userId]
        );
    }
}
