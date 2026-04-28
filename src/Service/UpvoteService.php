<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\{UpvoteRepository, PostRepository, NotificationRepository};
use RuntimeException;

class UpvoteService
{
    public function __construct(
        private UpvoteRepository       $upvotes,
        private PostRepository         $posts,
        private NotificationRepository $notifications,
    ) {}

    /** Toggle upvote: adiciona ou remove. Retorna novo total. */
    public function toggle(int $postId, int $userId): array
    {
        $post = $this->posts->findById($postId);

        if (! $post) {
            throw new RuntimeException('Post não encontrado.');
        }

        $hasUpvoted = $this->upvotes->hasUpvoted($postId, $userId);

        if ($hasUpvoted) {
            $this->upvotes->remove($postId, $userId);
        } else {
            $this->upvotes->add($postId, $userId);

            // Notifica o autor (exceto se ele mesmo deu upvote)
            if ((int) $post['user_id'] !== $userId) {
                $this->notifications->create([
                    'user_id'  => (int) $post['user_id'],
                    'actor_id' => $userId,
                    'type'     => 'upvote',
                    'post_id'  => $postId,
                    'message'  => "Seu post recebeu um upvote.",
                ]);
                $this->notifications->incrementUnread((int) $post['user_id']);
            }
        }

        $newCount = $this->upvotes->getCount($postId);

        return [
            'upvoted'       => ! $hasUpvoted,
            'upvotes_count' => $newCount,
        ];
    }
}
