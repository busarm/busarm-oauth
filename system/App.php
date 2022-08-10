<?php

namespace System;

use Closure;
use Exception;
use Throwable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

use System\Dto\BaseDto;
use System\Dto\CollectionBaseDto;
use System\Dto\ResponseDto;
use System\Interfaces\ErrorReportingInterface;
use System\Interfaces\LoaderInterface;
use System\Interfaces\MiddlewareInterface;
use System\Interfaces\RequestInterface;
use System\Interfaces\ResponseInterface;
use System\Interfaces\RouterInterface;
use System\Interfaces\SingletonInterface;
use System\Middlewares\ResponseMiddleware;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 10/14/2018
 * Time: 12:34 AM
 *
 * PHP Mini Framework
 *
 * @copyright busarm.com
 */

class App
{
    const DEFAULT_CONFIGS = [
        'app',
        'database',
        'mail',
        'services'
    ];

    /** @var MiddlewareInterface[] */
    private $middlewares = [];

    /** @var array */
    public $singletons = [];

    /** @var array */
    public $bindings = [];

    /** @var array */
    public $configs = [];

    /** @var RequestInterface */
    public $request;

    /** @var ResponseInterface */
    public $response;

    /** @var RouterInterface */
    public $router;

    /** @var LoggerInterface */
    public $logger;

    /** @var LoaderInterface */
    public $loader;

    /** @var ErrorReportingInterface */
    public $reporter;

    /** @var int Request start time in milliseconds */
    public $startTimeMs;

    /** @var string Path to config files - relative to app folder. Default: 'Views' */
    public $viewPath = "Views";

    /** @var string Path to config files - relative to app folder. Default: 'Configs' */
    public $configPath = "Configs";

    // SYSTEM HOOKS 
    private Closure|null $startHook = null;
    private Closure|null $completeHook = null;

    /**
     * @param RouterInterface|null $router
     * @param string $path App environment. Default: Env::LOCAL
     * @param string $path Relative path to app folder. Default: app. (Without leading or trailing slash)
     */
    public function __construct(public string $env = Env::LOCAL, public $path = 'app')
    {
        $this->setInstance();

        // Benchmark start time
        $this->startTimeMs = floor(microtime(true) * 1000);

        // Create request & response objects
        $this->request = new Request();
        $this->response = new Response();

        // Set error reporter
        $this->reporter = new ErrorReporter();

        // Set Loader
        $this->loader = new Loader();

        // Set router
        $this->router = new Router();

        // Set logger
        $this->logger = new ConsoleLogger(new ConsoleOutput((app()->env == Env::LOCAL || app()->env == Env::DEV) ? ConsoleOutput::VERBOSITY_DEBUG : ConsoleOutput::VERBOSITY_NORMAL, true));

        // Set up default configs
        $this->setUpConfigs(self::DEFAULT_CONFIGS);

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Add default bindings
        $this->addBinding(RouterInterface::class, Router::class);
        $this->addBinding(RequestInterface::class, Request::class);
        $this->addBinding(ResponseInterface::class, Response::class);
        $this->addBinding(LoaderInterface::class, Loader::class);
        $this->addBinding(ErrorReportingInterface::class, ErrorReporter::class);

        // Add response middleware as the first in the chain
        $this->addMiddleware(new ResponseMiddleware());
    }

    /**
     * Get App Instance
     *
     * @return App
     */
    public static function getInstance(): self|null
    {
        return $GLOBALS[APP_BASE_PATH . ':' . static::class] ?? null;
    }

    /**
     * Set App Instance
     *
     * @return App
     */
    public function setInstance()
    {
        $GLOBALS[APP_BASE_PATH . ':' . static::class] = &$this;
    }

    ############################
    # Setup and Run
    ############################

    /**
     * Load neccesary application files
     * 
     * @return void
     */
    public static function bootstrap()
    {
        defined('APP_BASE_PATH') or exit("Please define 'APP_BASE_PATH'");

        // Load packages
        require_once(APP_BASE_PATH . 'bootstrap/helpers.php');
    }

    /**
     * Set up configs
     * @param array $configs
     */
    private function setUpConfigs($configs = array())
    {
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->loadConfig((string) $config);
            }
        }
    }

    /**
     * Set up error handlers
     */
    private function setUpErrorHandlers()
    {
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            $this->reporter->reportError("Internal Server Error", $errstr, $errfile, $errline);
            $this->showMessage(500, $errstr, $errline, $errfile);
        });
        set_exception_handler(function (Throwable $e) {
            if ($e instanceof HttpException) {
                if ($e->getCode() >= 500) $this->reporter->reportException($e);
                $e->handler();
            } else {
                $this->reporter->reportException($e);
                $trace = array_map(function ($instance) {
                    return [
                        'file' => $instance['file'] ?? null,
                        'line' => $instance['line'] ?? null,
                        'class' => $instance['class'] ?? null,
                        'function' => $instance['function'] ?? null,
                    ];
                }, $e->getTrace());
                $this->showMessage($e->getCode() >= 400 ? $e->getCode() : 500, $e->getMessage(), $e->getLine(), $e->getFile(), $trace);
            }
        });
    }

    /**
     * Run application
     *
     * @param RequestInterface|null $request
     * @param ResponseInterface|null $response
     * @return void
     */
    public function run(RequestInterface &$request = null, ResponseInterface &$response = null,)
    {
        // Set request & response objects
        $this->request = $request ?? $this->request;
        $this->response = $response ?? $this->response;

        // Preflight Checking
        if (!is_cli()) {
            $this->preflight($this->router->getRequestMethod());
        }

        // Run start hook
        if ($this->startHook) ($this->startHook)($this);

        // Initiate rerouting
        if ($this->router) {
            if (!$this->processMiddleware(array_merge($this->middlewares, $this->router->process()))) {
                $this->showMessage(404, "Not found - " . $this->router->getRequestMethod() . ' ' . $this->router->getRequestPath());
            }
        } else throw new Exception("System Error: Router not configured. See `addRouter`");
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight($method)
    {
        // Check for CORS access request
        if (defined('CHECK_CORS') && CHECK_CORS == TRUE) {
            $headers = [];
            $allowed_cors_headers = defined('ALLOWED_CORS_HEADERS') ? ALLOWED_CORS_HEADERS : [];
            $exposed_cors_headers = defined('EXPOSED_CORS_HEADERS') ? EXPOSED_CORS_HEADERS : [];
            $allowed_cors_methods = defined('ALLOWED_CORS_METHODS') ? ALLOWED_CORS_METHODS : [];
            $max_cors_age = defined('MAX_CORS_AGE') ? MAX_CORS_AGE : 3600;

            // Convert the config items into strings
            $allowed_headers = implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []);
            $exposed_cors_headers = implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []);
            $allowed_methods = implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []);

            // If we want to allow any domain to access the API
            if (defined('ALLOWED_ANY_CORS_DOMAIN') && ALLOWED_ANY_CORS_DOMAIN == TRUE) {
                $headers['Access-Control-Allow-Origin'] = '*';
                $headers['Access-Control-Allow-Methods'] = $allowed_methods;
                $headers['Access-Control-Allow-Headers'] = $allowed_headers;
                $headers['Access-Control-Expose-Headers'] = $exposed_cors_headers;
                $headers['Access-Control-Allow-Max-Age'] = $max_cors_age;
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = env('HTTP_ORIGIN') ?? env('HTTP_REFERER') ?? '';
                $allowed_origins = defined('ALLOWED_CORS_ORIGINS') ? ALLOWED_CORS_ORIGINS : [];
                // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
                if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                    $headers['Access-Control-Allow-Origin'] = $origin;
                    $headers['Access-Control-Allow-Methods'] = $allowed_methods;
                    $headers['Access-Control-Allow-Headers'] = $allowed_headers;
                    $headers['Access-Control-Expose-Headers'] = $exposed_cors_headers;
                    $headers['Access-Control-Allow-Max-Age'] = $max_cors_age;
                }
            }

            // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
            if (strtolower($method) === 'options') {
                $this->sendHttpResponse(200, null, $headers);
            }
        } else {
            if (strtolower($method) === 'options') {
                // kill the response and send it to the client
                $this->showMessage(200, "Preflight Ok");
            }
        }
    }

    /**
     * Instantiate class with dependencies
     * 
     * @param string $className
     * @param bool $cache Save as singleton to be reused. Default: false
     * @return object
     */
    public function make($className, $cache = false)
    {
        if ($cache && ($singleton = $this->getSingleton($className))) return $singleton;
        else $instance = DI::instantiate($className);
        // Add instance as singleton is supported
        if ($cache && ($instance instanceof SingletonInterface)) {
            $this->addSingleton($className, $instance);
        }
        return $instance;
    }

    /**
     * Set path to config files - relative to app folder. 
     * (Without leading or trailing slash)
     *
     * @param string $configPath
     * @return void
     */
    public function setConfigPath(string $configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * Set path to view files - relative to app folder.
     * (Without leading or trailing slash)
     * 
     * @param string $viewPath
     * @return void
     */
    public function setViewPath(string $viewPath)
    {
        $this->viewPath = $viewPath;
    }

    /**
     * Add singleton
     * 
     * @param string $className
     * @param object|null $object
     * @return self
     */
    public function addSingleton($className, $object = null)
    {
        $this->singletons[$className] = &$object;
        return $this;
    }

    /**
     * Get singleton
     *
     * @param string $className
     * @param object $default
     * @return self
     */
    public function getSingleton($className, $default = null)
    {
        return $this->singletons[$className] ?? $default;
    }

    /**
     * Add interface binding
     *
     * @param string $interfaceName
     * @param string $className
     * @return self
     */
    public function addBinding($interfaceName, $className)
    {
        if (!in_array($interfaceName, class_implements($className))) {
            throw new Exception("Binding error: `$className` does not implement `$interfaceName`");
        }
        $this->bindings[$interfaceName] = $className;
        return $this;
    }

    /**
     * Get interface binding
     *
     * @param string $interfaceName
     * @param string $default
     * @return self
     */
    public function getBinding($interfaceName, $default = null)
    {
        return $this->bindings[$interfaceName] ?? $default;
    }

    /**
     * Load config file
     * 
     * @param string $config
     * @return self
     */
    public function loadConfig(string $config)
    {
        $configs = $this->loader->config($config);
        // Load configs into app
        if ($configs && is_array($configs)) {
            foreach ($configs as $key => $value) {
                $this->config($key, $value);
            }
        }
        return $this;
    }

    /**
     * Get config or Set if not available
     * 
     * @param string $config
     * @return mixed
     */
    public function config(string $name, $value = null)
    {
        $config = $this->configs[$name] ?? null;
        if (is_null($config) || !is_null($value)) {
            $this->configs[$name] = $config = $value;
        }
        return $config;
    }

    /**
     * Add middleware
     *
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Add router. Replaces existing
     *
     * @param RouterInterface $router
     * @return self
     */
    public function addRouter(RouterInterface $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * Add error reporter. Replaces existing
     * 
     * @param string $config
     * @return self
     */
    public function addErrorReporter(ErrorReportingInterface $reporter)
    {
        $this->reporter = $reporter;
        return $this;
    }

    /**
     * 
     * Process middleware
     *
     * @param MiddlewareInterface[] $middlewares
     * @param int $index
     * @return mixed
     */
    protected function processMiddleware(array $middlewares, $index = 0)
    {
        if (isset($middlewares[$index])) {
            return @$middlewares[$index]->handle(fn () => $this->processMiddleware($middlewares, ++$index));
        }
        return false;
    }

    /**
     * Hook to run before processing request.
     * Use this do perform any pre-validations such as maintainence mode checkings.
     *
     * @param Closure $startHook
     * @return void
     */
    public function beforeStart(Closure $startHook)
    {
        $this->startHook = $startHook;
    }

    /**
     * Hook to run after processing request.
     * This registers a shutdown handler.
     * @see `register_shutdown_function`
     *
     * @param Closure $completeHook
     * @return void
     */
    public function afterComplete(Closure $completeHook)
    {
        $this->completeHook = $completeHook;
        register_shutdown_function($this->completeHook, $this);
    }


    ############################
    # Response
    ############################


    /**
     * Show Message
     * @param string $code Code
     * @param string $message Message
     * @param string $errorLine 
     * @param string $errorFile 
     * @param array $errorTrace 
     */
    public function showMessage($code, $message = null, $errorLine = null, $errorFile = null,  $errorTrace = [])
    {
        if (is_cli()) {
            if ($code !== 200 || $code !== 201) {
                $this->logger->error(
                    PHP_EOL . "success\t-\tfalse" .
                        PHP_EOL . "message\t-\t$message" .
                        PHP_EOL . "version\t-\t" . APP_VERSION .
                        PHP_EOL . "line\t-\t$errorLine" .
                        PHP_EOL . "path\t-\t$errorFile" .
                        PHP_EOL,
                    $errorTrace
                );
            } else {
                $this->logger->info(
                    PHP_EOL . "success\t-\tfalse" .
                        PHP_EOL . "message\t-\t$message" .
                        PHP_EOL . "version\t-\t" . APP_VERSION .
                        PHP_EOL . "line\t-\t$errorLine" .
                        PHP_EOL . "path\t-\t$errorFile" .
                        PHP_EOL,
                    $errorTrace
                );
            }
        } else {
            $response = new ResponseDto();
            $response->success = $code == 200 || $code == 201;
            $response->message = $message;
            $response->env = $this->env;
            $response->version = APP_VERSION;
            $response->ip = app()->request->ip();

            // Show more info if not production
            if (!$response->success && $this->env !== Env::PROD) {
                $response->duration = (floor(microtime(true) * 1000) - $this->startTimeMs);
                $response->line = !empty($errorLine) ? $errorLine : null;
                $response->file = !empty($errorFile) ? $errorFile : null;
                $response->backtrace = !empty($errorTrace) ? $errorTrace : null;
            }

            $this->response
                ->setParameters($response->toArray())
                ->setStatusCode(($code >= 100 && $code < 600) ? $code : 500)
                ->send();
        }
    }


    /**
     * Send HTTP JSON Response
     * @param string $code Code
     * @param BaseDto|array|object|string $data Data
     * @param array $headers Headers
     */
    public function sendHttpResponse($code, $data = null, $headers = [])
    {
        if (!is_array($data)) {
            if ($data instanceof CollectionBaseDto) {
                $data = $data->toArray();
            } else if ($data instanceof BaseDto) {
                $data = $data->toArray();
            } else if (is_object($data)) {
                $data = (array) $data;
            } else {
                $response = new ResponseDto();
                $response->success = $code < 300;
                $response->message = $data;
                $response->duration = (floor(microtime(true) * 1000) - $this->startTimeMs);
                $data = $response->toArray();
            }
        }

        $this->response
            ->setParameters($data)
            ->setHttpHeaders($headers)
            ->setStatusCode(($code >= 100 && $code < 600) ? $code : 500)
            ->send();
    }
}
