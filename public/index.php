<?php

// Path to the front controller directory

use Application\Controllers\Authorize;
use Application\Controllers\Misc;
use Application\Controllers\Resources;
use Application\Controllers\Token;
use System\Router;

define('FCPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// Prevent cli - use ../console for cli
if (PHP_SAPI === 'cli' or defined('STDIN')) exit(1);

// Load packages
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : FCPATH . 'vendor/autoload.php');
require_once(FCPATH . 'bootstrap/helpers.php');

// Set up router
$router = (new Router())
    // Authorize
    ->route_get('/authorize/request', Authorize::class, 'request')
    ->route_get_post('/authorize/login', Authorize::class, 'login')
    ->route_get_post('/authorize/logout', Authorize::class, 'logout')
    // Token
    ->route_post('/token/request', Token::class, 'request')
    ->route_get_post('/token/verify', Token::class, 'verify')
    ->route_get_post('/token/info', Token::class, 'info')
    ->route_post('/token/invalidate', Token::class, 'invalidate')
    // Resources
    ->route_get('/resources/scopes', Resources::class, 'scopes')
    ->route_get_post('/resources/removeAccess', Resources::class, 'removeAccess')
    ->route_get_post('/resources/getUser', Resources::class, 'getUser')
    ->route_get_post('/resources/fetchUsers', Resources::class, 'fetchUsers')
    ->route_post('/resources/createUser', Resources::class, 'createUser')
    ->route_post('/resources/updateUser', Resources::class, 'updateUser')
    ->route_post('/resources/createClient', Resources::class, 'createClient')
    ->route_post('/resources/updateClient', Resources::class, 'updateClient')
    ->route_get('/resources/getPublicKey', Resources::class, 'getPublicKey')
    ->route_get('/resources/generateKeyPair', Resources::class, 'generateKeyPair')
    // Misc
    ->route_get('/misc/link', Misc::class, 'link');

// Run app
(new System\App($router))->run();
