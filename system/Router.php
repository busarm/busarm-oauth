<?php

namespace System;

use System\Interfaces\RouteInterface;
use System\Interfaces\RouterInterface;
use System\Interfaces\MiddlewareInterface;
use System\Middlewares\CallableRouteMiddleware;
use System\Middlewares\ControllerRouteMiddleware;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Router implements RouterInterface
{
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

    /** @var string HTTP request method */
    protected string|null $requestMethod = null;

    /** @var string HTTP request route */
    protected string|null $requestPath = null;

    /** @var RouteInterface Current HTTP route */
    protected RouteInterface|null $currentRoute = null;

    /** @var RouteInterface[] HTTP routes */
    protected array $routes = [];

    /**
     * @param string $controller
     * @param string $function
     * @param array $params
     */
    public function __construct(private $controller = null, private $function = null, private $params = [])
    {
        // Set Request Method
        $this->requestMethod = strtoupper(env('REQUEST_METHOD')) ?? NULL;

        // Set Request Path
        $this->requestPath = env('PATH_INFO') ?: (env('ORIG_PATH_INFO') ?: env('REQUEST_URI'));
        $this->requestPath = explode('?', $this->requestPath)[0];
    }

    /**
     * @return string|null
     */
    public function getRequestMethod(): string|null
    {
        return $this->requestMethod;
    }

    /**
     * @return string|null
     */
    public function getRequestPath(): string|null
    {
        return $this->requestPath;
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @return RouteInterface|null
     */
    public function getCurrentRoute(): RouteInterface|null
    {
        return $this->currentRoute;
    }

    /**
     * Process routing
     * @return MiddlewareInterface[]
     */
    public function process(): array
    {
        // If custom routes
        if ($this->controller && $this->function) {
            $routeMiddleware[] = new ControllerRouteMiddleware($this->controller, $this->function, $this->params);
            return $routeMiddleware;
        }
        // If http routes
        else {
            foreach ($this->routes as $route) {
                // Find route
                if (
                    strtoupper($route->getMethod()) == strtoupper($this->requestMethod) &&
                    ($params = $this->isMatch($this->requestPath, $route->getPath()))
                ) {
                    // Set current route
                    $this->currentRoute = is_array($params) ? $route->withParams($params) : $route;
                    // Callable
                    if ($callable = $this->currentRoute->getCallable()) {
                        $routeMiddleware = $this->currentRoute->getMiddlewares() ?? [];
                        $routeMiddleware[] = new CallableRouteMiddleware($callable, $this->currentRoute->getParams());
                        return $routeMiddleware;
                    }
                    // Controller
                    else {
                        $routeMiddleware = $route->getMiddlewares() ?? [];
                        $routeMiddleware[] = new ControllerRouteMiddleware($this->currentRoute->getController(), $this->currentRoute->getFunction(), $this->currentRoute->getParams());
                        return $routeMiddleware;
                    }
                }
            }
        }
        return [];
    }

    /**
     * @param Route $route 
     * @return RouterInterface
     */
    public function addRoute(RouteInterface $route): RouterInterface
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * @param Route[] $route 
     * @return RouterInterface
     */
    public function addRoutes(array $routes): RouterInterface
    {
        $this->routes = array_merge($this->routes, $routes);
        return $this;
    }

    /**
     * Check if path matches
     *
     * @param string $path Request path
     * @param string $route Route to compare to
     * @return boolean|array
     */
    private function isMatch($path, $route)
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
