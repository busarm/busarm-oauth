<?php

namespace System;

use DateInterval;
use DateTime;
use Exception;

define("ENV_DEV", "development");
define("ENV_PROD", "production");
define("ENV_TEST", "testing");

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 10/14/2018
 * Time: 12:34 AM
 *
 * Mini Framework for Busarm
 *
 * @copyright busarm.com
 */

class App
{

    const CONTROLLER_PATH =  "application/controllers/";
    const LIBRARY_PATH =  "application/library/";
    const VIEW_PATH =  "application/views/";
    const MODEL_PATH =  "application/model/";

    private static $instance;

    /** @var \Bugsnag\Client */
    private $bugsnag;

    /** @var Router */
    public $router;

    /**
     * @param Router|null $router
     */
    public function __construct(Router $router = null)
    {
        self::$instance = &$this;

        // Set router
        $this->router = $router ?: new Router();

        // Set up envs
        $this->setUpEnvironment();

        if ($key = Configs::BUGSNAG_KEY()) {
            $this->bugsnag = \Bugsnag\Client::make($key);
            $this->bugsnag->setReleaseStage(ENVIRONMENT);
            $this->bugsnag->setAppType(is_cli() ? "Console" : "HTTP");
            \Bugsnag\Handler::register($this->bugsnag);
        }
        set_error_handler(function ($errno, $errstr, $errfile = null, $errline = null) {
            if ($this->bugsnag) {
                $this->bugsnag->notifyError("unexpected_error", $errstr);
            }
            $this->showMessage(500, false, "unexpected_error", $errstr, $errline, $errfile);
        });
        set_exception_handler(function ($e) {
            if ($this->bugsnag) {
                $this->bugsnag->notifyException($e);
            }
            $trace = array_map(function ($instance) {
                return [
                    'file' => $instance['file'] ?? null,
                    'line' => $instance['line'] ?? null,
                    'class' => $instance['class'] ?? null,
                    'function' => $instance['function'] ?? null,
                ];
            }, $e->getTrace());
            $this->showMessage(500, false, "unexpected_exception", $e->getMessage(), $e->getLine(), $e->getFile(), $trace);
        });
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

    /**
     * Get Bugsnag Client
     *
     * @return \Bugsnag\Client
     */
    public function getBugsnag()
    {
        return $this->bugsnag;
    }

    /**
     * Set up environment
     */
    private function setUpEnvironment()
    {
        // Define user's IP Address as to be viewed across the server
        define('IPADDRESS', get_ip_address());

        // Define Server Local Ip
        define('LOCALHOST', getHostByName(getHostName()));

        // Define base variables
        define("BASE_SCHEME", (is_https() ? "https" : "http") . "://");
        define("BASE_SERVER", BASE_SCHEME . env('HTTP_HOST'));
        define("BASE_URL", BASE_SERVER . str_replace(basename(env('SCRIPT_NAME')), "", env('SCRIPT_NAME')));
        define("CURRENT_URL", BASE_SERVER . env('REQUEST_URI'));

        // Define HTTP_VERSION
        define("HTTP_VERSION", get_server_protocol());


        /*
        *---------------------------------------------------------------
        * APPLICATION ENVIRONMENT
        *---------------------------------------------------------------
        *
        * You can load different configurations depending on your
        * current environment. Setting the environment also influences
        * things like logging and error reporting.
        *
        * This can be set to anything, but default usage is:
        *
        *     development
        *     testing
        *     production
        *
        * NOTE: If you change these, also change the error_reporting() code below
        */

        if (strtolower(env('ENV')) == "prod" || strtolower(env('STAGE')) == "prod") {
            define('ENVIRONMENT', ENV_PROD);
        } else if (strtolower(env('ENV')) == "dev" || strtolower(env('STAGE')) == "dev") {
            define('ENVIRONMENT', ENV_TEST);
        } else {
            define('ENVIRONMENT', ENV_DEV);
        }


        /*
        *---------------------------------------------------------------
        * ERROR REPORTING
        *---------------------------------------------------------------
        *
        * Different environments will require different levels of error reporting.
        * By default development will show errors but testing and live will hide them.
        */
        switch (ENVIRONMENT) {
            case ENV_DEV:
            case ENV_TEST:
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                break;
            case ENV_PROD:
                ini_set('display_errors', 0);
                if (version_compare(PHP_VERSION, '5.3', '>=')) {
                    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
                } else {
                    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
                }
                break;

            default:
                header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
                echo 'The application environment is not set correctly.';
                exit(1); // EXIT_ERROR
        }
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
            $this->preflight($this->router->method);
        }

        // If offline or on maintenance mode
        if (!empty(Configs::SYSTEM_START_UP_TIME()) && !empty(Configs::SYSTEM_SHUT_DOWN_TIME())) {
            $start = new DateTime(Configs::SYSTEM_START_UP_TIME());
            $stop = (new DateTime(Configs::SYSTEM_SHUT_DOWN_TIME()))->sub(DateInterval::createFromDateString('1 day'));
            if (time() < $start->getTimestamp() && time() >= $stop->getTimestamp()) {
                if (Configs::MAINTENANCE_MODE()) {
                    $this->showMessage(503, false, "System is under maintenance. Please come back on " . $start->format('Y-m-d H:i P'));
                } else {
                    $this->showMessage(503, false, "System is currently offline. Please come back on " . $start->format('Y-m-d H:i P'));
                }
            }
        } else if (Configs::MAINTENANCE_MODE()) {
            $this->showMessage(503, false, "System is under maintenance");
        }

        // Initiate rerouting
        if ($this->router->check('/ping')) {
            $this->showMessage(200, true, "System Online");
        } else if (!$this->router->process()) {
            $this->showMessage(404, false, "Not found");
        }
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight($method)
    {
        // Check for CORS access request
        if (Configs::CHECK_CORS == TRUE) {
            $this->check_cors();
        } else {
            if (strtolower($method) === 'options') {
                // kill the response and send it to the client
                $this->showMessage(200, true, "Preflight Ok");
            }
        }
    }

    #---------------------------
    # Loaders
    #---------------------------


    /**Load View
     * @param $path
     * @param array $vars
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function loadView($path, $vars = array(), $return = false)
    {
        if ($filePath = Utils::fileExists(FCPATH . self::VIEW_PATH . $path . ".php")) {
            ob_start();
            if (!empty($vars))
                extract($vars);
            include $filePath;
            $content = ob_get_contents();
            ob_end_clean();

            if ($return) {
                return $content;
            } else {
                echo $content;
                exit;
            }
        } else {
            if ($return) {
                return null;
            } else {
                throw new Exception("File does not Exist");
            }
        }
    }

    /**Load Library
     * @param $path
     * @param array $vars
     * @throws Exception
     */
    public function loadLibrary($path, $vars = array())
    {
        if ($filePath = Utils::fileExists(FCPATH . self::LIBRARY_PATH . $path . ".php")) {
            if (!empty($vars)) extract($vars);
            require_once $filePath;
        } else {
            throw new Exception("File does not Exist");
        }
    }


    /**Load Model
     * @param $path
     * @param array $vars
     * @throws Exception
     */
    public function loadModel($path, $vars = array())
    {
        if ($filePath = Utils::fileExists(FCPATH . self::MODEL_PATH . $path . ".php")) {
            if (!empty($vars)) extract($vars);
            require_once $filePath;
        } else {
            throw new Exception("File does not Exist");
        }
    }

    /**
     * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
     * @credits Codeigniter
     * 
     * @access protected
     * @return void
     */
    protected function check_cors()
    {
        $allowed_cors_headers = Configs::ALLOWED_CORS_HEADERS;
        $exposed_cors_headers = Configs::EXPOSED_CORS_HEADERS;
        $allowed_cors_methods = Configs::ALLOWED_CORS_METHODS;
        $max_cors_age = Configs::MAX_CORS_AGE;

        // Convert the config items into strings
        $allowed_headers = implode(', ', is_array($allowed_cors_headers) ? $allowed_cors_headers : []);
        $exposed_cors_headers = implode(', ', is_array($exposed_cors_headers) ? $exposed_cors_headers : []);
        $allowed_methods = implode(', ', is_array($allowed_cors_methods) ? $allowed_cors_methods : []);

        // If we want to allow any domain to access the API
        if (Configs::ALLOWED_ANY_CORS_DOMAIN == TRUE) {
            header(HTTP_VERSION . " 200 OK", TRUE, 200);
            header('Access-Control-Allow-Origin: *', true);
            header('Access-Control-Allow-Methods: ' . $allowed_methods, true);
            header('Access-Control-Allow-Headers: ' . $allowed_headers, true);
            header('Access-Control-Expose-Headers: ' . $exposed_cors_headers, true);
            header('Access-Control-Max-Age: ' . $max_cors_age, true);
        } else {

            // We're going to allow only certain domains access
            // Store the HTTP Origin header
            $origin = env('HTTP_ORIGIN') ?? env('HTTP_REFERER') ?? '';

            $allowed_origins = Configs::ALLOWED_CORS_ORIGINS;

            // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
            if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                header(HTTP_VERSION . " 200 OK", true, 200);
                header('Access-Control-Allow-Origin: ' . $origin, true);
                header('Access-Control-Allow-Methods: ' . $allowed_methods, true);
                header('Access-Control-Allow-Headers: ' . $allowed_headers, true);
                header('Access-Control-Expose-Headers: ' . $exposed_cors_headers, true);
                header('Access-Control-Max-Age: ' . $max_cors_age, true);
            }
        }

        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if (strtolower(env("REQUEST_METHOD")) === 'options') {
            die();
        }
    }

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
        !ob_get_contents() ?: ob_clean();
        ob_start();
        if (is_cli()) {
            echo "status - false" . PHP_EOL . "msg - " . ($msg ?? $title) . PHP_EOL .  "version - " . Configs::APP_VERSION() . PHP_EOL . "line - $line" . PHP_EOL . "file path - $file" . PHP_EOL;
        } else {
            if (!headers_sent()) {
                header(HTTP_VERSION . ' ' . $code . ' ' . $msg ? $title : '', TRUE, $code);
                header("Content-type: application/json");
                header('Access-Control-Allow-Origin: *', true);
                header('Access-Control-Allow-Methods: *', true);
            }
            $data = ['status' => $status, 'msg' => $msg ?? $title, 'env' => ENVIRONMENT, 'version' => Configs::APP_VERSION(), 'ip' => IPADDRESS];
            if (ENVIRONMENT != ENV_PROD) {
                if (!empty($line)) $data['line'] = $line;
                if (!empty($file)) $data['file_path'] = $file;
                if (!empty($trace)) $data['backtrace'] = $trace;
            }
            echo json_encode($data, JSON_PRETTY_PRINT);
        }
        ob_flush();
        exit;
    }

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @return void
     */
    public static function reportError($heading, $message)
    {
        if (!empty(self::$instance->bugsnag)) {
            self::$instance->bugsnag->notifyError($heading, $message);
        }
    }

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public static function reportException($exception)
    {
        if (!empty(self::$instance->bugsnag)) {
            self::$instance->bugsnag->notifyException($exception);
        }
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
