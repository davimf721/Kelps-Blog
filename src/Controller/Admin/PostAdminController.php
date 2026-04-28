<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Service\PostService;
use App\Repository\PostRepository;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostAdminController extends BaseController
{
    public function __construct(
        Renderer $view,
        private PostRepository $posts,
        private PostService    $postService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $page  = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $limit = 20;

        return $this->render($response, 'admin/posts', [
            'layout' => 'admin',
            'posts'  => $this->posts->listAll($limit, ($page - 1) * $limit),
            'total'  => $this->posts->count(),
            'page'   => $page,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $this->postService->delete((int) $args['id'], $this->userId(), isAdmin: true);
            $this->flash('success', 'Post removido.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/admin/posts');
    }
}
