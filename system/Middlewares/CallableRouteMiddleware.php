<?php

namespace System\Middlewares;

use Closure;
use System\DI;
use System\Errors\SystemError;
use System\Exceptions\HttpException;
use System\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
final class CallableRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private Closure $callable, private $params = [])
    {
    }

    public function handle(callable $next = null): mixed
    {
        if (is_callable($this->callable)) {
            return ($this->callable)(...array_merge(DI::resolveCallableDependencies($this->callable), $this->params));
        }
        throw new SystemError("Callable route can't be executed");
    }
}
