<?php

/*
|--------------------------------------------------------------------------
| ADD APPLICATION ROTUES
|--------------------------------------------------------------------------
| e.g app()->router->addRoutes([Route::get(...)]);
|
*/

use System\Route;
use App\Controllers\HTTP\Authorize;
use App\Controllers\HTTP\Misc;
use App\Controllers\HTTP\Resources;
use App\Controllers\HTTP\Token;
use App\Middlewares\AuthenticateMiddleware;
use App\Middlewares\AuthorizeMiddleware;
use App\Middlewares\ThrottleMiddleware;

// Miscellaneous
app()->router->addRoutes([
    Route::get('/ping')->call(function () {
        return app()->showMessage(200, "System Online");
    })->middlewares([
        new ThrottleMiddleware(60, 60, 'ping-system')
    ]),
    Route::get('/misc/link')->to(Misc::class, 'link')->middlewares([
        new ThrottleMiddleware(5, 60, 'link-get')
    ]),
]);


// Authorize
app()->router->addRoutes([
    Route::get('/authorize/request')->to(Authorize::class, 'request')->middlewares([
        new ThrottleMiddleware(10, 60, 'authorize-request')
    ]),
    Route::post('/authorize/request')->to(Authorize::class, 'request')->middlewares([
        new ThrottleMiddleware(5, 60, 'authorize-request')
    ]),
    Route::get('/authorize/login')->to(Authorize::class, 'login'),
    Route::post('/authorize/login')->to(Authorize::class, 'processLogin')->middlewares([
        new ThrottleMiddleware(10, 60, 'authorize-request')
    ]),
    Route::get('/authorize/logout')->to(Authorize::class, 'logout'),
]);


// Token
app()->router->addRoutes([
    Route::post('/token/request')->to(Token::class, 'request')->middlewares([
        new ThrottleMiddleware(5, 60, 'token-request')
    ]),
    Route::get('/token/verify')->to(Token::class, 'verify')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-verify')
    ]),
    Route::post('/token/verify')->to(Token::class, 'verify')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-verify')
    ]),
    Route::get('/token/info')->to(Token::class, 'info')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-info')
    ]),
    Route::post('/token/info')->to(Token::class, 'info')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-info')
    ]),
    Route::get('/token/user')->to(Token::class, 'user')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-user')
    ]),
    Route::post('/token/user')->to(Token::class, 'user')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-user')
    ]),
    Route::post('/token/invalidate')->to(Token::class, 'invalidate')->middlewares([
        new ThrottleMiddleware(10, 60, 'token-invalidate')
    ]),


    // Resources
    Route::get('/resources/scopes')->to(Resources::class, 'scopes')->middlewares([
        new AuthenticateMiddleware(),
        new ThrottleMiddleware(10, 60, 'scopes-get')
    ]),
    Route::get('/resources/removeAccess')->to(Resources::class, 'removeAccess')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/removeAccess')->to(Resources::class, 'removeAccess')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/getUser')->to(Resources::class, 'getUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::get('/resources/getUserById')->to(Resources::class, 'getUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/fetchUsers')->to(Resources::class, 'fetchUsers')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/fetchUsers')->to(Resources::class, 'fetchUsers')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/createUser')->to(Resources::class, 'createUser')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateUser')->to(Resources::class, 'updateUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::put('/resources/updateUser')->to(Resources::class, 'updateUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::post('/resources/updateUserById')->to(Resources::class, 'updateUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateUserById')->to(Resources::class, 'updateUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/createClient')->to(Resources::class, 'createClient')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateClient')->to(Resources::class, 'updateClient')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::put('/resources/updateClient')->to(Resources::class, 'updateClient')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::post('/resources/updateClientById')->to(Resources::class, 'updateClientById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateClientById')->to(Resources::class, 'updateClientById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateClientKeys')->to(Resources::class, 'updateClientKeys')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateClientKeys')->to(Resources::class, 'updateClientKeys')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/getPublicKey')->to(Resources::class, 'getPublicKey')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::get('/resources/getPublicKeyById')->to(Resources::class, 'getPublicKeyById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/generateKeyPair')->to(Resources::class, 'generateKeyPair')->middlewares([
        new AuthenticateMiddleware(),
    ])
]);


// Resources
app()->router->addRoutes([
    Route::get('/resources/scopes')->to(Resources::class, 'scopes')->middlewares([
        new AuthenticateMiddleware(),
        new ThrottleMiddleware(10, 60, 'scopes-get')
    ]),
    Route::get('/resources/removeAccess')->to(Resources::class, 'removeAccess')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/removeAccess')->to(Resources::class, 'removeAccess')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/getUser')->to(Resources::class, 'getUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::get('/resources/getUserById')->to(Resources::class, 'getUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/fetchUsers')->to(Resources::class, 'fetchUsers')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/fetchUsers')->to(Resources::class, 'fetchUsers')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/createUser')->to(Resources::class, 'createUser')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateUser')->to(Resources::class, 'updateUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::put('/resources/updateUser')->to(Resources::class, 'updateUser')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::post('/resources/updateUserById')->to(Resources::class, 'updateUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateUserById')->to(Resources::class, 'updateUserById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/createClient')->to(Resources::class, 'createClient')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateClient')->to(Resources::class, 'updateClient')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::put('/resources/updateClient')->to(Resources::class, 'updateClient')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::post('/resources/updateClientById')->to(Resources::class, 'updateClientById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateClientById')->to(Resources::class, 'updateClientById')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::post('/resources/updateClientKeys')->to(Resources::class, 'updateClientKeys')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::put('/resources/updateClientKeys')->to(Resources::class, 'updateClientKeys')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/getPublicKey')->to(Resources::class, 'getPublicKey')->middlewares([
        new AuthenticateMiddleware(),
    ]),
    Route::get('/resources/getPublicKeyById')->to(Resources::class, 'getPublicKey')->middlewares([
        new AuthenticateMiddleware(),
        new AuthorizeMiddleware(SCOPE_SYSTEM, SCOPE_ADMIN),
    ]),
    Route::get('/resources/generateKeyPair')->to(Resources::class, 'generateKeyPair')->middlewares([
        new AuthenticateMiddleware(),
    ])
]);
