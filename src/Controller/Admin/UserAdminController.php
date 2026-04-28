<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Repository\UserRepository;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserAdminController extends BaseController
{
    public function __construct(
        Renderer $view,
        private UserRepository $users,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $page  = max(1, (int) ($request->getQueryParams()['page'] ?? 1));
        $limit = 20;

        return $this->render($response, 'admin/users', [
            'layout' => 'admin',
            'users'  => $this->users->listAll($limit, ($page - 1) * $limit),
            'total'  => $this->users->count(),
            'page'   => $page,
        ]);
    }

    public function ban(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        if ($id === $this->userId()) {
            $this->flash('error', 'Você não pode banir a si mesmo.');
            return $this->redirect($response, '/admin/users');
        }

        $this->users->ban($id);
        $this->flash('success', 'Usuário banido.');
        return $this->redirect($response, '/admin/users');
    }

    public function unban(Request $request, Response $response, array $args): Response
    {
        $this->users->unban((int) $args['id']);
        $this->flash('success', 'Usuário desbanido.');
        return $this->redirect($response, '/admin/users');
    }

    public function makeAdmin(Request $request, Response $response, array $args): Response
    {
        $this->users->makeAdmin((int) $args['id']);
        $this->flash('success', 'Usuário promovido a admin.');
        return $this->redirect($response, '/admin/users');
    }

    public function removeAdmin(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        if ($id === $this->userId()) {
            $this->flash('error', 'Você não pode remover seus próprios privilégios.');
            return $this->redirect($response, '/admin/users');
        }

        $this->users->removeAdmin($id);
        $this->flash('success', 'Privilégios de admin removidos.');
        return $this->redirect($response, '/admin/users');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        if ($id === $this->userId()) {
            $this->flash('error', 'Você não pode deletar sua própria conta pelo painel admin.');
            return $this->redirect($response, '/admin/users');
        }

        $this->users->delete($id);
        $this->flash('success', 'Usuário excluído.');
        return $this->redirect($response, '/admin/users');
    }
}
