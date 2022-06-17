<?php

namespace System;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Router
{

    const GET_METHOD =  "GET";
    const POST_METHOD =  "POST";
    const PUT_METHOD =  "PUT";
    const DELETE_METHOD =  "DELETE";

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
    }

    /**
     * Process routing
     * @return boolean
     */
    public function process()
    {
        // If custom routes
        if ($this->controller && $this->function) {
            return $this->reroute($this->controller, $this->function, $this->params);
        }
        // If http routes
        else {
            if ($path = $this->check()) {
                $routes = explode('::', $path);
                $controller = $routes[0] ?? null;
                $function = $routes[1] ?? null;
                return $this->reroute($controller, $function);
            }
        }
        return false;
    }

    /**
     * Set HTTP GET routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $function Application Controller (public) function. e.g index
     * @return self
     */
    public function get($path, $class, $function)
    {
        $this->routes[self::GET_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Set HTTP POST routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $method [Optional] Application Controller function. e.g index
     * @return self
     */
    public function post($path, $class, $function)
    {
        $this->routes[self::POST_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Set HTTP GET & POST routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $function Application Controller (public) function. e.g index
     * @return self
     */
    public function get_post($path, $class, $function)
    {
        $this->routes[self::GET_METHOD][$path] = $class . '::' . $function;
        $this->routes[self::POST_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Set HTTP PUT routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $method [Optional] Application Controller function. e.g index
     * @return self
     */
    public function put($path, $class, $function)
    {
        $this->routes[self::PUT_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Set HTTP POST & PUT routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $method [Optional] Application Controller function. e.g index
     * @return self
     */
    public function post_put($path, $class, $function)
    {
        $this->routes[self::POST_METHOD][$path] = $class . '::' . $function;
        $this->routes[self::PUT_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Set HTTP DELETE routes
     * 
     * @param $path HTTP path. e.g /home. Regular expression is supported
     * @param $class Application Controller class name e.g Home
     * @param $method [Optional] Application Controller function. e.g index
     * @return self
     */
    public function delete($path, $class, $function)
    {
        $this->routes[self::DELETE_METHOD][$path] = $class . '::' . $function;
        return $this;
    }

    /**
     * Check if path matches
     *
     * @param string $path
     * @param string $match
     * @return boolean|array
     */
    private function is_match($path, $match)
    {
        $match = str_replace(['/', '.'], ['\/', '\.'], $match);
        if (!empty($path) && preg_match("/$match/i", $path, $result)) {
            return $result ?: true;
        }
        return false;
    }

    /**
     * Check route
     * @param string $match 
     * @return bool|array Result matched request route with either specified route or supplied match
     */
    public function check($match = null)
    {
        if (!empty($match)) {
            return $this->is_match($this->route, $match) ? $match : false;
        } else if (!empty($this->method)) {
            foreach (($this->routes[$this->method] ?? []) as $match => $to) {
                if ($this->is_match($this->route, $match)) return $to;
            }
        }
        return false;
    }

    /**
     * Process Route
     *
     * @param string $controller
     * @param string $function
     * @param string $params
     * @return boolean|mixed
     */
    private function reroute($controller, $function, $params = [])
    {
        if (class_exists($controller)) {
            // Load controller
            $object = new $controller();
            if (
                method_exists($object, $function)
                && is_callable(array($object, $function))
            ) {
                return call_user_func_array(
                    array($object, $function),
                    $params
                );
            }
            app()->reportError("Route error", "Method can't be called: " . $function);
            return false;
        }
        app()->reportError("Route error", "Class does not exist: " . $controller);
        return false;
    }
}
