<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticationException;
use App\Services\OAuthService;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 2:46 AM
 */
class AuthenticateMiddleware implements MiddlewareInterface
{

    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface $handle
     * @return ResponseInterface
     */
    public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if ($request instanceof RequestInterface) {
            if (!OAuthService::make($request)->validateClient() && !OAuthService::make($request)->validateAccessToken()) {
                throw new AuthenticationException();
            }
        }
        return $handler->handle($request);
    }
}
