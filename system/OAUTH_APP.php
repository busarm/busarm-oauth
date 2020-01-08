<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');
require_once "OAUTH_APP_CONFIGS.php";

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

class OAUTH_APP {

    private $controllerDir = OAUTH_APP_CONFIGS::OAUTH_CONTROLLER_PATH;
    private $viewDir = OAUTH_APP_CONFIGS::OAUTH_VIEW_PATH;
    private $modelDir = OAUTH_APP_CONFIGS::OAUTH_MODEL_PATH;
    private $libraryDir = OAUTH_APP_CONFIGS::OAUTH_LIBRARY_PATH;

    private static $instance;

    public function __construct(){
        self::$instance = $this;
    }

    /**
     * Get App Instance
     *
     * @return OAUTH_APP
     */
    public static function getInstance(){
        return self::$instance;
    }

    /**
     * Initialize app
     */
    public function initialize ()
    {
        /*Initiate rerouting*/
        $request_path = getServer('REQUEST_URI');
        if (empty($request_path)) {
            $request_path = getServer('PATH_INFO');
        }
        if (empty($request_path)) {
            $request_path = getServer('ORIG_PATH_INFO');
        }
        if (!empty($request_path)) {
            //Remove base path map from request path if available e.g 'oauth' from staging.wecari.com/oauth
            if(ENVIRONMENT == ENV_TEST && !empty($base = getServer('BASE_PATH_MAP'))){
                if(strpos($request_path, $base."/") !== false){
                    $request_path = str_replace($base."/", "", $request_path);
                }
            }
            else if(ENVIRONMENT == ENV_DEV){
                $request_path = str_replace("wecari.com/oauth", "", $request_path);
            }
            $routes = explode('/', explode('?',$request_path)[0]);
            $this->reroute($routes);
        } else {
            $this->showError(404, "Invalid Request", "Invalid Request Path");
        } 
    }

    /**Re-Routing
     * @param $routes
     */
    private function reroute ($routes)
    {
        $actualPath = "";
        $controller = "";
        $function = "";
        $params = null;
        $param_key = null;
        $param_count = 0;

        foreach ($routes as $key => $route) {
            if (!empty(trim($route, "\n\r'\"\\/&%!@#$*)(|<>{}"))) {
                if ($key == 0) { //Route at section 0 = Controller
                    $controller = basename(trim($this->controllerDir . $route));
                    $actualPath .= $controller;
                    $actualPath .= ".php";
                } else if ($key == 1){ //Route at section 1 = Function
                    $function = $route;
                } else { //Every other section. = Url params
                    $param_count++;
                    if ($param_count % 2 == 0) {
                        $params[$param_key] = $route;
                    } else {
                        $param_key = $route;
                    }
                }
                if ($key == count($routes) - 1){  //Last
                    if (isset($params)) {
                        $_GET = $params;
                    }
                    if ($realPath = $this->fileExists($this->controllerDir . $actualPath, false)) {

                        try {

                            /*
                            * Let's Go...
                            */
                            require_once OAUTH_BASE_PATH . 'Server.php';
                            require_once $realPath;

                            if (class_exists(ucfirst($controller))) {

                                /*Load Class*/
                                /*Create instance of controller*/
                                $token = new $controller();

                                if (method_exists($token, $function)
                                    && is_callable(array($token, $function))) {
                                    call_user_func(
                                        array($token, $function)
                                    );
                                } else {
                                    $this-> showError(401, "Unknown Method", "Unknown Method - " . $function);
                                }
                            } else {
                                $this->showError(404, "Invalid Request", "Invalid request path - " . $controller);
                            }
                        } catch (Exception $e) {
                            $this->showError(500, "Invalid Request", $e->getMessage());
                        }
                    } else {
                        $this->showError(404, "Invalid Request", "Request path not found - " . $controller);
                    }
                    break; //unnecessary but just in-case...  can't be too sure ;)
                }
            } else {
                unset($routes[$key]);
                $routes = array_values($routes);
                $this->reroute($routes);
                break;
            }
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


    /**Show Error
     * @param $code string Error Code
     * @param $title string Error Title
     * @param $msg string Error Message
     */
    public function showError($code, $title, $msg)
    {
        header(PROTOCOL_HEADER . ' ' . $code . ' ' . $title, TRUE, $code);
        header("Content-type: application/json");
        echo json_encode(['status'=>false, 'error' => $title, 'error_description' => $msg, 'env' => ENVIRONMENT, 'env' => ENVIRONMENT]);
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
            ob_flush();

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
    public function generate_csrf_token()
    {
        $dateObj = new DateTime("now", new DateTimeZone("GMT"));
        $unique = md5(uniqid(IPADDRESS));
        $this->set_cookie("csrf_key",$unique);
        $csrf_token = md5($unique. IPADDRESS. OAUTH_BASE_URL . $dateObj->format('Y-m-d H'));
        return $csrf_token;
    }


    /**Get CSRF TOKEN
     * @return string
     */
    public function get_csrf_token()
    {
        if(!empty($unique = $this->get_cookie("csrf_key"))){
            $dateObj = new DateTime("now", new DateTimeZone("GMT"));
            $csrf_token = md5($unique. IPADDRESS. OAUTH_BASE_URL . $dateObj->format('Y-m-d H'));
            $this->delete_cookie("csrf_key");
            return $csrf_token;
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
    public function get_cookie($name){
        return !empty($_COOKIE["oauth_".$name])? @$_COOKIE["oauth_".$name] : null;
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

}