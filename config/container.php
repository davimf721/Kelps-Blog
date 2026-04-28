<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Repository\{
    UserRepository,
    PostRepository,
    CommentRepository,
    NotificationRepository,
    FollowRepository,
    UpvoteRepository,
};
use App\Security\RateLimiter;
use App\Service\{
    AuthService,
    PostService,
    CommentService,
    UpvoteService,
    FollowService,
    ProfileService,
    UploadService,
};
use App\View\Renderer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Parsedown;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\create;
use function DI\get;
use function DI\factory;

return [
    // ------------------------------------------------------------------
    // Database
    // ------------------------------------------------------------------
    Connection::class => factory(function () {
        return Connection::getInstance();
    }),

    // ------------------------------------------------------------------
    // Logger
    // ------------------------------------------------------------------
    LoggerInterface::class => factory(function () {
        $log = new Logger('kelps');
        $log->pushHandler(new StreamHandler(
            ROOT_PATH . '/storage/logs/app.log',
            (getenv('APP_ENV') === 'production') ? Logger::WARNING : Logger::DEBUG
        ));
        return $log;
    }),

    // ------------------------------------------------------------------
    // Parsedown (Markdown)
    // ------------------------------------------------------------------
    Parsedown::class => factory(function () {
        $pd = new Parsedown();
        $pd->setSafeMode(true);
        return $pd;
    }),

    // ------------------------------------------------------------------
    // View Renderer
    // ------------------------------------------------------------------
    Renderer::class => factory(function () {
        return new Renderer(ROOT_PATH . '/resources/views');
    }),

    // ------------------------------------------------------------------
    // Upload Service
    // ------------------------------------------------------------------
    UploadService::class => factory(function () {
        return new UploadService(ROOT_PATH . '/public/uploads');
    }),

    // ------------------------------------------------------------------
    // Repositories (autowire via DI)
    // ------------------------------------------------------------------
    UserRepository::class         => autowire(),
    PostRepository::class         => autowire(),
    CommentRepository::class      => autowire(),
    NotificationRepository::class => autowire(),
    FollowRepository::class       => autowire(),
    UpvoteRepository::class       => autowire(),

    // ------------------------------------------------------------------
    // Services (autowire via DI)
    // ------------------------------------------------------------------
    RateLimiter::class     => autowire(),
    AuthService::class     => autowire(),
    PostService::class     => autowire(),
    CommentService::class  => autowire(),
    UpvoteService::class   => autowire(),
    FollowService::class   => autowire(),
    ProfileService::class  => autowire(),
];
