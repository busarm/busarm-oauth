<?php

namespace System;

use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use System\Dto\BaseDto;
use System\Dto\ResponseDto;
use System\HttpException;
use System\Interfaces\ErrorReportingInterface;
use System\Interfaces\LoaderInterface;
use System\Interfaces\LoggerInterface;
use System\Interfaces\MiddlewareInterface;
use System\Interfaces\RequestInterface;
use System\Interfaces\ResponseInterface;
use System\Interfaces\RouterInterface;
use System\Loader;
use System\Middlewares\ResponseMiddleware;
use Throwable;

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

    /** @var self */
    private static $instance;

    /** @var MiddlewareInterface[] */
    private $middlewares = [];

    /** @var array */
    public $singletons = [];

    /** @var array */
    public $bindings = [
        RequestInterface::class => Request::class,
        ResponseInterface::class => Response::class,
    ];

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

    /**
     * @param RouterInterface|null $router
     * @param array $configs List of custom config files in configuration directory to load. e.g aws, papertrail 
     */
    public function __construct($configs = array())
    {
        self::$instance = &$this;

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
        $this->logger = new Logger(ENVIRONMENT === ENV_DEV ? ConsoleOutput::VERBOSITY_DEBUG : ConsoleOutput::VERBOSITY_NORMAL);

        // Set up default configs
        $this->setUpConfigs(self::DEFAULT_CONFIGS);

        // Set up error reporting
        $this->setUpErrorHandlers();

        // Set up custom configs
        $this->setUpConfigs($configs);

        // Add response middleware as the first in the chain
        $this->addMiddleware(new ResponseMiddleware());
    }

    /**
     * Get App Instance
     *
     * @return App
     */
    public static function &getInstance()
    {
        return self::$instance;
    }


    ############################
    # Setup and Run
    ############################


    /**
     * Set up configs
     * @param array $configs
     */
    private function setUpConfigs($configs = array())
    {
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $this->addConfig((string) $config);
            }
        }
    }

    /**
     * Set up error handlers
     */
    private function setUpErrorHandlers()
    {
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null, $errcontext = []) {
            $this->reporter->reportError("Internal Server Error", $errline);
            $this->showMessage(500, false, $errno, $errstr, $errline, $errfile, $errcontext);
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
                $this->showMessage($e->getCode() >= 400 ? $e->getCode() : 500, false, "Unexpected Exception", $e->getMessage(), $e->getLine(), $e->getFile(), $trace);
            }
        });
    }

    /**
     * Initialize app
     *
     * @return void
     */
    public function run()
    {
        // Preflight Checking
        if (!is_cli()) {
            $this->preflight($this->router->getRequestMethod());
        }

        // If offline or on maintenance mode
        if (!empty(SYSTEM_START_UP_TIME) && !empty(SYSTEM_SHUT_DOWN_TIME)) {
            $start = new DateTime(SYSTEM_START_UP_TIME);
            $stop = (new DateTime(SYSTEM_SHUT_DOWN_TIME))->sub(DateInterval::createFromDateString('1 day'));
            if (time() < $start->getTimestamp() && time() >= $stop->getTimestamp()) {
                if (MAINTENANCE_MODE) {
                    $this->showMessage(503, false, "System is under maintenance. Please come back on " . $start->format('Y-m-d H:i P'));
                } else {
                    $this->showMessage(503, false, "System is currently offline. Please come back on " . $start->format('Y-m-d H:i P'));
                }
            }
        } else if (MAINTENANCE_MODE) {
            $this->showMessage(503, false, "System is under maintenance");
        }

        // Initiate rerouting
        if ($this->router) {
            if (!$this->processMiddleware(array_merge($this->middlewares, $this->router->process()))) {
                $this->showMessage(404, false, "Not found - " . $this->router->getRequestPath());
            }
        } else throw new Exception("Router not configured. See `addRouter`");
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight($method)
    {
        // Check for CORS access request
        if (CHECK_CORS == TRUE) {
            $headers = [];
            $allowed_cors_headers = ALLOWED_CORS_HEADERS;
            $exposed_cors_headers = EXPOSED_CORS_HEADERS;
            $allowed_cors_methods = ALLOWED_CORS_METHODS;
            $max_cors_age = MAX_CORS_AGE;

            // Convert the config items into strings
            $allowed_headers = implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []);
            $exposed_cors_headers = implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []);
            $allowed_methods = implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []);

            // If we want to allow any domain to access the API
            if (ALLOWED_ANY_CORS_DOMAIN == TRUE) {
                $headers['Access-Control-Allow-Origin'] = '*';
                $headers['Access-Control-Allow-Methods'] = $allowed_methods;
                $headers['Access-Control-Allow-Headers'] = $allowed_headers;
                $headers['Access-Control-Expose-Headers'] = $exposed_cors_headers;
                $headers['Access-Control-Allow-Max-Age'] = $max_cors_age;
            } else {
                // We're going to allow only certain domains access
                // Store the HTTP Origin header
                $origin = env('HTTP_ORIGIN') ?? env('HTTP_REFERER') ?? '';
                $allowed_origins = ALLOWED_CORS_ORIGINS;
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
                $this->showMessage(200, true, "Preflight Ok");
            }
        }
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
        $this->singletons[$className] = !empty($object) ? DI::instantiate($className, false) : $object;
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
            throw new Exception("Binding error: $className does not implement $interfaceName");
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
     * Add config file
     * 
     * @param string $config
     * @return self
     */
    public function addConfig(string $config)
    {
        $this->loader->config($config);
        return $this;
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
     * Add router
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
     * Add error reporter
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
     * Add middleware
     *
     * @param MiddlewareInterface[] $middlewares
     * @param int $index
     * @return mixed
     */
    public function processMiddleware(array $middlewares, $index = 0)
    {
        if (isset($middlewares[$index])) {
            return $middlewares[$index]->handle(fn () => $this->processMiddleware($middlewares, ++$index));
        }
        return false;
    }


    ############################
    # Response & Reports
    ############################


    /**
     * Show Message
     * @param string $code Code
     * @param bool $status Status
     * @param string $title Title
     * @param string $msg Message
     * @param string $line 
     * @param string $file 
     * @param string $trace 
     */
    public function showMessage($code, $status, $title, $msg = null, $line = null, $file = null,  $trace = [])
    {
        if (is_cli()) {
            if (!$status || $code !== 200 || $code !== 201) {
                $this->logger->logError(
                    PHP_EOL . "success\t-\tfalse" .
                        PHP_EOL . "message\t-\t" . ($msg ?? $title) .
                        PHP_EOL . "version\t-\t" . APP_VERSION .
                        PHP_EOL . "line\t-\t$line" .
                        PHP_EOL . "path\t-\t$file" .
                        PHP_EOL,
                    $trace
                );
            } else {
                $this->logger->logInfo(
                    PHP_EOL . "success\t-\ttrue" .
                        PHP_EOL . "message\t-\t" . ($msg ?? $title) .
                        PHP_EOL . "version\t-\t" . APP_VERSION .
                        PHP_EOL . "line\t-\t$line" .
                        PHP_EOL . "path\t-\t$file" .
                        PHP_EOL,
                    $trace
                );
            }
        } else {
            $response = new ResponseDto();
            $response->success = $status;
            $response->message = $msg ?? $title;

            // Show env info if not successful
            if ($code !== 200 || $code !== 201) {
                $response->env = ENVIRONMENT;
                $response->version = APP_VERSION;
                $response->ip = IPADDRESS;
            }

            // Show error info if not production
            if (ENVIRONMENT != ENV_PROD) {
                $response->line = !empty($line) ? $line : null;
                $response->file = !empty($file) ? $file : null;
                $response->trace = !empty($trace) ? $trace : null;
            }

            $this->response->setParameters($response->toArray());
            $this->response->setStatusCode($code < 600 ? $code : 500, ($msg ? $title : ''));
            $this->response->send();
        }
        die;
    }


    /**
     * Show Http Response
     * @param string $code Code
     * @param mixed $data Data
     * @param array $headers Headers
     */
    public function sendHttpResponse($code, $data = null, $headers = [])
    {
        if (!is_array($data)) {
            if ($data instanceof BaseDto) {
                $data = $data->toArray();
            } else if (is_object($data)) {
                $data = (array) $data;
            } else {
                $response = new ResponseDto();
                $response->success = $code < 300;
                $response->message = $data;
                $data = $response->toArray();
            }
        }

        $this->response->setParameters($data);
        $this->response->setHttpHeaders($headers);
        $this->response->setStatusCode($code < 600 ? $code : 500);
        $this->response->send();
        die;
    }

    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param \Throwable $exception
     * @param string $type @see \Bugsnag\Breadcrumbs\Breadcrumb::getTypes
     * @return void
     */
    public static function leaveBreadcrumbs($crumb, $type = null, array $metadata = [])
    {
        if (!empty(self::$instance->bugsnag)) {
            self::$instance->bugsnag->leaveBreadcrumb($crumb, $type, $metadata);
        }
    }
}
