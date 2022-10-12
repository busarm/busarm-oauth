<?php

namespace App\Middlewares;

use Busarm\PhpMini\Interfaces\MiddlewareInterface;
use Busarm\PhpMini\Interfaces\RequestHandlerInterface;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Interfaces\ResponseInterface;
use Busarm\PhpMini\Interfaces\RouteInterface;

/**
 * Add security headers to response
 * 
 * @see https://content-security-policy.com/
 * 
 * Created by VSCODE.
 * User: Samuel
 * Date: 30/7/2022
 * Time: 1:20 AM
 */
class HelmetMiddleware implements MiddlewareInterface
{

    public function __construct(
        private string|bool $referrerPolicy = "no-referrer-when-downgrade",
        private string|bool $xssProtection = "1; mode=block",
        private string|bool $xFrameOption = "SAMEORIGIN",
        private string|bool $xContentTypeOption = "nosniff",
        private string|bool $hsts = "max-age=63072000; includeSubdomains; preload",
        private array|bool $csp = [
            'default-src' => [
                'self'
            ]
        ],
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
        return $this->handle($handler->handle($request));
    }

    /**
     * Process
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function handle(ResponseInterface $response): ResponseInterface
    {
        $this->referrerPolicy and $response->setHttpHeader('Referrer-Policy', $this->referrerPolicy);
        $this->xssProtection and $response->setHttpHeader('X-XSS-Protection', $this->xssProtection);
        $this->xFrameOption and $response->setHttpHeader('X-Frame-Options', $this->xFrameOption);
        $this->xContentTypeOption and $response->setHttpHeader('X-Content-Type-Options', $this->xContentTypeOption);
        $this->hsts and $response->setHttpHeader('Strict-Transport-Security', $this->hsts);
        $this->csp and $response->setHttpHeader('Content-Security-Policy', $this->generateCSP($this->csp));
        return $response;
    }

    /**
     * Generate Content Site Policy string from array
     * @param $id
     * @param $value
     * @return string
     */
    public function generateCSP($data = [], $parent = null)
    {
        $csp = $parent ? $parent : "";
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $csp .= $this->generateCSP($val, $key) . '; ';
            } else {
                $csp .= sprintf(" %s", $val);
            }
        }
        return $csp;
    }
}
