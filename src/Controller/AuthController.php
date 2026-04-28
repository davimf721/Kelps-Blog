<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\SessionManager;
use App\Service\AuthService;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends BaseController
{
    public function __construct(
        Renderer $view,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    // GET /login
    public function showLogin(Request $request, Response $response): Response
    {
        if ($this->isLoggedIn()) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/login');
    }

    // POST /login
    public function login(Request $request, Response $response): Response
    {
        $data = $this->body($request);

        try {
            $this->auth->login(
                $data['username_or_email'] ?? '',
                $data['password'] ?? '',
                isset($data['remember_me'])
            );

            $redirect = SessionManager::get('redirect_after_login', '/');
            SessionManager::forget('redirect_after_login');

            return $this->redirect($response, $redirect);
        } catch (\RuntimeException $e) {
            return $this->render($response, 'auth/login', [
                'error' => $e->getMessage(),
                'old'   => ['username_or_email' => $data['username_or_email'] ?? ''],
            ]);
        }
    }

    // GET /register
    public function showRegister(Request $request, Response $response): Response
    {
        if ($this->isLoggedIn()) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/register');
    }

    // POST /register
    public function register(Request $request, Response $response): Response
    {
        $data = $this->body($request);

        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            return $this->render($response, 'auth/register', [
                'error' => 'As senhas não conferem.',
                'old'   => $data,
            ]);
        }

        try {
            $userId = $this->auth->register($data);
            $this->flash('success', 'Conta criada com sucesso! Faça login.');
            return $this->redirect($response, '/login');
        } catch (\RuntimeException $e) {
            return $this->render($response, 'auth/register', [
                'error' => $e->getMessage(),
                'old'   => $data,
            ]);
        }
    }

    // POST /logout
    public function logout(Request $request, Response $response): Response
    {
        if ($userId = $this->userId()) {
            $this->auth->logout($userId);
        }

        return $this->redirect($response, '/login');
    }

    // GET /forgot-password
    public function showForgotPassword(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/forgot-password');
    }

    // POST /forgot-password
    public function forgotPassword(Request $request, Response $response): Response
    {
        $data  = $this->body($request);
        $email = trim($data['email'] ?? '');

        $result = $this->auth->generateResetToken($email);

        // Sempre exibe mensagem genérica para não vazar se e-mail existe
        if ($result) {
            // TODO: enviar e-mail com $result['token']
            // EmailService::sendPasswordReset($result['user']['email'], $result['token']);
        }

        return $this->render($response, 'auth/forgot-password', [
            'success' => 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.',
        ]);
    }

    // GET /reset-password/{token}
    public function showResetPassword(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'auth/reset-password', [
            'token' => $args['token'],
        ]);
    }

    // POST /reset-password/{token}
    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $data = $this->body($request);

        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            return $this->render($response, 'auth/reset-password', [
                'token' => $args['token'],
                'error' => 'As senhas não conferem.',
            ]);
        }

        try {
            $ok = $this->auth->resetPassword($args['token'], $data['password'] ?? '');

            if (! $ok) {
                return $this->render($response, 'auth/reset-password', [
                    'token' => $args['token'],
                    'error' => 'Token inválido ou expirado.',
                ]);
            }

            $this->flash('success', 'Senha alterada com sucesso!');
            return $this->redirect($response, '/login');
        } catch (\RuntimeException $e) {
            return $this->render($response, 'auth/reset-password', [
                'token' => $args['token'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    // GET /banned
    public function banned(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/banned', ['layout' => 'minimal']);
    }
}
