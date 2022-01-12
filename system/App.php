<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');
require_once "Helpers.php";
require_once "Configs.php";
require_once "CIPHER.php";
require_once "Scopes.php";

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 10/14/2018
 * Time: 12:34 AM
 *
 * Mini Framework for Wecari
 *
 * @copyright wecari.com
 */

class App
{

    private $controllerDir = Configs::OAUTH_CONTROLLER_PATH;
    private $viewDir = Configs::OAUTH_VIEW_PATH;
    private $modelDir = Configs::OAUTH_MODEL_PATH;
    private $libraryDir = Configs::OAUTH_LIBRARY_PATH;

    private static $instance;

    /** @var \Bugsnag\Client */
    public $bugsnag;

    public function __construct()
    {
        self::$instance =& $this;

        if ($key = Configs::BUGSNAG_KEY()) {
            $this->bugsnag = Bugsnag\Client::make($key);
            $this->bugsnag->setReleaseStage(ENVIRONMENT);
            $this->bugsnag->setAppType(is_cli() ? "Console" : "HTTP");
            Bugsnag\Handler::register($this->bugsnag);
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
     * Get PATH for APP
     *
     * @param string $path
     * @return void
     */
    public static function getAppUrl($path = '')
    {
        if (ENVIRONMENT == ENV_PROD)
            return "https://wecari.com/" . $path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://staging.wecari.com/" . $path;
        else
            return "http://localhost/" . $path;
    }

    /**
     * Get PATH for CDN
     *
     * @param string $path
     * @return void
     */
    public static function getCDNUrl($path = '')
    {
        if (ENVIRONMENT == ENV_PROD)
            return "https://cdn.wecari.com/" . $path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://cdn.staging.wecari.com/" . $path;
        else
            return "https://cdn.staging.wecari.com/" . $path;
    }

    /**
     * Initialize app
     */
    public function initialize($controller = null, $function = null, $params = [])
    {
        /*Preflight Checking*/
        if (!is_cli()) {
            $this->preflight();
        }

        /*Initiate rerouting*/
        if ($controller && $function) {
            $this->processRoute($controller, $function, $params);
        } else {

            $request_path = env('PATH_INFO');
            if (empty($request_path)) {
                $request_path = env('ORIG_PATH_INFO');
            }
            if (empty($request_path)) {
                $request_path = env('REQUEST_URI');
            }
            $request_path = urldecode($request_path);

            if (!empty($request_path)) {
                if (preg_match('/\/ping(\/)?$/', $request_path)) {
                    $this->showMessage(200, true, "System Online");
                } else {
                    $routes = explode('/', explode('?', $request_path)[0]);
                    if (!empty($routes)) {
                        $this->reroute($routes);
                    } else {
                        $this->showMessage(404, false, "Invalid Request", "Invalid Request Path");
                    }
                }
            } else {
                $this->showMessage(404, false, "Invalid Request", "Invalid Request Path");
            }
        }
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight()
    {
        // Check for CORS access request
        if (Configs::CHECK_CORS == TRUE) {
            $this->check_cors();
        } else {
            if (strtolower(env("REQUEST_METHOD")) === 'options') {
                // kill the response and send it to the client
                $this->showMessage(200, true, "Preflight Ok");
            }
        }
    }


    /**
     * Re-Routing -  Inspired by Codeigniter
     * @param array $routes
     */
    private function reroute($routes)
    {
        $routes = array_values(array_filter($routes, function ($route) {
            if (!empty(trim($route, "\n\r'\"\\/&%!@#$*)(|<>{} "))) return $route;
        }));
        $controller = isset($routes[0]) ? $routes[0] : null; // First path = controller
        $function = isset($routes[1]) ? $routes[1] : null; // Second path = function (public)
        $params = isset($routes[2]) ? array_slice($routes, 2, count($routes)) : []; // Subsequent paths = function parameters

        // Process route if available
        if ($controller && $function) {
            $this->processRoute(trim($controller), trim($function), $params);
        } else {
            $this->showMessage(404, false, "Invalid Request", "Invalid Request Path");
        }
    }

    /**
     * Process Route
     *
     * @param string $controller
     * @param string $function
     * @param string $params
     * @return void
     */
    private function processRoute($controller, $function, $params = [])
    {
        if ($realPath = $this->fileExists(FCPATH . $this->controllerDir . $controller . ".php", false)) {
            /*
            * Let's Go...
            */
            require_once OAUTH_BASE_PATH . 'Server.php';
            require_once $realPath;
            if (class_exists(ucfirst($controller))) {
                /*Load Class*/
                /*Create instance of class*/
                $object = new $controller();
                if (
                    method_exists($object, $function)
                    && is_callable(array($object, $function))
                ) {
                    call_user_func_array(
                        array($object, $function),
                        $params
                    );
                }
                return  $this->showMessage(400, false, "unknown_method", "Unknown Method - " . $function);
            }
            return $this->showMessage(400, false, "invalid_request", "Invalid request path - " . $controller);
        }
        return $this->showMessage(404, false, "invalid_request", "Request path not found - " . $controller);
    }


    /**Case Insensitive search
     * @param $fileName string
     * @param $caseSensitive bool
     * @return mixed
     */
    private function fileExists($fileName, $caseSensitive = true)
    {

        if (file_exists($fileName)) {
            return $fileName;
        }
        if ($caseSensitive) return false;

        // Handle case insensitive requests
        $directoryName = dirname($fileName);
        $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower($fileName);
        foreach ($fileArray as $file) {
            if (strtolower($file) == $fileNameLowerCase) {
                return $file;
            }
        }
        return false;
    }


    /**
     * Show Message
     * @param string $code Code
     * @param bool $status Status
     * @param string $title itle
     * @param string $msg Message
     */
    public function showMessage($code, $status, $title, $msg = null, $errorLine = null, $errorFile = null, $errorContext = [])
    {
        if (!is_cli() && !headers_sent()) {
            header(HTTP_VERSION . ' ' . $code . ' ' . $title, TRUE, $code);
            header("Content-type: application/json");
            header('Access-Control-Allow-Origin: *', true);
            header('Access-Control-Allow-Methods: *', true);
        }
        if ($status) {
            if (is_cli()) {
                echo "success - true" . PHP_EOL . "message - " . $msg ?? $title;
            } else {
                echo json_encode(['success' => true, 'msg' => $title, 'env' => ENVIRONMENT, 'ip' => IPADDRESS], JSON_PRETTY_PRINT);
            }
            exit(1);
        } else {
            if (is_cli()) {
                echo "success - false" . PHP_EOL . "message - " . ($msg ?? $title) . PHP_EOL . "line - $errorLine" . PHP_EOL . "file path - $errorFile" . PHP_EOL;
            } else if (ENVIRONMENT != ENV_PROD) {
                echo json_encode(['success' => false, 'error' => $title, 'error_description' => $msg, 'env' => ENVIRONMENT, 'ip' => IPADDRESS,  'line' =>  $errorLine,  'file_path' =>  $errorFile,  'backtrace' =>  $errorContext], JSON_PRETTY_PRINT);
            } else {
                echo json_encode(['success' => false, 'error' => $title, 'error_description' => $msg, 'env' => ENVIRONMENT, 'ip' => IPADDRESS], JSON_PRETTY_PRINT);
            }
            exit;
        }
    }


    /**Get File From path
     * @param $filePath
     * @return bool|string
     */
    public function get_file($filePath)
    {
        $data = false;
        if (isset($filePath)) {
            //$dl_file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).]|[\.]{2,})", '', $filePath); // simple file name validation
            $dl_file = filter_var($filePath, FILTER_SANITIZE_URL); // Remove (more) invalid characters
            $fullPath = $dl_file;

            if (file_exists($fullPath)) {
                try {
                    $fd = @fopen($fullPath, "r");
                } finally {
                    if ($fd) {
                        $file = @fread($fd, filesize($fullPath));
                        $data = $file;
                        fclose($fd);
                    }
                }
            }
        }

        return $data;
    }


    /**Load View
     * @param $path
     * @param array $vars
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function loadView($path, $vars = array(), $return = false)
    {
        if ($filePath = $this->fileExists(FCPATH . $this->viewDir . $path . ".php")) {
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
        if ($filePath = $this->fileExists(FCPATH . $this->libraryDir . $path . ".php")) {
            if (!empty($vars))
                extract($vars);
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
        if ($filePath = $this->fileExists(FCPATH . $this->modelDir . $path . ".php")) {
            if (!empty($vars))
                extract($vars);
            require_once $filePath;
        } else {
            throw new Exception("File does not Exist");
        }
    }

    /**Generate CSRF TOKEN
     * @return string
     */
    public function generate_csrf_token($key = null)
    {
        if (empty($key) && empty($key = $this->get_cookie("csrf_key"))) {
            $key = md5(uniqid(IPADDRESS));
            $this->set_cookie("csrf_key", $key);
        }
        $dateObj = new DateTime("now", new DateTimeZone("GMT"));
        $csrf_token = sha1(sprintf("%s:%s:%s", $key, IPADDRESS, $dateObj->format('Y-m-d H')));
        return $csrf_token;
    }


    /**Get CSRF TOKEN
     * @return string
     */
    public function get_csrf_token()
    {
        if (!empty($key = $this->get_cookie("csrf_key"))) {
            return $this->generate_csrf_token($key);
        }
        return null;
    }

    /**CSRF Validation
     * @param string $csrf_token
     * @return array|boolean
     */
    public function validate_csrf_token($csrf_token)
    {
        if ($csrf_token) {
            return $csrf_token == $this->get_csrf_token();
        }
        return false;
    }

    /**
     * Get cookie
     * @param String $name
     * @return void
     */
    public function get_cookie($name)
    {
        return !empty($_COOKIE["oauth_" . $name]) ? @$_COOKIE["oauth_" . $name] : null;
    }

    /**
     * Pull cookie - Get and delete cooke
     * @param String $name
     * @return void
     */
    public function pull_cookie($name)
    {
        $value = $this->get_cookie($name);
        if ($value)
            $this->delete_cookie($name);
        return $value;
    }

    /**
     * Set cookie
     *
     * @param [type] $name
     * @param [type] $value
     * @param integer $duration
     * @return bool
     */
    public function set_cookie($name, $value, $duration = 3600)
    {
        return setcookie("oauth_" . $name, $value, time() + $duration, "/");
    }

    /**
     * Delete cookie
     * @param String $name
     * @return bool
     */
    public function delete_cookie($name)
    {
        return $this->set_cookie($name, "", -3600);
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
     * Start Login session
     *
     * @param string $user User Id
     * @param string $duration Session duration in seconds. default = 1hr
     * @return void
     */
    public function startLoginSession($user, $duration = 3600)
    {
        if (!$user) return;
        $encryptedUser = CIPHER::encrypt(Configs::ENCRYPTION_KEY() . md5(IPADDRESS), $user);
        $this->set_cookie('login_user', $encryptedUser, $duration);
    }

    /**
     * Clear Login session
     * 
     * @return void
     */
    public function clearLoginSession()
    {
        $this->delete_cookie('login_user');
    }

    /**
     * Get Login User
     *
     * @return string|bool
     */
    public function getLoginUser()
    {
        $encryptedUser = $this->get_cookie('login_user');
        if ($encryptedUser) {
            return CIPHER::decrypt(Configs::ENCRYPTION_KEY() . md5(IPADDRESS), $encryptedUser);
        }
        return false;
    }

    /**
     * Header Redirect
     *	 *
     * @param	string	$uri	URL
     * @param	string	$method	Redirect method
     *			'auto', 'location' or 'refresh'
     * @param	int	$code	HTTP Response status code
     * @return	void
     */
    public function redirect($uri, $method = 'auto', $code = NULL)
    {
        if (!preg_match('#^(\w+:)?//#i', $uri)) {
            $uri = trim(OAUTH_BASE_URL, '/') . '/' . $uri;
        }

        // IIS environment likely? Use 'refresh' for better compatibility
        if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== FALSE) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && (empty($code) or !is_numeric($code))) {
            if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                $code = ($_SERVER['REQUEST_METHOD'] !== 'GET')
                    ? 303    // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
                    : 307;
            } else {
                $code = 302;
            }
        }

        switch ($method) {
            case 'refresh':
                header('Refresh:0;url=' . $uri);
                break;
            default:
                header('Location: ' . $uri, TRUE, $code);
                break;
        }
        exit;
    }

    /**
     * @param array $params
     * @param string $parent
     * @return string
     */
    public static function buildUrlParams($params, $parent = null)
    {
        $query = '';
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                $query .= $parent ? self::buildUrlParams($param, $parent . "[$key]") : self::buildUrlParams($param, $key);
            } else {
                $query .= ($parent ? urlencode($parent . "[$key]") . "=$param&" : "$key=$param&");
            }
        }
        return trim($query, '&');
    }

    /**
     * @param string $url
     * @param array $params
     * @param bool $override Overide URL query with given params if duplicate found
     * @return string
     */
    public static function parseUrl($url, $params = [], $override = false)
    {
        if (!empty($params)) {
            parse_str(parse_url($url,  PHP_URL_QUERY), $query);

            // Defining a callback function
            $callback = function ($var) {
                return ($var !== NULL && $var !== FALSE && $var !== "");
            };

            if ($override) {
                $params = array_merge(array_filter($query, $callback), array_filter($params, $callback));
            } else {
                $params = array_merge(array_filter($params, $callback), array_filter($query, $callback));
            }

            if (isset($params['ajax'])) {
                unset($params['ajax']);
            }
            if (isset($params['pjax'])) {
                unset($params['pjax']);
            }

            $url = explode('?', $url)[0];
            if (!empty($params)) {
                $url .= '?' . ((function_exists('http_build_query')) ? http_build_query($params) : self::buildUrlParams($params));
            }
        }
        return $url;
    }
    
    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @return void
     */
    public static function reportError($heading, $message){
        if(!empty(self::getInstance()->bugsnag)){
			self::getInstance()->bugsnag->notifyError($heading, $message);
        }
    }
    
    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public static function reportException($exception){
        if(!empty(self::getInstance()->bugsnag)){
			self::getInstance()->bugsnag->notifyException($exception);
        }
    }
    
    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param \Throwable $exception
     * @param string $type @see \Bugsnag\Breadcrumbs\Breadcrumb::getTypes
     * @return void
     */
    public static function leaveBreadcrumbs($crumb, $type = null, array $metadata = []){
        if(!empty(self::getInstance()->bugsnag)){
			self::getInstance()->bugsnag->leaveBreadcrumb($crumb, $type, $metadata);
        }
    }
}
