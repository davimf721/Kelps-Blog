<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\View\Renderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController extends BaseController
{
    public function __construct(
        Renderer $view,
        private NotificationRepository $notifications,
    ) {
        parent::__construct($view);
    }

    // GET /notifications
    public function index(Request $request, Response $response): Response
    {
        $items = $this->notifications->findByUser($this->userId());
        $this->notifications->markAllRead($this->userId());

        return $this->render($response, 'profile/notifications', ['notifications' => $items]);
    }

    // GET /api/notifications/count
    public function count(Request $request, Response $response): Response
    {
        $count = $this->notifications->countUnread($this->userId());
        return $this->json($response, ['count' => $count]);
    }

    // POST /api/notifications/read-all
    public function markAllRead(Request $request, Response $response): Response
    {
        $this->notifications->markAllRead($this->userId());
        return $this->json($response, ['success' => true]);
    }

    // DELETE /api/notifications/{id}
    public function delete(Request $request, Response $response, array $args): Response
    {
        $this->notifications->delete((int) $args['id'], $this->userId());
        return $this->json($response, ['success' => true]);
    }
}
