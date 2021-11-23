<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');
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
    
    static function ENCRYPTION_KEY($default = null){
        return getServer("ENCRYPTION_KEY", $default);
    }

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
    
    static function BUGSNAG_KEY(){
        return getServer("BUGSNAG_KEY", "");
    }

    static function RECAPTCHA_SECRET_KEY(){
        return getServer("RECAPTCHA_SECRET_KEY", "");
    }
    static function RECAPTCHA_CLIENT_KEY(){
        return getServer("RECAPTCHA_CLIENT_KEY", "");
    }
}