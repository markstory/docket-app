<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This application re-uses the same controller for both API and HTML endpoints.
 *
 * Because of this I don't want CSRF applied to requests that are made via API tokens.
 */
class ApiCsrfProtectionMiddlewareMiddleware extends CsrfProtectionMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization && strtolower(substr($authorization, 0, 6)) === 'token') {
            return $handler->handle($request);
        }

        return parent::process($request, $handler);
    }
}
