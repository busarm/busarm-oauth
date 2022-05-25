<?php
define('FCPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
// Prevent cli - use ../console for cli
if (PHP_SAPI === 'cli' or defined('STDIN')) exit(1);
// Load packages
require_once(boolval(getenv('SEPARATE_VENDOR')) ? '/tmp/vendor/autoload.php' : FCPATH . 'vendor/autoload.php');
require_once(FCPATH . 'bootstrap/helpers.php');

use Application\Controllers\Authorize;
use Application\Controllers\Misc;
use Application\Controllers\Resources;
use Application\Controllers\Token;
use System\Router;

// Set up router
$router = (new Router())
    // Authorize
    ->get('/authorize/request', Authorize::class, 'request')
    ->get_post('/authorize/login', Authorize::class, 'login')
    ->get_post('/authorize/logout', Authorize::class, 'logout')
    // Token
    ->post('/token/request', Token::class, 'request')
    ->get_post('/token/verify', Token::class, 'verify')
    ->get_post('/token/info', Token::class, 'info')
    ->post('/token/invalidate', Token::class, 'invalidate')
    // Resources
    ->get('/resources/scopes', Resources::class, 'scopes')
    ->get_post('/resources/removeAccess', Resources::class, 'removeAccess')
    ->get_post('/resources/getUser', Resources::class, 'getUser')
    ->get_post('/resources/fetchUsers', Resources::class, 'fetchUsers')
    ->post('/resources/createUser', Resources::class, 'createUser')
    ->post_put('/resources/updateUser', Resources::class, 'updateUser')
    ->post('/resources/createClient', Resources::class, 'createClient')
    ->post_put('/resources/updateClient', Resources::class, 'updateClient')
    ->get('/resources/getPublicKey', Resources::class, 'getPublicKey')
    ->get('/resources/generateKeyPair', Resources::class, 'generateKeyPair')
    // Misc
    ->get('/misc/link', Misc::class, 'link');

// Run app
(new System\App($router))->run();
