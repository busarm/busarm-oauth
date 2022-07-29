<?php

namespace System\Interfaces;

interface ResponseInterface
{
    /**
     * @param array $httpHeaders
     */
    public function addHttpHeaders(array $httpHeaders);

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode);

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
     */
    public function setParameters(array $parameters);

    /**
     * @param array $parameters
     */
    public function addParameters(array $parameters);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getParameter($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value);

    /**
     * @param array $httpHeaders
     */
    public function setHttpHeaders(array $httpHeaders);

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setHttpHeader($name, $value);

    /**
     * @param string $format 'json' | 'xml'
     * @param bool $continue
     */
    public function send($format = 'json', $continue = false);
}
