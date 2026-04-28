<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\RateLimiter;
use App\Service\{PostService, CommentService, UpvoteService};
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PostController extends BaseController
{
    public function __construct(
        Renderer $view,
        private PostService    $posts,
        private CommentService $comments,
        private UpvoteService  $upvotes,
        private RateLimiter    $limiter,
    ) {
        parent::__construct($view);
    }

    // GET /
    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $posts   = $this->posts->getPaginated($page, 10, $this->userId());

        return $this->render($response, 'posts/index', [
            'posts'       => $posts,
            'page'        => $page,
            'totalPosts'  => $this->posts->getCount(),
            'perPage'     => 10,
        ]);
    }

    // GET /posts/{id}
    public function show(Request $request, Response $response, array $args): Response
    {
        $post = $this->posts->getById((int) $args['id'], $this->userId());

        if (! $post) {
            return $this->render($response, 'errors/404')->withStatus(404);
        }

        $comments = $this->comments->getByPost((int) $args['id']);

        return $this->render($response, 'posts/show', [
            'post'     => $post,
            'comments' => $comments,
        ]);
    }

    // GET /posts/create
    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'posts/create');
    }

    // POST /posts
    public function store(Request $request, Response $response): Response
    {
        if ($this->limiter->tooMany('post', 5, 1)) {
            $this->flash('error', 'Muitos posts criados. Aguarde um momento.');
            return $this->redirect($response, '/posts/create');
        }

        try {
            $postId = $this->posts->create($this->userId(), $this->body($request));
            $this->limiter->hit('post', 1);
            $this->flash('success', 'Post publicado com sucesso!');
            return $this->redirect($response, "/posts/{$postId}");
        } catch (\RuntimeException $e) {
            return $this->render($response, 'posts/create', [
                'error' => $e->getMessage(),
                'old'   => $this->body($request),
            ]);
        }
    }

    // GET /posts/{id}/edit
    public function edit(Request $request, Response $response, array $args): Response
    {
        $post = $this->posts->getById((int) $args['id']);

        if (! $post) {
            return $this->render($response, 'errors/404')->withStatus(404);
        }

        if ((int) $post['user_id'] !== $this->userId() && ! $this->isAdmin()) {
            return $this->render($response, 'errors/403')->withStatus(403);
        }

        return $this->render($response, 'posts/edit', ['post' => $post]);
    }

    // POST /posts/{id}/edit
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $this->posts->update(
                (int) $args['id'],
                $this->userId(),
                $this->isAdmin(),
                $this->body($request)
            );
            $this->flash('success', 'Post atualizado!');
            return $this->redirect($response, "/posts/{$args['id']}");
        } catch (\RuntimeException $e) {
            return $this->render($response, 'posts/edit', [
                'error' => $e->getMessage(),
                'post'  => array_merge(
                    $this->posts->getById((int) $args['id']) ?? [],
                    $this->body($request)
                ),
            ]);
        }
    }

    // POST /posts/{id}/delete
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $this->posts->delete((int) $args['id'], $this->userId(), $this->isAdmin());
            $this->flash('success', 'Post removido.');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect($response, '/');
    }

    // ------------------------------------------------------------------
    // API endpoints (JSON)
    // ------------------------------------------------------------------

    // GET /api/posts
    public function apiIndex(Request $request, Response $response): Response
    {
        $page  = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $posts = $this->posts->getPaginated($page, 10, $this->userId());

        return $this->json($response, $posts);
    }

    // POST /api/posts/{id}/upvote
    public function upvote(Request $request, Response $response, array $args): Response
    {
        if (! $this->isLoggedIn()) {
            return $this->json($response, ['error' => 'Login necessário.'], 401);
        }

        try {
            $result = $this->upvotes->toggle((int) $args['id'], $this->userId());
            return $this->json($response, $result);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 404);
        }
    }

    // POST /api/posts/{id}/comments
    public function addComment(Request $request, Response $response, array $args): Response
    {
        if (! $this->isLoggedIn()) {
            return $this->json($response, ['error' => 'Login necessário.'], 401);
        }

        $data = $this->body($request);

        try {
            $id = $this->comments->create(
                (int) $args['id'],
                $this->userId(),
                $data['content'] ?? ''
            );

            return $this->json($response, ['id' => $id, 'success' => true], 201);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 422);
        }
    }

    // DELETE /api/comments/{id}
    public function deleteComment(Request $request, Response $response, array $args): Response
    {
        if (! $this->isLoggedIn()) {
            return $this->json($response, ['error' => 'Login necessário.'], 401);
        }

        try {
            $this->comments->delete((int) $args['id'], $this->userId(), $this->isAdmin());
            return $this->json($response, ['success' => true]);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 403);
        }
    }

    // GET /api/posts — para compatibilidade legada
    public function fetchPosts(Request $request, Response $response): Response
    {
        return $this->apiIndex($request, $response);
    }

    // POST /api/posts/preview — renderiza markdown no servidor
    public function preview(Request $request, Response $response): Response
    {
        $data    = (array) $request->getParsedBody();
        $content = $data['content'] ?? '';
        $html    = $this->posts->renderMarkdown($content);

        return $this->json($response, ['html' => $html]);
    }

    // Helper para PostService::count exposto como getCount
    public function getCount(): int
    {
        return $this->posts->getCount();
    }
}
