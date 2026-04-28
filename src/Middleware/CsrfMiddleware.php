<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\Csrf;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Valida token CSRF em requests mutáveis (POST/PUT/PATCH/DELETE).
 * Rotas de API que usam JSON e header X-CSRF-Token também são cobertas.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function process(Request $request, Handler $handler): Response
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $body  = (array) $request->getParsedBody();
        $token = $body['csrf_token']
            ?? $request->getHeaderLine('X-CSRF-Token')
            ?: null;

        if (! Csrf::verify($token)) {
            $isJson = str_contains($request->getHeaderLine('Accept'), 'application/json');

            if ($isJson) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode(['error' => 'Token CSRF inválido.']));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            }

            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
