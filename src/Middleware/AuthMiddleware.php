<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;

/**
 * Protege rotas que exigem login.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (! SessionManager::has('user_id')) {
            SessionManager::set('redirect_after_login', (string) $request->getUri());
            SessionManager::flash('error', 'Você precisa estar logado para acessar esta página.');

            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
