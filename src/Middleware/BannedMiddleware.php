<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repository\UserRepository;
use App\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Redireciona usuários banidos para /banned (exceto logout e a própria página).
 */
class BannedMiddleware implements MiddlewareInterface
{
    private const ALLOWED_PATHS = ['/logout', '/banned'];

    public function __construct(private UserRepository $users) {}

    public function process(Request $request, Handler $handler): Response
    {
        $userId = SessionManager::get('user_id');

        if (! $userId) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return $handler->handle($request);
            }
        }

        $user = $this->users->findById((int) $userId);

        if ($user && $user['is_banned'] === 't') {
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/banned')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
