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
}
