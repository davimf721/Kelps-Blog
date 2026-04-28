<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\SessionManager;
use App\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Inicia sessão segura e verifica remember-me token em toda request.
 */
class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $auth) {}

    public function process(Request $request, Handler $handler): Response
    {
        SessionManager::start();
        $this->auth->checkRememberToken();

        return $handler->handle($request);
    }
}
