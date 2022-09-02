<?php

namespace App\Middlewares;

use App\Exceptions\AuthorizationException;
use App\Services\OAuthService;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 2:46 AM
 */
class AuthorizeMiddleware implements MiddlewareInterface
{
    private $scopes = [];

    public function __construct(...$scopes)
    {
        $this->scopes = $scopes;
    }

    public function handle(App $app, callable $next = null): mixed
    {
        if (!OAuthService::getInstance()->validatePermission($this->scopes)) {
            throw new AuthorizationException();
        }
        return $next ? $next() : true;
    }
}
