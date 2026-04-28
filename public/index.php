<?php

declare(strict_types=1);

// ------------------------------------------------------------------
// Bootstrap
// ------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

// Carregar variáveis de ambiente (.env em desenvolvimento)
if (file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

// Configuração de erros
$isProd = (getenv('APP_ENV') ?: 'production') === 'production';

if ($isProd) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/storage/logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ------------------------------------------------------------------
// Container PHP-DI + Slim App
// ------------------------------------------------------------------
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

$builder = new ContainerBuilder();
$builder->addDefinitions(ROOT_PATH . '/config/container.php');

if ($isProd) {
    $builder->enableCompilation(ROOT_PATH . '/storage/cache/di');
}

$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// ------------------------------------------------------------------
// Middlewares globais (ordem: último adicionado = primeiro executado)
// ------------------------------------------------------------------
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Session middleware (inicia sessão e verifica remember-me)
$app->add(\App\Middleware\SessionMiddleware::class);

// Error handler
$errorMiddleware = $app->addErrorMiddleware(! $isProd, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new \App\Middleware\ErrorHandler($app->getCallableResolver(), $app->getResponseFactory())
);

// ------------------------------------------------------------------
// Rotas
// ------------------------------------------------------------------
(require ROOT_PATH . '/config/routes.php')($app);

// ------------------------------------------------------------------
// Run
// ------------------------------------------------------------------
$app->run();
