<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\{PostRepository, NotificationRepository, FollowRepository};
use App\Security\InputSanitizer;
use Parsedown;
use RuntimeException;

class PostService
{
    public function __construct(
        private PostRepository         $posts,
        private NotificationRepository $notifications,
        private FollowRepository       $follows,
        private Parsedown              $parsedown,
    ) {}

    public function getPaginated(int $page, int $perPage = 10, ?int $viewerId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $rows   = $this->posts->paginate($perPage, $offset, $viewerId);

        return array_map(fn($row) => $this->enrichPost($row), $rows);
    }

    public function getById(int $id, ?int $viewerId = null): ?array
    {
        $post = $this->posts->findById($id);

        if (! $post) {
            return null;
        }

        return $this->enrichPost($post, full: true);
    }

    public function create(int $userId, array $data): int
    {
        $title   = InputSanitizer::string($data['title'] ?? '', 200);
        $content = InputSanitizer::markdown($data['content'] ?? '');

        if (strlen($title) < 3) {
            throw new RuntimeException('Título deve ter ao menos 3 caracteres.');
        }

        if (strlen($content) < 10) {
            throw new RuntimeException('Conteúdo muito curto.');
        }

        $postId = $this->posts->create([
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
        ]);

        // Notifica seguidores
        $followers = $this->follows->getFollowers($userId);
        foreach ($followers as $follower) {
            $this->notifications->create([
                'user_id'  => (int) $follower['id'],
                'actor_id' => $userId,
                'type'     => 'new_post',
                'post_id'  => $postId,
                'message'  => "Novo post publicado.",
            ]);
            $this->notifications->incrementUnread((int) $follower['id']);
        }

        return $postId;
    }

    public function update(int $postId, int $userId, bool $isAdmin, array $data): void
    {
        $post = $this->posts->findById($postId);

        if (! $post) {
            throw new RuntimeException('Post não encontrado.');
        }

        if ((int) $post['user_id'] !== $userId && ! $isAdmin) {
            throw new RuntimeException('Sem permissão para editar este post.');
        }

        $title   = InputSanitizer::string($data['title'] ?? '', 200);
        $content = InputSanitizer::markdown($data['content'] ?? '');

        if (strlen($title) < 3) {
            throw new RuntimeException('Título deve ter ao menos 3 caracteres.');
        }

        $this->posts->update($postId, [
            'title'   => $title,
            'content' => $content,
        ]);
    }

    public function delete(int $postId, int $userId, bool $isAdmin): void
    {
        $post = $this->posts->findById($postId);

        if (! $post) {
            throw new RuntimeException('Post não encontrado.');
        }

        if ((int) $post['user_id'] !== $userId && ! $isAdmin) {
            throw new RuntimeException('Sem permissão para deletar este post.');
        }

        $this->posts->delete($postId);
    }

    public function getCount(): int
    {
        return $this->posts->count();
    }

    public function renderMarkdown(string $content): string
    {
        return $this->parsedown->text($content);
    }

    // ------------------------------------------------------------------
    // Helpers privados
    // ------------------------------------------------------------------

    private function enrichPost(array $post, bool $full = false): array
    {
        // Preview do conteúdo (sem imagens, sem markdown)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $post['content']);
        $text = strip_tags($text ?? '');
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Extrai primeira imagem do markdown
        $firstImage = null;
        if (preg_match('/!\[([^\]]*)\]\(([^)]+)\)/', $post['content'], $m)) {
            $firstImage = $m[2];
        }

        $post['excerpt']     = mb_substr($text, 0, 200) . (mb_strlen($text) > 200 ? '...' : '');
        $post['first_image'] = $firstImage;

        if ($full) {
            $post['content_html'] = $this->parsedown->text($post['content']);
        }

        return $post;
    }
}
