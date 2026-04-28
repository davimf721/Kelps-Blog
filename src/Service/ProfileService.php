<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\{UserRepository, PostRepository};
use App\Security\InputSanitizer;
use RuntimeException;

class ProfileService
{
    public function __construct(
        private UserRepository $users,
        private PostRepository $posts,
    ) {}

    public function getProfile(int $userId): ?array
    {
        $user = $this->users->findById($userId);

        if (! $user) {
            return null;
        }

        $stats = $this->users->getStats($userId);

        return array_merge($user, [
            'posts_count'     => (int) $stats['posts_count'],
            'followers_count' => (int) $stats['followers_count'],
            'following_count' => (int) $stats['following_count'],
        ]);
    }

    public function update(int $userId, array $data): void
    {
        $bio      = InputSanitizer::string($data['bio'] ?? '', 500);
        $location = InputSanitizer::string($data['location'] ?? '', 100);
        $website  = InputSanitizer::string($data['website'] ?? '', 255);

        // Validação simples de URL
        if ($website && ! filter_var($website, FILTER_VALIDATE_URL)) {
            $website = '';
        }

        $this->users->update($userId, [
            'bio'      => $bio,
            'location' => $location,
            'website'  => $website,
        ]);
    }

    public function updateAvatar(int $userId, string $filename): void
    {
        $this->users->update($userId, ['profile_picture' => $filename]);
    }

    public function updateBanner(int $userId, string $filename): void
    {
        $this->users->update($userId, ['banner_image' => $filename]);
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        $user = $this->users->findById($userId);

        if (! $user || ! password_verify($current, $user['password_hash'])) {
            throw new RuntimeException('Senha atual incorreta.');
        }

        if (strlen($new) < 8) {
            throw new RuntimeException('A nova senha deve ter ao menos 8 caracteres.');
        }

        $this->users->updatePassword($userId, password_hash($new, PASSWORD_BCRYPT));
    }

    public function deleteAccount(int $userId, string $password): void
    {
        $user = $this->users->findById($userId);

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Senha incorreta.');
        }

        $this->users->delete($userId);
    }

    public function getUserPosts(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->posts->findByUser($userId, $perPage, $offset);
    }
}
