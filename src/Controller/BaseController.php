<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\SessionManager;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseController
{
    public function __construct(protected Renderer $view) {}

    // ------------------------------------------------------------------
    // Helpers de resposta
    // ------------------------------------------------------------------

    protected function render(Response $response, string $view, array $data = []): Response
    {
        return $this->view->render($response, $view, $data);
    }

    protected function redirect(Response $response, string $url, int $status = 302): Response
    {
        return $response->withHeader('Location', $url)->withStatus($status);
    }

    protected function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    // ------------------------------------------------------------------
    // Helpers de sessão
    // ------------------------------------------------------------------

    protected function userId(): ?int
    {
        return SessionManager::get('user_id');
    }

    protected function isAdmin(): bool
    {
        return (bool) SessionManager::get('is_admin', false);
    }

    protected function isLoggedIn(): bool
    {
        return SessionManager::has('user_id');
    }

    protected function flash(string $type, string $message): void
    {
        SessionManager::flash($type, $message);
    }

    // ------------------------------------------------------------------
    // Helpers de request
    // ------------------------------------------------------------------

    protected function body(Request $request): array
    {
        return (array) ($request->getParsedBody() ?? []);
    }

    protected function queryParam(Request $request, string $key, mixed $default = null): mixed
    {
        return $request->getQueryParams()[$key] ?? $default;
    }

    protected function routeArg(Request $request, string $key): mixed
    {
        $args = $request->getAttribute('__routingResults__');
        return $args?->getRouteArguments()[$key] ?? null;
    }
}
