<?php

namespace System;

/**
 * Define Configuration
 */
class Configs
{
    /*
    |--------------------------------------------------------------------------
    | Cookie Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix appended to cookie name to prevent collisions
    */
    const COOKIE_PREFIX = "oauth_";

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
    static function ALLOWED_CORS_ORIGINS()
    {
        return [
            self::APP_URL(),
            self::API_URL(),
            self::PARTNER_URL()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ADD CUSTOM CONFIGS
    |--------------------------------------------------------------------------
    |
    */

    static function APP_VERSION($default = '0.1')
    {
        return env("APP_VERSION", $default);
    }
    static function APP_NAME()
    {
        return env("APP_NAME");
    }
    static function APP_THEME_PRIMARY_COLOR()
    {
        return env("APP_THEME_PRIMARY_COLOR");
    }
    static function APP_THEME_SECONDARY_COLOR()
    {
        return env("APP_THEME_SECONDARY_COLOR");
    }
    static function COMPANY_NAME()
    {
        return env("COMPANY_NAME");
    }
    static function SYSTEM_SHUT_DOWN_TIME()
    {
        return env("SYSTEM_SHUT_DOWN_TIME", false);
    }
    static function SYSTEM_START_UP_TIME()
    {
        return env("SYSTEM_START_UP_TIME", false);
    }
    static function MAINTENANCE_MODE()
    {
        return env("MAINTENANCE_MODE", false);
    }
    static function API_URL()
    {
        return env("API_URL", '');
    }
    static function APP_URL()
    {
        return env("APP_URL", '');
    }
    static function ASSET_URL()
    {
        return env("ASSET_URL", '');
    }
    static function PARTNER_URL()
    {
        return env("PARTNER_URL", '');
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
    static function DB_NAME()
    {
        return env("DB_NAME");
    }
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
