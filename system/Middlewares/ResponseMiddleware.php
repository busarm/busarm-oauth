<?php

namespace System\Middlewares;

use System\Interfaces\MiddlewareInterface;
use System\Interfaces\ResponseInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
class ResponseMiddleware implements MiddlewareInterface
{
    public function handle(callable $next = null): mixed
    {
        $response = $next ? $next() : null;
        if ($response) {
            if ($response instanceof ResponseInterface) {
                $response->send('json', true);
            } else if (is_array($response)) {
                app()->sendHttpResponse(200, $response);
            } else {
                return app()->showMessage(200, true, $response);
            }
        }
        return false;
    }
}
