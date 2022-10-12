<?php

namespace App\Middlewares;

use App\Exceptions\ThrottleException;
use App\Helpers\Utils;
use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

use function Busarm\PhpMini\Helpers\out;

/**
 * Basic throttling using browser cookies
 * // TODO Add Handling with Cache
 * 
 * Created by VSCODE.
 * User: Samuel
 * Date: 30/7/2022
 * Time: 1:20 AM
 */
class ThrottleMiddleware implements MiddlewareInterface
{

    /**
     * @param integer $limit
     * @param integer $seconds
     * @param string|null|null $name
     * @param boolean $route Used for specific route. Set to false if used as a global middleware
     */
    public function __construct(
        private int $limit = 0,
        private int $seconds = 60,
        private string|null $name = null,
        private bool $route = true
    ) {
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
            if (!empty($request->session()->getId())) {
                $this->handleWithSession($request);
            } else {
                $this->handleWithCookie($request);
            }
        }
        return $handler->handle($request);
    }

    /**
     * Handle throttling with cookies
     *
     * @param RequestInterface $request
     * @return void
     */
    private function handleWithCookie(RequestInterface $request)
    {
        $key = md5(ThrottleMiddleware::class . $this->name . ($this->route ? $request->uri() . $request->method() : ''));
        $count = ($request->cookie()->get($key) ?? 0) + 1;
        if ($this->limit > 0 && $count >= $this->limit) {
            throw new ThrottleException("Too many request. Please try again later");
        }
        $request->cookie()->set($key, $count, $this->seconds);
    }

    /**
     * Handle throttling with session
     *
     * @param RequestInterface $request
     * @return void
     */
    private function handleWithSession(RequestInterface $request)
    {
        $key = md5(ThrottleMiddleware::class . $this->name . ($this->route ? $request->uri() . $request->method() : ''));
        $count = 0;
        $expiry = time() + $this->seconds;
        $data = $request->session()->get($key) ?? [];
        if (!empty($data)) {
            $count = ($data['count'] ?? $count) + 1;
            $expiry = $data['expiry'] ?? $expiry;
            if ($this->limit > 0 && $count >= $this->limit) {
                // Remove session if expired
                if ($expiry <= time()) $request->session()->remove($key);
                throw new ThrottleException("Too many request. Please try again later");
            }
        }
        $request->session()->set($key, [
            'count' => $count,
            'expiry' => $expiry,
        ]);
    }
}
