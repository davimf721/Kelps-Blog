<?php

declare(strict_types=1);

namespace App\Middleware;

use App\View\Renderer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Interfaces\CallableResolverInterface;

class ErrorHandler extends \Slim\Handlers\ErrorHandler
{
    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface  $responseFactory,
        private ?Renderer         $renderer = null,
        ?LoggerInterface          $logger   = null,
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
    }

    protected function respond(): Response
    {
        $exception  = $this->exception;
        $statusCode = $this->statusCode;

        // Log em produção para erros 5xx
        if ($statusCode >= 500) {
            error_log('[ErrorHandler] ' . $exception->getMessage());
        }

        // Tenta renderizar view de erro
        if ($this->renderer) {
            $view = match (true) {
                $exception instanceof HttpNotFoundException  => 'errors/404',
                $exception instanceof HttpForbiddenException => 'errors/403',
                $statusCode >= 500                          => 'errors/500',
                default                                     => 'errors/500',
            };

            try {
                $response = $this->responseFactory->createResponse($statusCode);
                return $this->renderer->render($response, $view, [
                    'layout' => 'minimal',
                    'code'   => $statusCode,
                ]);
            } catch (\Throwable) {
                // Fallback para texto simples
            }
        }

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write("Erro {$statusCode}");
        return $response;
    }
}
