<?php

namespace App\Middlewares;

use App\Exceptions\ThrottleException;
use App\Helpers\Utils;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

/**
 * Basic throttling using browser cookies
 * // TODO Use Cache instead of cookie
 * 
 * Created by VSCODE.
 * User: Samuel
 * Date: 30/7/2022
 * Time: 1:20 AM
 */
class ThrottleMiddleware implements MiddlewareInterface
{

    public function __construct(private $limit = 0, private $seconds = 60, private $name = null)
    {
    }

    /**
     * Middleware handler
     *
     * @param RequestInterface|RouteInterface $request
     * @param RequestHandlerInterface $handle
     * @return ResponseInterface
     */
    public function process(RequestInterface|RouteInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request instanceof RequestInterface) {
            $key = md5('throttle:' . $this->name . $request->uri() . $request->method());
            $count = ($request->cookie()->get($key) ?? 0) + 1;
            if ($this->limit > 0 && $count >= $this->limit) {
                throw new ThrottleException("Too many request. Please try again later");
            } else {
                $request->cookie()->set($key, $count, $this->seconds);
            }
        }
        return $handler->handle($request);
    }
}
