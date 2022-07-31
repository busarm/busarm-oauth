<?php

namespace System;

use InvalidArgumentException;
use System\Interfaces\ResponseInterface;

/**
 * Class to handle OAuth2 Responses in a graceful way.  Use this interface
 * to output the proper OAuth2 responses.
 * This class borrows heavily from the Symfony2 Framework and is part of the symfony package
 * @see Symfony\Component\HttpFoundation\Request (https://github.com/symfony/symfony)
 *
 * @see System\Interface\ResponseInterface
 */
class Response implements ResponseInterface
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var array
     */
    protected $httpHeaders = array();

    /**
     * @var array
     */
    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    /**
     * @param array $parameters
     * @param int   $statusCode
     * @param array $headers
     */
    public function __construct($parameters = array(), $statusCode = 200, $headers = array())
    {
        $this->setParameters($parameters);
        $this->setStatusCode($statusCode);
        $this->setHttpHeaders($headers);
        $this->version = '1.1';
    }

    /**
     * Converts the response object to string containing all headers and the response content.
     *
     * @return string The response with headers and content
     */
    public function __toString()
    {
        $headers = array();
        foreach ($this->httpHeaders as $name => $value) {
            $headers[$name] = (array) $value;
        }

        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
            $this->getHttpHeadersAsString($headers) . "\r\n" .
            $this->getResponseBody();
    }

    /**
     * Returns the build header line.
     *
     * @param string $name  The header name
     * @param string $value The header value
     *
     * @return string The built header line
     */
    protected function buildHeader($name, $value)
    {
        return sprintf("%s: %s\n", $name, $value);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @param string $text
     * @throws InvalidArgumentException
     */
    public function setStatusCode($statusCode, $text = null)
    {
        $this->statusCode = (int) $statusCode;
        if ($this->isInvalid()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $statusCode));
        }

        $this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
    }

    /**
     * @return string
     */
    public function getStatusText()
    {
        return $this->statusText;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param array $httpHeaders
     */
    public function setHttpHeaders(array $httpHeaders)
    {
        $this->httpHeaders = $httpHeaders;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setHttpHeader($name, $value)
    {
        $this->httpHeaders[$name] = $value;
    }

    /**
     * @param array $httpHeaders
     */
    public function addHttpHeaders(array $httpHeaders)
    {
        $this->httpHeaders = array_merge($this->httpHeaders, $httpHeaders);
    }

    /**
     * @return array
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function getHttpHeader($name, $default = null)
    {
        return isset($this->httpHeaders[$name]) ? $this->httpHeaders[$name] : $default;
    }

    /**
     * @param string $format
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getResponseBody($format = 'json')
    {
        switch ($format) {
            case 'json':
                return $this->parameters ? json_encode($this->parameters) : '';
            case 'xml':
                // this only works for single-level arrays
                $xml = new \SimpleXMLElement('<response/>');
                foreach ($this->parameters as $key => $param) {
                    $xml->addChild($key, $param);
                }

                return $xml->asXML();
        }

        throw new InvalidArgumentException(sprintf('The format %s is not supported', $format));
    }

    /**
     * @param string $format
     * @param bool $continue
     */
    public function send($format = 'json', $continue = false)
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        switch ($format) {
            case 'json':
                $this->setHttpHeader('Content-Type', 'application/json');
                break;
            case 'xml':
                $this->setHttpHeader('Content-Type', 'text/xml');
                break;
        }
        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        foreach ($this->getHttpHeaders() as $name => $header) {
            header(sprintf('%s: %s', $name, $header));
        }
        echo $this->getResponseBody($format);
        if (!$continue) die;
    }

    /**
     * @param array $data
     * @param int $code response code
     * @param bool $continue
     */
    public function json($data, $code = 200, $continue = false)
    {
        $this->setParameters($data);
        $this->setStatusCode($code);
        $this->send('json', $continue);
    }

    /**
     * @param mixed $data
     * @param int $code response code
     * @param bool $continue
     */
    public function xml($data, $code = 200, $continue = false)
    {
        $this->setParameters($data);
        $this->setStatusCode($code);
        $this->send('xml', $continue);
    }

    /**
     * @return Boolean
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $headers
     * @return string
     */
    private function getHttpHeadersAsString($headers)
    {
        if (count($headers) == 0) {
            return '';
        }

        $max = max(array_map('strlen', array_keys($headers))) + 1;
        $content = '';
        ksort($headers);
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $this->beautifyHeaderName($name) . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param string $name
     * @return mixed
     */
    private function beautifyHeaderName($name)
    {
        return preg_replace_callback('/\-(.)/', array($this, 'beautifyCallback'), ucfirst($name));
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $match
     * @return string
     */
    private function beautifyCallback($match)
    {
        return '-' . strtoupper($match[1]);
    }
}
