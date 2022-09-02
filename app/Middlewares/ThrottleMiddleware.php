<?php

namespace App\Middlewares;

use App\Exceptions\ThrottleException;
use App\Helpers\Utils;
use Busarm\PhpMini\App;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;

// TODO Use Cache instead of cookie
/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 30/7/2022
 * Time: 1:20 AM
 * 
 * Basic throttling using browser cookies
 */
class ThrottleMiddleware implements MiddlewareInterface
{

    public function __construct(private $limit = 0, private $seconds = 60, private $name = null)
    {
    }

    public function handle(App $app, callable $next = null): mixed
    {
        $key = md5('throttle:' . $this->name . $app->router->getRequestPath() . $app->router->getRequestMethod());
        $count = (Utils::getCookie($key, $app->request->ip()) ?? 0) + 1;
        if ($this->limit > 0 && $count >= $this->limit) {
            throw new ThrottleException("Too many request. Please try again later");
        } else {
            Utils::setCookie($key, $count, $this->seconds, $app->request->ip());
        }
        return $next ? $next() : true;
    }
}
