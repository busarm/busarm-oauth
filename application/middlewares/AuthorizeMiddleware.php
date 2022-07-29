<?php

namespace Application\Middlewares;

use Application\Exceptions\AuthorizationException;
use Application\Services\OAuthService;
use System\Interfaces\MiddlewareInterface;

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

    public function handle(callable $next = null): mixed
    {
        if (!OAuthService::getInstance()->validatePermission($this->scopes)) {
            throw new AuthorizationException();
        }
        return $next ? $next() : true;
    }
}
