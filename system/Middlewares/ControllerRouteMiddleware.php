<?php

namespace System\Middlewares;

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
final class ControllerRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $controller, private $function, private $params = [])
    {
    }

    public function handle(callable $next = null): mixed
    {
        if (class_exists($this->controller)) {
            // Load controller
            $object = app()->make($this->controller);
            if (
                // Load method
                method_exists($object, $this->function)
                && is_callable(array($object, $this->function))
            ) {
                return call_user_func_array(
                    array($object, $this->function),
                    array_merge(DI::resolveMethodDependencies($this->controller, $this->function), $this->params)
                );
            }
            throw new SystemError("Function not found or can't be executed: " . $this->controller . '::' . $this->function);
        }
        throw new SystemError("Class does not exist: " . $this->controller);
    }
}
