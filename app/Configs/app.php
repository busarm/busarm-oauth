<?php


use Busarm\PhpMini\Enums\Env;
use Busarm\PhpMini\Enums\Verbose;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\env;

/*
|--------------------------------------------------------------------------
| ADD APPLICATION CONFIGS
|--------------------------------------------------------------------------
|
*/

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
| Set Up App instance config
|--------------------------------------------------------------------------
|
*/
app()->config->setName(APP_NAME);
app()->config->setVersion(APP_VERSION);
app()->config->setLoggerVerborsity(app()->env != Env::PROD ? Verbose::DEBUG : Verbose::NORMAL);
app()->config->setHttpCheckCors(env("CHECK_CORS", TRUE));
app()->config->setHttpAllowAnyCorsDomain(app()->env != Env::PROD);
app()->config->setHttpAllowedCorsOrigins([
    APP_URL,
    API_URL,
    PARTNER_URL
]);
app()->config->setHttpAllowedCorsMethods([
    'GET',
    'POST',
    'OPTIONS',
    'PUT',
    'PATCH',
    'DELETE'
]);
app()->config->setHttpAllowedCorsHeaders([
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
app()->config->setHttpExposedCorsHeaders(['*']);
app()->config->setHttpCorsMaxAge(env("MAX_CORS_AGE", 3600));
