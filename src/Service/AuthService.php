<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use App\Security\{InputSanitizer, RateLimiter, SessionManager};
use RuntimeException;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private RateLimiter $limiter,
    ) {}

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    /**
     * @throws RuntimeException com mensagem amigável em caso de falha
     */
    public function login(string $identifier, string $password, bool $remember = false): array
    {
        $key = 'login';

        if ($this->limiter->tooMany($key, 5, 15)) {
            $wait = $this->limiter->availableIn($key, 15);
            throw new RuntimeException("Muitas tentativas. Tente novamente em {$wait}s.");
        }

        $user = $this->users->findByUsernameOrEmail(trim($identifier));

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            $this->limiter->hit($key, 15);

            if ($user) {
                $this->users->incrementFailedLogin((int) $user['id']);
            }

            throw new RuntimeException('Usuário ou senha incorretos.');
        }

        if ($user['is_banned'] === 't') {
            throw new RuntimeException('Conta suspensa. Entre em contato com a administração.');
        }

        if (! empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            throw new RuntimeException('Conta temporariamente bloqueada por excesso de tentativas.');
        }

        // Login bem-sucedido
        $this->limiter->clear($key);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $this->users->recordLogin((int) $user['id'], $ip);

        SessionManager::regenerate();
        SessionManager::set('user_id', (int) $user['id']);
        SessionManager::set('username', $user['username']);
        SessionManager::set('is_admin', $user['is_admin'] === 't');

        if ($remember) {
            $this->setRememberCookie((int) $user['id']);
        }

        return $user;
    }

    public function logout(int $userId): void
    {
        $this->users->clearRememberToken($userId);

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }

        SessionManager::destroy();
    }

    // ------------------------------------------------------------------
    // Registro
    // ------------------------------------------------------------------

    /**
     * @throws RuntimeException com mensagem amigável
     */
    public function register(array $data): int
    {
        $key = 'register';

        if ($this->limiter->tooMany($key, 3, 60)) {
            throw new RuntimeException('Muitos registros do mesmo IP. Tente mais tarde.');
        }

        $username = InputSanitizer::username($data['username'] ?? '');
        $email    = InputSanitizer::email($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (! $username) {
            throw new RuntimeException('Nome de usuário inválido (3-30 chars, letras/números/_).');
        }

        if (! $email) {
            throw new RuntimeException('E-mail inválido.');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('A senha deve ter ao menos 8 caracteres.');
        }

        if ($this->users->existsByUsername($username)) {
            throw new RuntimeException('Esse nome de usuário já está em uso.');
        }

        if ($this->users->existsByEmail($email)) {
            throw new RuntimeException('Esse e-mail já está cadastrado.');
        }

        $this->limiter->hit($key, 60);

        return $this->users->create([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'is_admin'      => false,
            'is_banned'     => false,
            'is_active'     => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Recuperação de senha
    // ------------------------------------------------------------------

    public function generateResetToken(string $email): ?array
    {
        $user = $this->users->findByEmail($email);

        if (! $user) {
            return null; // Não revelar se e-mail existe
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->users->setPasswordResetToken((int) $user['id'], $token, $expires);

        return ['user' => $user, 'token' => $token];
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        if (strlen($newPassword) < 8) {
            throw new RuntimeException('A senha deve ter ao menos 8 caracteres.');
        }

        $user = $this->users->findByResetToken($token);

        if (! $user) {
            return false;
        }

        $this->users->updatePassword((int) $user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
        $this->users->clearResetToken((int) $user['id']);

        return true;
    }

    // ------------------------------------------------------------------
    // Remember me
    // ------------------------------------------------------------------

    public function checkRememberToken(): bool
    {
        if (SessionManager::has('user_id')) {
            return true;
        }

        $token = $_COOKIE['remember_token'] ?? null;

        if (! $token) {
            return false;
        }

        $user = $this->users->findByRememberToken($token);

        if (! $user || $user['is_banned'] === 't') {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }

        SessionManager::regenerate();
        SessionManager::set('user_id', (int) $user['id']);
        SessionManager::set('username', $user['username']);
        SessionManager::set('is_admin', $user['is_admin'] === 't');

        return true;
    }

    // ------------------------------------------------------------------
    // Helpers privados
    // ------------------------------------------------------------------

    private function setRememberCookie(int $userId): void
    {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $this->users->setRememberToken($userId, $token, $expires);

        setcookie('remember_token', $token, [
            'expires'  => strtotime('+30 days'),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
