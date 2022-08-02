<?php

namespace System;

use Closure;
use System\Interfaces\MiddlewareInterface;
use System\Interfaces\RouteInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Route implements RouteInterface
{

    const GET_METHOD =  "GET";
    const POST_METHOD =  "POST";
    const PUT_METHOD =  "PUT";
    const PATCH_METHOD =  "PATCH";
    const DELETE_METHOD =  "DELETE";

    /** @var Closure Request executable function */
    private Closure|null $callable = null;

    /** @var string Request controller */
    protected string|null $controller = null;

    /** @var string Request controller function*/
    protected string|null $function = null;

    /** @var array Request controller function params */
    protected array $params = [];

    /** @var string HTTP request method */
    protected string|null $method = null;

    /** @var string HTTP request path */
    protected string|null $path = null;

    /** @var MiddlewareInterface[] */
    protected array $middlewares = [];

    /**
     * @param string $method
     * @param string $path
     */
    private function __construct($method, $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**  @return Closure|null */
    public function getCallable(): Closure|null
    {
        return $this->callable;
    }
    /**  @return string */
    public function getController(): string|null
    {
        return $this->controller;
    }
    /**  @return string */
    public function getFunction(): string|null
    {
        return $this->function;
    }
    /**  @return string */
    public function getParams(): array|null
    {
        return $this->params;
    }
    /**  @return string */
    public function getMethod(): string|null
    {
        return $this->method;
    }
    /**  @return string */
    public function getPath(): string|null
    {
        return $this->path;
    }
    /**  @return MiddlewareInterface[] */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Set route params 
     * 
     * @return RouteInterface 
     */
    public function withParams(array $params): RouteInterface
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }


    /**
     * Set callable route destination
     * 
     * @param string $callable Function to execute for route
     * @return RouteInterface
     */
    public function call(Closure $callable): RouteInterface
    {
        $this->callable = $callable;
        $this->controller = null;
        $this->function = null;
        return $this;
    }

    /**
     * Set controller route destination
     * 
     * @param string $controller Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @return RouteInterface
     */
    public function to(string $controller, string $function): RouteInterface
    {
        $this->controller = $controller;
        $this->function = $function;
        $this->callable = null;
        return $this;
    }

    /**
     * Add route middlewares
     * 
     * @param MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return RouteInterface
     */
    public function middlewares(array $middlewares = []): RouteInterface
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Add route middleware
     * 
     * @param MiddlewareInterface $middlewares
     * @return RouteInterface
     */
    public function middleware(MiddlewareInterface $middleware): RouteInterface
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Set HTTP GET routes
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return RouteInterface
     */
    public static function get(string $path): RouteInterface
    {
        $route = new Route(self::GET_METHOD, $path);
        return $route;
    }

    /**
     * Set HTTP POST routes
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return static
     */
    public static function post(string $path): RouteInterface
    {
        $route = new Route(self::POST_METHOD, $path);
        return $route;
    }

    /**
     * Set HTTP PUT routes
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return static
     */
    public static function put(string $path): RouteInterface
    {
        $route = new Route(self::PUT_METHOD, $path);
        return $route;
    }

    /**
     * Set HTTP PATCH routes
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return static
     */
    public static function patch(string $path): RouteInterface
    {
        $route = new Route(self::PATCH_METHOD, $path);
        return $route;
    }

    /**
     * Set HTTP DELETE routes
     * 
     * @param string $path HTTP path. e.g /user. See `Router::MATCHER_REGX` for list of parameters matching keywords
     * @return static
     */
    public static function delete(string $path): RouteInterface
    {
        $route = new Route(self::DELETE_METHOD, $path);
        return $route;
    }
}
