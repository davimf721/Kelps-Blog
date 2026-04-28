<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Proteção CSRF via token sincronizado na sessão.
 */
class Csrf
{
    private const KEY    = 'csrf_token';
    private const LENGTH = 32;

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(self::LENGTH));
        }

        return $_SESSION[self::KEY];
    }

    public static function verify(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::KEY], $token);
    }

    public static function regenerate(): string
    {
        $_SESSION[self::KEY] = bin2hex(random_bytes(self::LENGTH));
        return $_SESSION[self::KEY];
    }

    /** Retorna campo hidden pronto para uso em formulários. */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s">',
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /** Extrai token do POST ou do header X-CSRF-Token. */
    public static function fromRequest(): ?string
    {
        return $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
    }
}
