<?php
defined('OAUTH_BASE_PATH') or exit('No direct script access allowed');

define("ENV_DEV", "development");
define("ENV_PROD", "production");
define("ENV_TEST", "testing");

// Define user's IP Address as to be viewed across the server
define('IPADDRESS', get_ip_address());

// Define Server Local Ip
define('LOCALHOST', getHostByName(getHostName()));

// Define base variables
define("OAUTH_BASE_SCHEME", (is_https() ? "https" : "http") . "://");
define("OAUTH_BASE_SERVER", OAUTH_BASE_SCHEME . env('HTTP_HOST'));
define("OAUTH_BASE_URL", OAUTH_BASE_SERVER . str_replace(basename(env('SCRIPT_NAME')), "", env('SCRIPT_NAME')));
define("OAUTH_CURRENT_URL", OAUTH_BASE_SERVER . env('REQUEST_URI'));

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

/**
 * Define Configuration
 */
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

    static function APP_VERSION($default = '0.1')
    {
        return env("APP_VERSION", $default);
    }
    static function APP_NAME()
    {
        return env("APP_NAME");
    }
    static function COMPANY_NAME()
    {
        return env("COMPANY_NAME");
    }
    
    # App Settings
    static function ENCRYPTION_KEY($default = null)
    {
        return env("ENCRYPTION_KEY", $default);
    }
    static function EMAIL_INFO()
    {
        return env("EMAIL_INFO", "");
    }
    static function EMAIL_SUPPORT()
    {
        return env("EMAIL_SUPPORT", "");
    }

    # Database
    static function DB_HOST()
    {
        return env("DB_HOST");
    }
    static function DB_PORT()
    {
        return intval(env("DB_PORT"));
    }
    static function DB_USER()
    {
        return env("DB_USER");
    }
    static function DB_PASS()
    {
        return env("DB_PASS");
    }

    # AWS
    static function SMTP_HOST()
    {
        return env("SMTP_HOST", "");
    }
    static function SMTP_PORT()
    {
        return intval(env("SMTP_PORT", ""));
    }
    static function SMTP_KEY()
    {
        return env("SMTP_KEY", "");
    }
    static function SMTP_SECRET()
    {
        return env("SMTP_SECRET", "");
    }

    # External
    static function BUGSNAG_KEY()
    {
        return env("BUGSNAG_KEY", "");
    }
    static function RECAPTCHA_SECRET_KEY()
    {
        return env("RECAPTCHA_SECRET_KEY", "");
    }
    static function RECAPTCHA_CLIENT_KEY()
    {
        return env("RECAPTCHA_CLIENT_KEY", "");
    }
}
