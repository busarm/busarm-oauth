<?php

namespace System\Interfaces;

use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
interface RouteInterface
{
    
    /**
     * Set HTTP GET routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function get($path, $class, $function, $middlewares = []): RouteInterface;

    /**
     * Set HTTP POST routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function post($path, $class, $function, $middlewares = []): RouteInterface;

    /**
     * Set HTTP GET & POST routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function get_post($path, $class, $function, $middlewares = []): RouteInterface;

    /**
     * Set HTTP PUT routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function put($path, $class, $function, $middlewares = []): RouteInterface;

    /**
     * Set HTTP POST & PUT routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function post_put($path, $class, $function, $middlewares = []): RouteInterface;

    /**
     * Set HTTP DELETE routes
     * 
     * @param string $path HTTP path. e.g /home. Regular expression is supported
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function delete($path, $class, $function, $middlewares = []): RouteInterface;
    
    /**
     * Process routing
     * @return \System\Interfaces\MiddlewareInterface[] 
     */
    public function process(): array;
}