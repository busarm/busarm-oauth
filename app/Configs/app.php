<?php

/*
|--------------------------------------------------------------------------
| ADD APPLICATION CONFIGS
|--------------------------------------------------------------------------
|
*/

use System\Env;

define('APP_VERSION', env("APP_VERSION"));
define('APP_NAME', env("APP_NAME"));
define('APP_THEME_PRIMARY_COLOR', env("APP_THEME_PRIMARY_COLOR"));
define('APP_THEME_SECONDARY_COLOR', env("APP_THEME_SECONDARY_COLOR"));
define('COMPANY_NAME', env("COMPANY_NAME"));
define('SYSTEM_SHUT_DOWN_TIME', env("SYSTEM_SHUT_DOWN_TIME", false));
define('SYSTEM_START_UP_TIME', env("SYSTEM_START_UP_TIME", false));
define('MAINTENANCE_MODE', env("MAINTENANCE_MODE", false));

define('ENCRYPTION_KEY', env("ENCRYPTION_KEY", ""));

define('EMAIL_INFO', env("EMAIL_INFO", ""));
define('EMAIL_SUPPORT', env("EMAIL_SUPPORT", ""));

define('APP_URL', env("APP_URL"));
define('API_URL', env("API_URL"));
define('ASSET_URL', env("ASSET_URL"));
define('PARTNER_URL', env("PARTNER_URL"));


/*
|--------------------------------------------------------------------------
| Cookie Prefix
|--------------------------------------------------------------------------
|
| Prefix appended to cookie name to prevent collisions
*/
define('COOKIE_PREFIX', env("COOKIE_PREFIX", "oauth"));

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
define('CHECK_CORS', env("CHECK_CORS", TRUE));

/*
|--------------------------------------------------------------------------
| CORS Max Age
|--------------------------------------------------------------------------
|
| How long in seconds to cache CORS preflight response in browser.
| -1 for disabling caching.
|
*/
define('MAX_CORS_AGE', env("MAX_CORS_AGE", 3600));


/*
|--------------------------------------------------------------------------
| CORS Allowable Headers
|--------------------------------------------------------------------------
|
| If using CORS checks, set the allowable headers here
|
*/
define('ALLOWED_CORS_HEADERS', [
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
]);


/*
|--------------------------------------------------------------------------
| CORS Exposed Headers
|--------------------------------------------------------------------------
|
| If using CORS checks, set the headers permitted to be sent to client here
|
*/
define('EXPOSED_CORS_HEADERS', []);


/*
|--------------------------------------------------------------------------
| CORS Allowable Methods
|--------------------------------------------------------------------------
|
| If using CORS checks, you can set the methods you want to be allowed
|
*/
define('ALLOWED_CORS_METHODS', [
    'GET',
    'POST',
    'OPTIONS',
    'PUT',
    'PATCH',
    'DELETE'
]);

/*
|--------------------------------------------------------------------------
| CORS Allow Any Domain
|--------------------------------------------------------------------------
|
| Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
| source domain
|
*/
define('ALLOWED_ANY_CORS_DOMAIN', app()->env != Env::PROD);

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
define('ALLOWED_CORS_ORIGINS', [
    APP_URL,
    API_URL,
    PARTNER_URL
]);