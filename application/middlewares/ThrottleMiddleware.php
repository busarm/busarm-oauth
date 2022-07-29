<?php

namespace Application\Middlewares;

use Application\Exceptions\AuthenticationException;
use Application\Exceptions\ThrottleException;
use Application\Helpers\Utils;
use Application\Services\OAuthService;
use System\Interfaces\MiddlewareInterface;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 30/7/2022
 * Time: 1:20 AM
 * 
 * // TODO Use Cache instead of cookie
 */
class ThrottleMiddleware implements MiddlewareInterface
{

    public function __construct(private $id, private $limit = 0, private $seconds = 60)
    {
    }

    public function handle(callable $next = null): mixed
    {
        $key = md5('throttle:' . $this->id);
        $count = (Utils::getCookie($key, IPADDRESS) ?? 0) + 1;
        if ($this->limit > 0 && $count >= $this->limit) {
            throw new ThrottleException("Too many request. Please try again later");
        } else {
            Utils::setCookie($key, $count, $this->seconds, IPADDRESS);
        }
        return $next ? $next() : true;
    }
}
