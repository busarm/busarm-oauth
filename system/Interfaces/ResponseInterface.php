<?php

namespace System\Interfaces;

interface ResponseInterface
{
    /**
     * @param array $httpHeaders
     * @return self
     */
    public function addHttpHeaders(array $httpHeaders): self;

    /**
     * @param int $statusCode
     * @param string $text
     * @return self
     */
    public function setStatusCode($statusCode, $text = null): self;

    /**
     * @return string
     */
    public function getStatusText();

    /**
     * @return array
     */
    public function getParameters();

    /**
     * @param array $parameters
     * @return self
     */
    public function setParameters(array $parameters): self;

    /**
     * @param array $parameters
     * @return self
     */
    public function addParameters(array $parameters): self;

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getParameter($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function setParameter($name, $value): self;

    /**
     * @param array $httpHeaders
     * @return self
     */
    public function setHttpHeaders(array $httpHeaders): self;

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function setHttpHeader($name, $value): self;

    /**
     * Header Redirect
     *
     * @param string $uri URL
     * @param string $method Redirect method 'auto', 'location' or 'refresh'
     * @param int $code	HTTP Response status code
     * @return self
     */
    public function redirect($uri, $method = 'auto', $code = NULL): self;

    /**
     * @param string $format 'json' | 'xml'
     * @param bool $continue
     */
    public function send($format = 'json', $continue = false);

    /**
     * @param array $data
     * @param int $code response code
     * @param bool $continue
     */
    public function json($data, $code = 200, $continue = false);

    /**
     * @param array $data
     * @param int $code response code
     * @param bool $continue
     */
    public function xml($data, $code = 200, $continue = false);

    /**
     * @param string|null $data
     * @param int $code response code
     * @param bool $continue
     */
    public function html($data, $code = 200, $continue = false);
    
    /**
     * @return Boolean
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isInformational();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isRedirection();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isClientError();

    /**
     * @return Boolean
     *
     * @api
     */
    public function isServerError();
}
