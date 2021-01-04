<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

if (!function_exists('is_cli'))
{
	/**
	 * Is CLI?
	 *
	 * Test to see if a request was made from the command line.
	 *
	 * @return 	bool
	 */
	function is_cli()
	{
		return (PHP_SAPI === 'cli' OR defined('STDIN'));
	}
}

/**
 * Get Server Variable
 *
 * @param [type] $name
 * @param [type] $default
 * @return void
 */
function getServer($name, $default = null){
    return (!empty($data = @getenv($name))?$data:$default);
}

/**Check if https enabled*/
function is_https(){
    if (!empty(getServer('HTTPS')) && strtolower(getServer('HTTPS')) !== 'off') {
        return TRUE;
    } elseif (!empty(getServer('HTTP_X_FORWARDED_PROTO')) && strtolower(getServer('HTTP_X_FORWARDED_PROTO')) === 'https') {
        return TRUE;
    } elseif (!empty(getServer('HTTP_FRONT_END_HTTPS')) && strtolower(getServer('HTTP_FRONT_END_HTTPS')) !== 'off') {
        return TRUE;
    }
    return FALSE;
}


$server_protocol = (!empty(getServer('SERVER_PROTOCOL')) && in_array(getServer('SERVER_PROTOCOL'), array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2'), TRUE))
    ? getServer('SERVER_PROTOCOL') : 'HTTP/1.1';

define("PROTOCOL_HEADER", $server_protocol, true); //Server Protocol header

$base_url = (is_https() ? "https" : "http") . "://";
define("OAUTH_BASE_SCHEME", $base_url);
$base_url .= getServer('HTTP_HOST');
define("OAUTH_BASE_SERVER", $base_url);
$base_url .= str_replace(basename(getServer('SCRIPT_NAME')), "", getServer('SCRIPT_NAME'));
$config['base_url'] = $base_url;
define("OAUTH_BASE_URL", $base_url);
define("OAUTH_CURRENT_URL", OAUTH_BASE_SERVER . getServer('REQUEST_URI'));

define("ENV_DEV", "development", true);
define("ENV_PROD", "production", true);
define("ENV_TEST", "testing", true);

// fix cross site to option request error
if (getServer('REQUEST_METHOD') == 'OPTIONS') {
    header(PROTOCOL_HEADER . " 200 OK", TRUE, 200);
    exit();
}

/**
 * Get Ip of users
 *
 */
function get_ip_address()
{
    // check for shared internet/ISP IP
    if (!empty(getServer('HTTP_CLIENT_IP')) && validate_ip(getServer('HTTP_CLIENT_IP'))) {
        return getServer('HTTP_CLIENT_IP');
    }
    // check for IPs passing through proxies
    if (!empty(getServer('HTTP_X_FORWARDED_FOR'))) {
        // check if multiple ips exist in var
        if (strpos(getServer('HTTP_X_FORWARDED_FOR'), ',') !== false) {
            $iplist = explode(',', getServer('HTTP_X_FORWARDED_FOR'));
            foreach ($iplist as $ip) {
                if (validate_ip($ip))
                    return $ip;
            }
        } else {
            if (validate_ip(getServer('HTTP_X_FORWARDED_FOR')))
                return getServer('HTTP_X_FORWARDED_FOR');
        }
    }
    if (!empty(getServer('HTTP_X_FORWARDED')) && validate_ip(getServer('HTTP_X_FORWARDED')))
        return getServer('HTTP_X_FORWARDED');

    if (!empty(getServer('HTTP_X_CLUSTER_CLIENT_IP')) && validate_ip(getServer('HTTP_X_CLUSTER_CLIENT_IP')))
        return getServer('HTTP_X_CLUSTER_CLIENT_IP');

    if (!empty(getServer('HTTP_FORWARDED_FOR')) && validate_ip(getServer('HTTP_FORWARDED_FOR')))
        return getServer('HTTP_FORWARDED_FOR');

    if (!empty(getServer('HTTP_FORWARDED')) && validate_ip(getServer('HTTP_FORWARDED')))
        return getServer('HTTP_FORWARDED');

    // return unreliable ip since all else failed
    return getServer('REMOTE_ADDR');
}


/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 * @param $ip
 * @return bool
 */
function validate_ip($ip)
{
    if (strtolower($ip) === 'unknown')
        return false;
    // generate ipv4 network address
    $ip = ip2long($ip);

    // if the ip is set and not equivalent to 255.255.255.255
    if ($ip !== false && $ip !== -1) {

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


/*
* Define user's IP Address as
* to be viewed across the server
*
*/

define('IPADDRESS', get_ip_address());

/*
* Define Server Local Ip
*/
define('LOCALHOST', getHostByName(getHostName()));


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

if (strtolower(getServer('ENV')) == "prod" || strtolower(getServer('STAGE')) == "prod") {
    define('ENVIRONMENT', ENV_PROD);
}
else if (strtolower(getServer('ENV')) == "dev" || strtolower(getServer('STAGE')) == "dev") {
    define('ENVIRONMENT', ENV_TEST);
} 
else {
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

class Configs
{ 
    const OAUTH_CONTROLLER_PATH =  "application/controllers/";
    const OAUTH_LIBRARY_PATH =  "application/library/";
    const OAUTH_VIEW_PATH =  "application/views/";
    const OAUTH_MODEL_PATH =  "application/model/";
    
    /*
    |--------------------------------------------------------------------------
    | CORS Check
    |--------------------------------------------------------------------------
    |
    | Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
    | are hosting your API on a different domain from the application that
    | will access it through a browser
    |
    */
    const CHECK_CORS = TRUE; 
    
    /*
    |--------------------------------------------------------------------------
    | CORS Max Age
    |--------------------------------------------------------------------------
    |
    | How long in seconds to cache CORS preflight response in browser.
    | -1 for disabling caching.
    |
    */
    const MAX_CORS_AGE = 3600; 

    /*
    |--------------------------------------------------------------------------
    | CORS Allowable Headers
    |--------------------------------------------------------------------------
    |
    | If using CORS checks, set the allowable headers here
    |
    */
    const ALLOWED_CORS_HEADERS = [
        'Authorization',
        'Origin',
        'Referer',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Access-Token',
        'Session-Token',
        'X-Session-Token',
        'X-Access-Token',
        'X-Encrypted',
        'X-Integrity',
        'X-Api-Key',
    ];


    /*
    |--------------------------------------------------------------------------
    | CORS Exposed Headers
    |--------------------------------------------------------------------------
    |
    | If using CORS checks, set the headers permitted to be sent to client here
    |
    */
    const EXPOSED_CORS_HEADERS = [];

    

    /*
    |--------------------------------------------------------------------------
    | CORS Allowable Methods
    |--------------------------------------------------------------------------
    |
    | If using CORS checks, you can set the methods you want to be allowed
    |
    */
    const ALLOWED_CORS_METHODS = [
        'GET',
        'POST',
        'OPTIONS',
        'PUT',
        'PATCH',
        'DELETE'
    ];

    /*
    |--------------------------------------------------------------------------
    | CORS Allow Any Domain
    |--------------------------------------------------------------------------
    |
    | Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
    | source domain
    |
    */
    const ALLOWED_ANY_CORS_DOMAIN = ENVIRONMENT != ENV_PROD;

    /*
    |--------------------------------------------------------------------------
    | CORS Allowable Domains
    |--------------------------------------------------------------------------
    |
    | Used if $config['check_cors'] is set to TRUE and $config['allow_any_cors_domain']
    | is set to FALSE. Set all the allowable domains within the array
    |
    | e.g. $config['allowed_origins'] = ['http://www.example.com', 'https://spa.example.com']
    |
    */
    const ALLOWED_CORS_ORIGINS = [
        'https://wecari.com',
        'https://wecari.com/',
        'https://staging.wecari.com',
        'https://staging.wecari.com/',
        'https://api.wecari.com',
        'https://api.wecari.com/',
        'https://api.staging.wecari.com',
        'https://api.staging.wecari.com/',
        'https://partner.wecari.com',
        'https://partner.wecari.com/',
        'https://partner.staging.wecari.com',
        'https://partner.staging.wecari.com/'
    ];


    static function DB_HOST(){
        return getServer("DB_HOST");
    }
    static function DB_PORT(){
        return intval(getServer("DB_PORT"));
    }
    static function DB_USER(){
        return getServer("DB_USER");
    }
    static function DB_PASS(){
        return getServer("DB_PASS");
    }

    static function STAGE_DB_HOST(){
        return getServer("STAGE_DB_HOST");
    }
    static function STAGE_DB_PORT(){
        return intval(getServer("STAGE_DB_PORT"));
    }
    static function STAGE_DB_USER(){
        return getServer("STAGE_DB_USER");
    }
    static function STAGE_DB_PASS(){
        return getServer("STAGE_DB_PASS");
    }

    static function AWS_SMTP_HOST(){
        return getServer("AWS_SMTP_HOST", "");
    }
    static function AWS_SMTP_PORT(){
        return intval(getServer("AWS_SMTP_PORT", ""));
    }
    static function AWS_SMTP_KEY(){
        return getServer("AWS_SMTP_KEY", "");
    }
    static function AWS_SMTP_SECRET(){
        return getServer("AWS_SMTP_SECRET", "");
    }
    
}