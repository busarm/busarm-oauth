<?php

namespace App\Middlewares;

use App\Exceptions\AuthenticationException;
use App\Services\OAuthService;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 2:46 AM
 */
class AuthenticateMiddleware implements MiddlewareInterface
{
    public function handle(App $app, Callable $next = null): mixed
    {
        if(!OAuthService::getInstance()->validateClient() && !OAuthService::getInstance()->validateAccessToken()) {
            throw new AuthenticationException();
        }
        return $next ? $next() : true;
    }
}
