<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\{FollowRepository, NotificationRepository};
use RuntimeException;

class FollowService
{
    public function __construct(
        private FollowRepository       $follows,
        private NotificationRepository $notifications,
    ) {}

    public function toggle(int $followerId, int $targetId): array
    {
        if ($followerId === $targetId) {
            throw new RuntimeException('Você não pode se seguir.');
        }

        $isFollowing = $this->follows->isFollowing($followerId, $targetId);

        if ($isFollowing) {
            $this->follows->unfollow($followerId, $targetId);
        } else {
            $this->follows->follow($followerId, $targetId);

            $this->notifications->create([
                'user_id'  => $targetId,
                'actor_id' => $followerId,
                'type'     => 'follow',
                'message'  => "Começou a te seguir.",
            ]);
            $this->notifications->incrementUnread($targetId);
        }

        return [
            'following'       => ! $isFollowing,
            'followers_count' => $this->follows->countFollowers($targetId),
        ];
    }

    public function getFollowers(int $userId): array
    {
        return $this->follows->getFollowers($userId);
    }

    public function getFollowing(int $userId): array
    {
        return $this->follows->getFollowing($userId);
    }

    public function isFollowing(int $followerId, int $targetId): bool
    {
        return $this->follows->isFollowing($followerId, $targetId);
    }
}
