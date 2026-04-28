<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\{CommentRepository, PostRepository, NotificationRepository};
use App\Security\{InputSanitizer, RateLimiter};
use RuntimeException;

class CommentService
{
    public function __construct(
        private CommentRepository      $comments,
        private PostRepository         $posts,
        private NotificationRepository $notifications,
        private RateLimiter            $limiter,
    ) {}

    public function getByPost(int $postId): array
    {
        return $this->comments->findByPost($postId);
    }

    public function create(int $postId, int $userId, string $content): int
    {
        if ($this->limiter->tooMany('comment', 10, 1)) {
            throw new RuntimeException('Muitos comentários. Aguarde um momento.');
        }

        $content = InputSanitizer::string($content, 2000);

        if (strlen($content) < 2) {
            throw new RuntimeException('Comentário muito curto.');
        }

        $post = $this->posts->findById($postId);

        if (! $post) {
            throw new RuntimeException('Post não encontrado.');
        }

        $commentId = $this->comments->create([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
        ]);

        $this->limiter->hit('comment', 1);

        // Notifica o autor do post (se não for o mesmo usuário)
        if ((int) $post['user_id'] !== $userId) {
            $this->notifications->create([
                'user_id'  => (int) $post['user_id'],
                'actor_id' => $userId,
                'type'     => 'comment',
                'post_id'  => $postId,
                'message'  => "Novo comentário no seu post.",
            ]);
            $this->notifications->incrementUnread((int) $post['user_id']);
        }

        return $commentId;
    }

    public function delete(int $commentId, int $userId, bool $isAdmin): void
    {
        $comment = $this->comments->findById($commentId);

        if (! $comment) {
            throw new RuntimeException('Comentário não encontrado.');
        }

        if ((int) $comment['user_id'] !== $userId && ! $isAdmin) {
            throw new RuntimeException('Sem permissão para deletar este comentário.');
        }

        $this->comments->delete($commentId);
    }
}
