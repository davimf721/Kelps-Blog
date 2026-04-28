<?php

declare(strict_types=1);

use App\Controller\{
    AuthController,
    PostController,
    ProfileController,
    NotificationController,
};
use App\Controller\Admin\{
    DashboardController,
    UserAdminController,
    PostAdminController,
    CommentAdminController,
};
use App\Middleware\{
    AuthMiddleware,
    AdminMiddleware,
    CsrfMiddleware,
    BannedMiddleware,
};
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // ------------------------------------------------------------------
    // Middlewares globais de segurança
    // ------------------------------------------------------------------
    $app->add(BannedMiddleware::class);
    $app->add(CsrfMiddleware::class);

    // ------------------------------------------------------------------
    // Auth — públicas
    // ------------------------------------------------------------------
    $app->get('/login',    [AuthController::class, 'showLogin']);
    $app->post('/login',   [AuthController::class, 'login']);
    $app->get('/register', [AuthController::class, 'showRegister']);
    $app->post('/register',[AuthController::class, 'register']);
    $app->post('/logout',  [AuthController::class, 'logout'])->add(AuthMiddleware::class);
    $app->get('/banned',   [AuthController::class, 'banned']);

    $app->get('/forgot-password',  [AuthController::class, 'showForgotPassword']);
    $app->post('/forgot-password', [AuthController::class, 'forgotPassword']);
    $app->get('/reset-password/{token}',  [AuthController::class, 'showResetPassword']);
    $app->post('/reset-password/{token}', [AuthController::class, 'resetPassword']);

    // ------------------------------------------------------------------
    // Posts — públicas
    // ------------------------------------------------------------------
    $app->get('/',              [PostController::class, 'index']);
    $app->get('/posts/{id:\d+}', [PostController::class, 'show']);

    // Posts — requer auth
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/posts/create',          [PostController::class, 'create']);
        $group->post('/posts',                [PostController::class, 'store']);
        $group->get('/posts/{id:\d+}/edit',   [PostController::class, 'edit']);
        $group->post('/posts/{id:\d+}/edit',  [PostController::class, 'update']);
        $group->post('/posts/{id:\d+}/delete',[PostController::class, 'destroy']);
    })->add(AuthMiddleware::class);

    // ------------------------------------------------------------------
    // Profile — públicas
    // ------------------------------------------------------------------
    $app->get('/profile/{id:\d+}', [ProfileController::class, 'show']);

    // Profile — requer auth
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/profile/edit',           [ProfileController::class, 'edit']);
        $group->post('/profile/edit',          [ProfileController::class, 'update']);
        $group->post('/profile/change-password',[ProfileController::class, 'changePassword']);
        $group->get('/profile/delete',         [ProfileController::class, 'showDelete']);
        $group->post('/profile/delete',        [ProfileController::class, 'delete']);
    })->add(AuthMiddleware::class);

    // ------------------------------------------------------------------
    // Notifications — requer auth
    // ------------------------------------------------------------------
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/notifications', [NotificationController::class, 'index']);
    })->add(AuthMiddleware::class);

    // ------------------------------------------------------------------
    // API — JSON endpoints
    // ------------------------------------------------------------------
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Posts
        $group->get('/posts',                           [PostController::class, 'apiIndex']);
        $group->post('/posts/preview',                  [PostController::class, 'preview']);
        $group->post('/posts/{id:\d+}/upvote',          [PostController::class, 'upvote']);
        $group->post('/posts/{id:\d+}/comments',        [PostController::class, 'addComment']);
        $group->delete('/comments/{id:\d+}',            [PostController::class, 'deleteComment']);

        // Follow
        $group->post('/users/{id:\d+}/follow',          [ProfileController::class, 'follow']);
        $group->get('/users/{id:\d+}/followers',        [ProfileController::class, 'getFollowers']);
        $group->get('/users/{id:\d+}/following',        [ProfileController::class, 'getFollowing']);

        // Notifications
        $group->get('/notifications/count',             [NotificationController::class, 'count']);
        $group->post('/notifications/read-all',         [NotificationController::class, 'markAllRead']);
        $group->delete('/notifications/{id:\d+}',       [NotificationController::class, 'delete']);
    });

    // ------------------------------------------------------------------
    // Admin — requer auth + admin
    // ------------------------------------------------------------------
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('',       [DashboardController::class, 'index']);
        $group->get('/',      [DashboardController::class, 'index']);

        // Users
        $group->get('/users',                    [UserAdminController::class, 'index']);
        $group->post('/users/{id:\d+}/ban',      [UserAdminController::class, 'ban']);
        $group->post('/users/{id:\d+}/unban',    [UserAdminController::class, 'unban']);
        $group->post('/users/{id:\d+}/make-admin',[UserAdminController::class, 'makeAdmin']);
        $group->post('/users/{id:\d+}/remove-admin',[UserAdminController::class, 'removeAdmin']);
        $group->post('/users/{id:\d+}/delete',   [UserAdminController::class, 'delete']);

        // Posts
        $group->get('/posts',                    [PostAdminController::class, 'index']);
        $group->post('/posts/{id:\d+}/delete',   [PostAdminController::class, 'destroy']);

        // Comments
        $group->get('/comments',                 [CommentAdminController::class, 'index']);
        $group->post('/comments/{id:\d+}/delete',[CommentAdminController::class, 'destroy']);

    })->add(AdminMiddleware::class)->add(AuthMiddleware::class);

    // ------------------------------------------------------------------
    // Health check (Railway)
    // ------------------------------------------------------------------
    $app->get('/health', function ($request, $response) {
        try {
            \App\Database\Connection::getInstance()->fetchScalar('SELECT 1');
            $status = 'healthy';
            $code   = 200;
        } catch (\Throwable $e) {
            $status = 'unhealthy';
            $code   = 503;
        }

        $response->getBody()->write(json_encode([
            'status'    => $status,
            'timestamp' => time(),
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($code);
    });
};
