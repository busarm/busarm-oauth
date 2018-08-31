<?php

$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2'), TRUE))
    ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

define("PROTOCOL_HEADER", $server_protocol, true); //Server Protocol header

$base_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$base_url .= "://". @$_SERVER['HTTP_HOST'];
define("OAUTH_BASE_SERVER",$base_url);
$base_url .=     str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
$config['base_url'] = $base_url;

define("OAUTH_BASE_URL",$base_url);
define("ENV_DEV", "development", true);
define("ENV_PROD", "production", true);
define("ENV_TEST", "testing", true);

   
// fix cross site to option request error
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $headers = getallheaders();

    //Exit if option request contains authentication headers
    if(preg_match("/access-token|authorization|authentication|client-id|client-secret/",$headers[@"Access-Control-Request-Headers"])){
        header(PROTOCOL_HEADER." 200 OK", TRUE, 200);
        exit();
    }
}

/**
 *
 * Get Ip of users
 * @return string
 */
function get_ip_address() {

    // check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {

        // check if multiple ips exist in var
        if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false)
        {

            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            foreach ($iplist as $ip)
            {
                if ($this->validate_ip($ip))
                    return $ip;
            }

        }
        else
        {

            if ($this->validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

    }

    if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
        return $_SERVER['HTTP_X_FORWARDED'];

    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];

    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
        return $_SERVER['HTTP_FORWARDED_FOR'];

    if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
        return $_SERVER['HTTP_FORWARDED'];

    // return unreliable ip since all else failed
    return $_SERVER['REMOTE_ADDR'];

}


/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 * @param $ip
 * @return boolean
 */
function validate_ip($ip) {

    if (strtolower($ip) === 'unknown')
        return false;

    // generate ipv4 network address
    $ip = ip2long($ip);

    // if the ip is set and not equivalent to 255.255.255.255
    if ($ip !== false && $ip !== -1)
    {

        // make sure to get unsigned long representation of ip
        // due to discrepancies between 32 and 64 bit OSes and
        // signed numbers (ints default to signed in PHP)
        $ip = sprintf('%u', $ip);

        // do private network range checking
        if ($ip >= 0 && $ip <= 50331647) return false;
        if ($ip >= 167772160 && $ip <= 184549375) return false;
        if ($ip >= 2130706432 && $ip <= 2147483647) return false;
        if ($ip >= 2851995648 && $ip <= 2852061183) return false;
        if ($ip >= 2886729728 && $ip <= 2887778303) return false;
        if ($ip >= 3221225984 && $ip <= 3221226239) return false;
        if ($ip >= 3232235520 && $ip <= 3232301055) return false;
        if ($ip >= 4294967040) return false;
    }

    return true;
}


$ip = get_ip_address();

/*
 * Define user's IP Address as
 * to be viewed across the server
 *
 */

define('IPADDRESS', $ip);

/*
|
| Configure allowed IPs for development
| environment verification
|
*/

$dev_IPS =  array('::1','127.0.0.1',$_SERVER['SERVER_NAME']);

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

if (IPADDRESS == $_SERVER['SERVER_ADDR'] || in_array(IPADDRESS, $dev_IPS)) {
    define('ENVIRONMENT', ENV_DEV);
} else {
    define('ENVIRONMENT', ENV_PROD);
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
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        break;
    case ENV_TEST:
    case ENV_PROD:
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
        else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;

    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1); // EXIT_ERROR
}


define('OAUTH_APP_PATH','application/controllers/');
define('OAUTH_APP_PUBLIC_PATH',OAUTH_APP_PATH.'public/');
define('OAUTH_BASE_PATH','system/');
define('OAUTH_VIEW_PATH','application/views/');
$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2'), TRUE))
    ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

define("PROTOCOL_HEADER", $server_protocol); //Server Protocol header



/*Initiate rerouting*/
$request_path = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:null;
if (!isset($request_path))
{
    $request_path = isset($_SERVER['ORIG_PATH_INFO'])?$_SERVER['ORIG_PATH_INFO']:null;
}

if (isset($request_path)) {

    $routes = explode('/', $request_path);
    reroute($routes);
    
}
else {
    showError(404,"Invalid Request","Invalid Request Path");
}



/**Re-Routing
 *@param $routes
 */
function reroute($routes)
{

    $actualPath = "";
    $controller ="";
    $function ="";
    $params = null;
    $param_key = null;
    $param_count = 0;
    foreach($routes as $key => $route)
    {
        if(!empty(trim($route,"\n\r'\"\\/&%!@#$*)(|<>{}")))
        {

            if ($key == 0) //Route at section 0 = Controller
            {
                $controller = basename(trim(OAUTH_APP_PUBLIC_PATH.$route));
                $actualPath .= $controller;
                $actualPath.=".php";
            }
            else if ($key == 1) //Route at section 1 = Function
            {
                $function = $route;
            }
            else //Every other section. = Url params
            {
                $param_count++;

                if ($param_count%2==0)
                {
                    $params[$param_key] = $route;
                }
                else
                {
                    $param_key = $route;
                }
            }

            if ($key == count($routes)-1) //Last
            {
                if (isset($params)) {
                    $_GET = $params;
                }
                                   
                if ($realPath = fileExists(OAUTH_APP_PUBLIC_PATH.$actualPath,false))
                {

                    try {
                        
                        require_once $realPath;

                        if (class_exists(ucfirst($controller))) {
                            
                            /*
                             * Let's Go...
                             */
                            require_once('vendor/bshaffer/oauth2-server-php/src/OAuth2/Autoloader.php');
                            OAuth2\Autoloader::register();

                            /*Load Class*/
                            switch (strtolower($controller)) {
                                case 'token':
                                case 'authorize':
                                case 'resource':

                                    /*Create instance of controller*/
                                    $token = new $controller();

                                    /*Load function*/
                                    switch (strtolower($function)) {
                                        case 'get':
                                            $function = 'get_' . strtolower($controller);
                                            break;
                                        case 'post':
                                            $function = 'post_' . strtolower($controller);
                                            break;
                                        case 'put':
                                            $function = 'put_' . strtolower($controller);
                                            break;
                                        case 'delete':
                                            $function = 'delete_' . strtolower($controller);
                                            break;
                                        case 'patch':
                                            $function = 'patch_' . strtolower($controller);
                                            break;
                                    }

                                    if (method_exists($token, $function)
                                        && is_callable(array($token, $function))) {
                                        call_user_func(
                                            array($token, $function)
                                        );
                                    } else {
                                        showError(401, "Unknown Method", "Unknown Method - " . $function);
                                    }

                                    break;
                                default:
                                    showError(401, "Unauthorized Request", "Unauthorized Request - " . $controller);
                            }

                        } else {
                            showError(404, "Invalid Request", "Invalid Request - " . $controller);
                        }
                    } catch (Exception $e) {
                    }
                }
                else
                {
                    showError(404,"Invalid Request","Invalid Request - ".$controller);
                }

                break; //unnecessary but just in-case... Sh*t happens.. can't be too sure ;)
            }
        }
        else {
            unset($routes[$key]);
            $routes = array_values($routes);
            reroute($routes);
            break;
        }
    }

}



/**Case Insensitive search
 * @param $fileName string
 * @param $caseSensitive bool
 * @return mixed
 */
function fileExists($fileName, $caseSensitive = true) {

    if(file_exists($fileName)) {
        return $fileName;
    }
    if($caseSensitive) return false;

    // Handle case insensitive requests
    $directoryName = dirname($fileName);
    $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = strtolower($fileName);
    foreach($fileArray as $file) {
        if(strtolower($file) == $fileNameLowerCase) {
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
function showError($code,$title,$msg)
{
    header(PROTOCOL_HEADER.' '.$code.' '.$title, TRUE, $code);
    header("Content-type: text/html");

    echo
        "
            <!DOCTYPE html>
            <html>
            <head>
                <title>".$code." ".$title."</title>
            </head>
            <body>
                <h2 align='center'>".$msg."</h2>
                <h3 align='center'> Your IP : ".IPADDRESS."</h3>
                <h3 align='center'> ENVIRONMENT : ".ENVIRONMENT."</h3>
            </body>
            </html>
        ";
    exit($code);
}


/**Get File From path
 * @param $filePath
 * @return bool|string
 */
function get_file($filePath)
{
    $data = false;
    if (isset($filePath))
    {
        //$dl_file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).]|[\.]{2,})", '', $filePath); // simple file name validation
        $dl_file = filter_var($filePath, FILTER_SANITIZE_URL); // Remove (more) invalid characters
        $fullPath = $dl_file;

        if (file_exists($fullPath)) {

            try {

                $fd = @fopen($fullPath, "r");

            } finally {

                if ($fd)
                {
                    $file = @fread($fd,filesize($fullPath));
                    $data = $file;
                }

                fclose($fd);
            }
        }
    }

    return $data;
}


/**Load View
 * @param $path
 * @param bool $return
 * @return bool|null|string
 * @throws Exception
 */
function loadView($path,$return = false)
{
    if ($filePath = fileExists(OAUTH_VIEW_PATH.$path.".php"))
    {
        if ($file = get_file($filePath))
        {
            if ($return)
                return $file;
            else
            {
                echo $file;
            }
        }
        else
        {
            throw new Exception("Failed to load view");
        }
    }
    else
    {
        throw new Exception("File does not Exist");
    }

    return false;
}




