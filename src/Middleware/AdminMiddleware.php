<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Protege rotas que exigem privilégios de administrador.
 */
class AdminMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        if (! SessionManager::get('is_admin', false)) {
            SessionManager::flash('error', 'Acesso restrito a administradores.');

            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
