<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticationException;
use App\Services\OAuthService;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
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
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return false|mixed Return `false` if failed
     */
    public function handle(RequestInterface|RouteInterface &$request, ResponseInterface &$response, callable $next = null): mixed
    {
        if ($request instanceof RequestInterface) {
            if (!OAuthService::make($request, $response)->validateClient() && !OAuthService::make($request, $response)->validateAccessToken()) {
                throw new AuthenticationException();
            }
        }
        return $next ? $next() : true;
    }
}
