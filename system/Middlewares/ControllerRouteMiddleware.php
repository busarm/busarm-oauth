<?php

namespace System\Middlewares;

use System\DI;
use System\HttpException;
use System\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
class ControllerRouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $controller, private $function, private $params = [])
    {
    }

    public function handle(callable $next = null): mixed
    {
        if (class_exists($this->controller)) {
            // Load controller
            $object = DI::instantiate($this->controller);
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
            throw new HttpException(500, "Function not found or can't be executed: " . $this->controller . '::' . $this->function);
        }
        throw new HttpException(500, "Class does not exist: " . $this->controller);
    }
}
