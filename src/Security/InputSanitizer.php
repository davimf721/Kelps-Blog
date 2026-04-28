<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Sanitização e validação de inputs do usuário.
 */
class InputSanitizer
{
    public static function string(string $input, int $maxLength = 0): string
    {
        $clean = trim($input);

        if ($maxLength > 0) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }

    public static function email(string $email): ?string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    public static function int(mixed $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function username(string $username): ?string
    {
        $username = trim($username);

        if (! preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return null;
        }

        return $username;
    }

    /**
     * Sanitiza conteúdo Markdown removendo tags perigosas.
     * O Parsedown com setSafeMode(true) já cuida do resto no output.
     */
    public static function markdown(string $content): string
    {
        // Remove blocos de script
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        // Remove event handlers inline
        $clean = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean ?? $content);
        // Remove javascript: protocol
        $clean = preg_replace('/javascript\s*:/i', '', $clean ?? $content);

        return $clean ?? $content;
    }

    /** Escapa HTML para exibição segura em views. */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
