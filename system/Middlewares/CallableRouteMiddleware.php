<?php

namespace System\Middlewares;

use Closure;
use System\HttpException;
use System\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
class CallableRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private Closure $callable, private $params = [])
    {
    }

    public function handle(callable $next = null): mixed
    {
        if (is_callable($this->callable)) {
            return ($this->callable)(...$this->params);
        }
        throw new HttpException(500, "Callable route can't be executed");
    }
}
