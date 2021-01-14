<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');
require_once "Configs.php";

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 10/14/2018
 * Time: 12:34 AM
 *
 * Manage Content Delivery for Wecari
 *
 * @copyright wecari.com
 */

class App {

    private $controllerDir = Configs::OAUTH_CONTROLLER_PATH;
    private $viewDir = Configs::OAUTH_VIEW_PATH;
    private $modelDir = Configs::OAUTH_MODEL_PATH;
    private $libraryDir = Configs::OAUTH_LIBRARY_PATH;

    private static $instance;
    
    public $bugsnag;

    public function __construct(){
        self::$instance = $this;
        if($key = Configs::BUGSNAG_KEY()){
            $this->bugsnag = Bugsnag\Client::make($key);
            Bugsnag\Handler::register($this->bugsnag);
        }
    }

    /**
     * Get App Instance
     *
     * @return App
     */
    public static function getInstance(){
        return self::$instance;
    }

    /**
     * Get PATH for APP
     *
     * @param string $path
     * @return void
     */
    public static function get_app_path($path = ''){
        if (ENVIRONMENT == ENV_PROD)
            return "https://wecari.com/".$path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://staging.wecari.com/".$path;
        else
            return "http://localhost/".$path;
    }

    /**
     * Get PATH for CDN
     *
     * @param string $path
     * @return void
     */
    public static function get_cdn_path($path = ''){
        if (ENVIRONMENT == ENV_PROD)
            return "https://cdn.wecari.com/".$path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://cdn.staging.wecari.com/".$path;
        else
            return "https://cdn.staging.wecari.com/".$path;
    }

    /**
     * Initialize app
     */
    public function initialize ($controller = null, $function = null, $params = [])
    {
        /*Preflight Checking*/
        if(!is_cli()){
            $this->preflight();
        }

        /*Initiate rerouting*/
        if($controller && $function) {
            $this->processRoute($controller, $function, $params);
        }
        else {
               
            $request_path = getServer('PATH_INFO');
            if (empty($request_path)) {
                $request_path = getServer('ORIG_PATH_INFO');
            }
            if (empty($request_path)) {
                $request_path = getServer('REQUEST_URI');
            }
            
            if (!empty($request_path)) {
                if(preg_match('/\/ping(\/)?$/',$request_path)){
                    $this->showMessage(200, true, "System Online", ENVIRONMENT);
                }
                else { 
                    $routes = explode('/', explode('?',$request_path)[0]);
                    if(!empty($routes)){
                        $this->reroute($routes);
                    }
                    else {
                        $this->showMessage(404, false, "Invalid Request", "Invalid Request Path");
                    }
                }
            }
            else {
                $this->showMessage(404, false, "Invalid Request", "Invalid Request Path");
            }
        } 
    }

    /**
     * Preflight Check
     *
     * @return void
     */
    private function preflight(){
        // Check for CORS access request
        if (Configs::CHECK_CORS == TRUE) {
            $this->check_cors();
        } else { 
            if (strtolower(getServer("REQUEST_METHOD")) === 'options') {
                // kill the response and send it to the client
                $this->showMessage(200, true, "Preflight Ok", ENVIRONMENT);
            }
        }
    }


    /**Re-Routing
     * @param $routes
     */
    private function reroute ($routes)
    {
        $controller = "";
        $function = "";
        $params = [];
        foreach ($routes as $key => $route) {
            if (!empty(trim($route, "\n\r'\"\\/&%!@#$*)(|<>{} "))) {
                if ($key == 0) { //Route at section 0 = Controller
                    $controller = basename(trim($this->controllerDir . $route));
                } else if ($key == 1){ //Route at section 1 = Function
                    $function = $route;
                } else { //Every other section. = Url params
                    $params[] = $route;
                }
            } else {
                unset($routes[$key]);
                $routes = array_values($routes);
                return $this->reroute($routes);
            }
        }
        
        if ($controller && $function ){  //Last
            $this->processRoute($controller, $function, $params);
        }
        else {
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
    private function processRoute($controller, $function, $params = []){
        $path = $controller.".php";
        if ($realPath = $this->fileExists($this->controllerDir . $path, false)) {
            try {
                /*
                * Let's Go...
                */
                require_once OAUTH_BASE_PATH . 'Server.php';
                require_once $realPath;

                if (class_exists(ucfirst($controller))) {

                    /*Load Class*/
                    /*Create instance of class*/
                    $object = new $controller();

                    if (method_exists($object, $function)
                        && is_callable(array($object, $function))) {
                        call_user_func_array(
                            array($object, $function),
                            $params
                        );
                    } else {
                        $this->showMessage(400, false, "Unknown Method", "Unknown Method - " . $function);
                    }
                } else {
                    $this->showMessage(400, false, "Invalid Request", "Invalid request path - " . $controller);
                }
            } catch (Throwable $e) {
                if($this->bugsnag){
                    $this->bugsnag->notifyException($e);
                }
                $this->showMessage(400, false, "Invalid Request", $e->getMessage());
            }
        } else {
            $this->showMessage(404, false, "Invalid Request", "Request path not found - " . $controller);
        }
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
    public function showMessage($code, $status, $title, $msg)
    {
        if(!is_cli() && !headers_sent()){
            header(PROTOCOL_HEADER . ' ' . $code . ' ' . $title, TRUE, $code);
            header("Content-type: application/json");
            header('Access-Control-Allow-Origin: *', true);
            header('Access-Control-Allow-Methods: *', true); 
        }
        if($status){
            echo json_encode(['status'=>true, 'msg' => $title, 'env' => ENVIRONMENT, 'ip' => IPADDRESS]);
        }
        else {
            echo json_encode(['status'=>false, 'error' => $title, 'error_description' => $msg, 'env' => ENVIRONMENT, 'ip' => IPADDRESS]);
        }
        exit;
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
        if ($filePath = $this->fileExists($this->viewDir . $path . ".php")) {
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
        if ($filePath = $this->fileExists($this->libraryDir . $path . ".php")) {
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
        if ($filePath = $this->fileExists($this->modelDir . $path . ".php")) {
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
        if(empty($key) && empty($key = $this->get_cookie("csrf_key"))){
            $key = md5(uniqid(IPADDRESS));
            $this->set_cookie("csrf_key",$key);
        }
        $dateObj = new DateTime("now", new DateTimeZone("GMT"));
        $csrf_token = sha1(sprintf("%s:%s:%s",$key, IPADDRESS, $dateObj->format('Y-m-d H')));
        return $csrf_token;
    }


    /**Get CSRF TOKEN
     * @return string
     */
    public function get_csrf_token()
    {
        if(!empty($key = $this->get_cookie("csrf_key"))){
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
            $done = $csrf_token == $this->get_csrf_token();
            if($done){
                $this->delete_cookie("csrf_key");
            }
            return $done;
        }
        return false;
    }

    /**
     * Get cookie
    * @param String $name
    * @return void
    */
    public function get_cookie($name){
        return !empty($_COOKIE["oauth_".$name])? @$_COOKIE["oauth_".$name] : null;
    }

    /**
     * Pull cookie - Get and delete cooke
    * @param String $name
    * @return void
    */
    public function pull_cookie($name){
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
    public function set_cookie($name, $value, $duration = 3600){
        return setcookie("oauth_".$name, $value, time() + $duration, "/");
    }

    /**
     * Delete cookie
     * @param String $name
     * @return bool
     */
    public function delete_cookie($name){
        return setcookie("oauth_".$name, "", time() - 3600);
    }



    /**
     * Check to see if the API key has access to the controller and methods
     *
     * @access protected
     * @return bool TRUE the API key has access; otherwise, FALSE
     */
    protected function _check_access()
    {
        // If we don't want to check access, just return TRUE
        if ($this->config->item('rest_enable_access') === FALSE) {
            return TRUE;
        }

        //check if the key has all_access
        $accessRow = $this->rest->db
            ->where('key', $this->rest->key)
            ->get($this->config->item('rest_access_table'))->row_array();

        if (!empty($accessRow) && !empty($accessRow['all_access'])) {
            return TRUE;
        }

        // Fetch controller based on path and controller name
        $controller = implode(
            '/', [
            $this->router->directory,
            $this->router->class
        ]);

        // Remove any double slashes for safety
        $controller = str_replace('//', '/', $controller);

        // Query the access table and get the number of results
        return $this->rest->db
                ->where('key', $this->rest->key)
                ->where('controller', $controller)
                ->get($this->config->item('rest_access_table'))
                ->num_rows() > 0;
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
            header(PROTOCOL_HEADER . " 200 OK", TRUE, 200);
            header('Access-Control-Allow-Origin: *', true);
            header('Access-Control-Allow-Methods: ' . $allowed_methods, true);
            header('Access-Control-Allow-Headers: ' . $allowed_headers, true);
            header('Access-Control-Expose-Headers: ' . $exposed_cors_headers, true);
            header('Access-Control-Max-Age: ' . $max_cors_age, true);
        } else {

            // We're going to allow only certain domains access
            // Store the HTTP Origin header
            $origin = getServer('HTTP_ORIGIN');
            if ($origin === NULL) {
                $origin = getServer('HTTP_REFERER');
                if ($origin === NULL) {
                    $origin = '';
                }
            }

            $allowed_origins = Configs::ALLOWED_CORS_ORIGINS;

            // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
            if (is_array($allowed_origins) && in_array(trim($origin, "/"), $allowed_origins)) {
                header(PROTOCOL_HEADER . " 200 OK", true, 200);
                header('Access-Control-Allow-Origin: ' . $origin, true);
                header('Access-Control-Allow-Methods: ' . $allowed_methods, true);
                header('Access-Control-Allow-Headers: ' . $allowed_headers, true);
                header('Access-Control-Expose-Headers: ' . $exposed_cors_headers, true);
                header('Access-Control-Max-Age: ' . $max_cors_age, true);
            }
        }

        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if (strtolower(getServer("REQUEST_METHOD")) === 'options') {
            die();
        }
    }
}