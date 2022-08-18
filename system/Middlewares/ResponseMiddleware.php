<?php

namespace System\Middlewares;

use System\Dto\BaseDto;
use System\Dto\CollectionBaseDto;
use System\Interfaces\MiddlewareInterface;
use System\Interfaces\ResponseInterface;
use System\View;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
final class ResponseMiddleware implements MiddlewareInterface
{
    public function handle(callable $next = null): mixed
    {
        $response = $next ? $next() : null;
        if ($response !== false) {
            if ($response instanceof ResponseInterface) {
                $response->send();
            } else if ($response instanceof View) {
                $response->send();
            } else if ($response instanceof CollectionBaseDto) {
                app()->sendHttpResponse(200, $response->toArray());
            } else if ($response instanceof BaseDto) {
                app()->sendHttpResponse(200, $response->toArray());
            } else if (is_array($response) || is_object($response)) {
                app()->sendHttpResponse(200, $response);
            } else {
                return app()->response->html((string) $response);
            }
        }
        return false;
    }
}
