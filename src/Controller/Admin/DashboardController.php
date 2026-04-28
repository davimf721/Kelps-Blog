<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Repository\{UserRepository, PostRepository, CommentRepository};
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function __construct(
        Renderer $view,
        private UserRepository    $users,
        private PostRepository    $posts,
        private CommentRepository $comments,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/dashboard', [
            'layout'        => 'admin',
            'totalUsers'    => $this->users->count(),
            'totalPosts'    => $this->posts->count(),
            'totalComments' => $this->comments->count(),
        ]);
    }
}
