<?php

namespace System;

use System\Interfaces\RouteInterface;
use System\Middlewares\RouteMiddleware;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Router implements RouteInterface
{

    const GET_METHOD =  "GET";
    const POST_METHOD =  "POST";
    const PUT_METHOD =  "PUT";
    const DELETE_METHOD =  "DELETE";

    const MATCH_ALPHA = "alpha";
    const MATCH_ALPHA_NUM = "alpha-dash";
    const MATCH_ALPHA_NUM_DASH = "alpha-num-dash";
    const MATCH_NUM = "num";
    const MATCH_ANY = "any";

    const PATH_EXCLUDE_LIST = ["$", "<", ">", " ", "[", "]", "{", "}", "^", "\\", "|", "%"];
    const MATCH_ESCAPE_LIST = [
        "/" => "\/",
        "." => "\.",
    ];
    const MATCHER_REGX = [
        "/\(" . self::MATCH_ALPHA . "\)/" => "([a-zA-Z]+)",
        "/\(" . self::MATCH_ALPHA_NUM . "\)/" => "([a-zA-Z-_]+)",
        "/\(" . self::MATCH_ALPHA_NUM_DASH . "\)/" => "([a-zA-Z0-9-_]+)",
        "/\(" . self::MATCH_NUM . "\)/" => "([0-9]+)",
        "/\(" . self::MATCH_ANY . "\)/" => "(.+)",
        "/\{\w*\}/" => "(.+)"
    ];

    const ROUTE_SEPARATOR = "::";

    /** @var string Request controller */
    public $controller;
    /** @var string Request controller */
    public $function;
    /** @var string Request params */
    public $params;

    /** @var string HTTP request method */
    public $method;

    /** @var string HTTP request route */
    public $route;

    /** @var array HTTP routes */
    public $routes = [
        self::GET_METHOD => [],
        self::POST_METHOD => [],
        self::PUT_METHOD => [],
        self::DELETE_METHOD => [],
    ];

    /** @var array HTTP routes */
    public $middlewares = [
        self::GET_METHOD => [],
        self::POST_METHOD => [],
        self::PUT_METHOD => [],
        self::DELETE_METHOD => []
    ];

    /**
     * 
     * @param string $controller
     * @param string $function
     * @param array $params
     */
    public function __construct($controller = null, $function = null, $params = [])
    {
        // Set Manual Requests
        $this->controller = $controller;
        $this->function = $function;
        $this->params = $params;

        // Set Request Method
        $this->method = strtoupper(env('REQUEST_METHOD')) ?? NULL;

        // Set Request Route
        $this->route = env('PATH_INFO');
        if (empty($this->route)) {
            $this->route = env('ORIG_PATH_INFO');
        }
        if (empty($this->route)) {
            $this->route = env('REQUEST_URI');
        }
        $this->route = explode('?', $this->route)[0];
    }

    /**
     * Process routing
     * @return \System\Interfaces\MiddlewareInterface[]
     */
    public function process(): array
    {
        // If custom routes
        if ($this->controller && $this->function) {
            return [new RouteMiddleware($this->controller, $this->function, $this->params)];
        }
        // If http routes
        else {
            foreach (($this->routes[$this->method] ?? []) as $match => $to) {
                if ($params = $this->is_match($this->route, $match)) {
                    $routes = explode(self::ROUTE_SEPARATOR, $to);
                    $this->controller = $routes[0] ?? null;
                    $this->function = $routes[1] ?? null;
                    $this->params = is_array($params) ? $params : [];
                    $routeMiddleware = $this->middlewares[$this->method][$match] ?? [];
                    $routeMiddleware[] = new RouteMiddleware($this->controller, $this->function, $this->params);
                    return $routeMiddleware;
                }
            }
        }
        return [];
    }

    /**
     * Set HTTP GET routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function get($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::GET_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::GET_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Set HTTP POST routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function post($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::POST_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::POST_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Set HTTP GET & POST routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function get_post($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::GET_METHOD][$path] = $this->build_route($class, $function);
        $this->routes[self::POST_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::GET_METHOD][$path] = $middlewares;
        $this->middlewares[self::POST_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Set HTTP PUT routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function put($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::PUT_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::PUT_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Set HTTP POST & PUT routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function post_put($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::POST_METHOD][$path] = $this->build_route($class, $function);
        $this->routes[self::PUT_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::POST_METHOD][$path] = $middlewares;
        $this->middlewares[self::PUT_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Set HTTP DELETE routes
     * 
     * @param string $path HTTP path. e.g /home. See `MATCHER_REGX` for list of variable matching keywords
     * @param string $class Application Controller class name e.g Home
     * @param string $function Application Controller (public) function. e.g index
     * @param \System\Interfaces\MiddlewareInterface[] $middlewares Array of Middleware Interface.
     * @return self
     */
    public function delete($path, $class, $function, $middlewares = []): RouteInterface
    {
        $this->routes[self::DELETE_METHOD][$path] = $this->build_route($class, $function);
        $this->middlewares[self::DELETE_METHOD][$path] = $middlewares;
        return $this;
    }

    /**
     * Build controller -> function route
     *
     * @param string $class Class name
     * @param string $function Function name
     * @return string
     */
    private function build_route($class, $function)
    {
        return $class . self::ROUTE_SEPARATOR . $function;
    }

    /**
     * Check if path matches
     *
     * @param string $path Request path
     * @param string $route Route to compare to
     * @return boolean|array
     */
    private function is_match($path, $route)
    {
        // Decode url
        $path = urldecode($path);
        // Remove unwanted characters from path
        $path = str_replace(self::PATH_EXCLUDE_LIST, "", $path);
        // Escape charaters to be a safe Regexp
        $route = str_replace(array_keys(self::MATCH_ESCAPE_LIST), array_values(self::MATCH_ESCAPE_LIST), $route);
        // Replace matching keywords with regexp 
        $route = preg_replace(array_keys(self::MATCHER_REGX), array_values(self::MATCHER_REGX), $route);
        // Search request path against route
        $result = preg_match("/$route$/i", $path, $matches);
        if (!empty($path) && $result >= 1) {
            $params = array_splice($matches, 1);
            return !empty($params) ? $params : true;
        }
        return false;
    }
}
