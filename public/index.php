<?php
define('FCPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
// Prevent cli - use ../console for cli
if (PHP_SAPI === 'cli' or defined('STDIN')) exit(1);
// Load packages
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : FCPATH . 'vendor/autoload.php');
require_once(FCPATH . 'bootstrap/helpers.php');
require_once(FCPATH . 'bootstrap/constants.php');

use Application\Controllers\HTTP\Authorize;
use Application\Controllers\HTTP\Misc;
use Application\Controllers\HTTP\Resources;
use Application\Controllers\HTTP\Token;
use Application\Middlewares\AuthenticateMiddleware;
use Application\Middlewares\AuthorizeMiddleware;
use Application\Middlewares\ThrottleMiddleware;
use System\Router;

$app = new System\App();
// Add config files
$app->addConfig('scopes');
// Set up router
$app->addRouter((new Router())
        // Misc
        ->get('ping', Misc::class, 'ping', [
            new ThrottleMiddleware('ping-system', 10, 60)
        ])
        ->get('/misc/link', Misc::class, 'link', [
            new ThrottleMiddleware('link-get', 5, 60)
        ])
        // Authorize
        ->get('/authorize/request', Authorize::class, 'request', [
            new ThrottleMiddleware('authorize-request', 5, 60)
        ])
        ->get_post('/authorize/login', Authorize::class, 'login')
        ->get_post('/authorize/logout', Authorize::class, 'logout')
        // Token
        ->post('/token/request', Token::class, 'request', [
            new ThrottleMiddleware('token-request', 5, 60)
        ])
        ->get_post('/token/verify', Token::class, 'verify', [
            new ThrottleMiddleware('token-verify', 10, 60)
        ])
        ->get_post('/token/info', Token::class, 'info', [
            new ThrottleMiddleware('token-info', 10, 60)
        ])
        ->get_post('/token/user', Token::class, 'user', [
            new ThrottleMiddleware('token-user', 10, 60)
        ])
        ->post('/token/invalidate', Token::class, 'invalidate', [
            new ThrottleMiddleware('token-invalidate', 10, 60)
        ])
        // Resources
        ->get('/resources/scopes', Resources::class, 'scopes', [
            new AuthenticateMiddleware(),
            new ThrottleMiddleware('scopes-get', 10, 60)
        ])
        ->get_post('/resources/removeAccess', Resources::class, 'removeAccess', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->get_post('/resources/getUser', Resources::class, 'getUser', [
            new AuthenticateMiddleware(),
        ])
        ->get_post('/resources/getUserById', Resources::class, 'getUserById', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->get_post('/resources/fetchUsers', Resources::class, 'fetchUsers', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->post('/resources/createUser', Resources::class, 'createUser', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->post_put('/resources/updateUser', Resources::class, 'updateUser', [
            new AuthenticateMiddleware(),
        ])
        ->post_put('/resources/updateUserById', Resources::class, 'updateUserById', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->post('/resources/createClient', Resources::class, 'createClient', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->post_put('/resources/updateClient', Resources::class, 'updateClient', [
            new AuthenticateMiddleware(),
        ])
        ->post_put('/resources/updateClientById', Resources::class, 'updateClientById', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->post_put('/resources/updateClientKeys', Resources::class, 'updateClientKeys', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->get('/resources/getPublicKey', Resources::class, 'getPublicKey', [
            new AuthenticateMiddleware(),
        ])
        ->get('/resources/getPublicKeyById', Resources::class, 'getPublicKey', [
            new AuthenticateMiddleware(),
            new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
        ])
        ->get('/resources/generateKeyPair', Resources::class, 'generateKeyPair', [
            new AuthenticateMiddleware(),
        ])
);

// Run app
$app->run();
