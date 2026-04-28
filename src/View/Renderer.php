<?php

declare(strict_types=1);

namespace App\View;

use App\Security\{Csrf, SessionManager};
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Renderizador de views PHP nativas com suporte a layouts.
 *
 * Uso no controller:
 *   return $this->view->render($response, 'posts/index', ['posts' => $posts]);
 */
class Renderer
{
    public function __construct(private string $viewsPath) {}

    /**
     * @param array<string, mixed> $data Variáveis disponíveis na view
     */
    public function render(Response $response, string $view, array $data = []): Response
    {
        $content = $this->renderView($view, $data);
        $response->getBody()->write($content);

        return $response;
    }

    /** Renderiza somente a view, sem layout (para partials / AJAX). */
    public function partial(string $view, array $data = []): string
    {
        return $this->renderView($view, $data, withLayout: false);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function renderView(string $view, array $data, bool $withLayout = true): string
    {
        // Variáveis globais sempre disponíveis nas views
        $data['csrf']         = Csrf::field();
        $data['csrfToken']    = Csrf::token();
        $data['currentUser']  = $this->currentUser();
        $data['flash']        = $this->pullFlash();

        // Renderiza o conteúdo da view
        $content = $this->capture(
            $this->viewsPath . '/' . $view . '.php',
            $data
        );

        if (! $withLayout) {
            return $content;
        }

        // Descobre qual layout usar
        $layout = $data['layout'] ?? $this->guessLayout($view);

        $data['content'] = $content;

        return $this->capture(
            $this->viewsPath . '/layout/' . $layout . '.php',
            $data
        );
    }

    /** Executa o arquivo PHP capturando o output. */
    private function capture(string $file, array $data): string
    {
        if (! file_exists($file)) {
            throw new \RuntimeException("View não encontrada: {$file}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private function guessLayout(string $view): string
    {
        if (str_starts_with($view, 'admin/')) return 'admin';
        if (str_starts_with($view, 'auth/'))  return 'minimal';
        return 'main';
    }

    private function currentUser(): array
    {
        return [
            'id'       => SessionManager::get('user_id'),
            'username' => SessionManager::get('username'),
            'is_admin' => SessionManager::get('is_admin', false),
        ];
    }

    private function pullFlash(): array
    {
        return [
            'success' => SessionManager::flash('success'),
            'error'   => SessionManager::flash('error'),
            'info'    => SessionManager::flash('info'),
        ];
    }
}
