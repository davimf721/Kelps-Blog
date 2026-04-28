<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Repository\CommentRepository;
use App\Service\CommentService;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CommentAdminController extends BaseController
{
    public function __construct(
        Renderer $view,
        private CommentRepository $comments,
        private CommentService    $commentService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $page  = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $limit = 20;

        return $this->render($response, 'admin/comments', [
            'layout'   => 'admin',
            'comments' => $this->comments->listAll($limit, ($page - 1) * $limit),
            'total'    => $this->comments->count(),
            'page'     => $page,
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $this->commentService->delete((int) $args['id'], $this->userId(), isAdmin: true);
            $this->flash('success', 'Comentário removido.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/admin/comments');
    }
}
