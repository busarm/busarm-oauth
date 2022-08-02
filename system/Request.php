<?php

namespace System;

use LogicException;
use System\Interfaces\RequestInterface;

/**
 * This class is taken from the Symfony2 Framework and is part of the Symfony package.
 * See Symfony\Component\HttpFoundation\Request (https://github.com/symfony/symfony)
 * 
 * @see System\Interface\RequestInterface
 */
class Request implements RequestInterface
{
    protected $attributes;
    protected $request;
    protected $query;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;
    protected $content;
    protected $ip;
    protected $scheme;
    protected $host;
    protected $baseUrl;
    protected $currentUrl;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ip = get_ip_address();
        $this->scheme = (is_https() ? "https" : "http");
        $this->host = $this->scheme  . "://" . env('HTTP_HOST');
        $this->baseUrl = $this->host . str_replace(basename(env('SCRIPT_NAME')), "", env('SCRIPT_NAME'));
        $this->currentUrl = $this->host . env('REQUEST_URI');

        $this->initialize($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
        $contentType = $this->server('CONTENT_TYPE', '');
        $requestMethod = $this->server('REQUEST_METHOD', 'GET');
        if (
            0 === strpos($contentType, 'application/x-www-form-urlencoded')
            && in_array(strtoupper($requestMethod), array('PUT', 'DELETE'))
        ) {
            parse_str($this->getContent(), $data);
            $this->request = $data;
        } elseif (
            0 === strpos($contentType, 'application/json')
            && in_array(strtoupper($requestMethod), array('POST', 'PUT', 'DELETE'))
        ) {
            $data = json_decode($this->getContent(), true);
            $this->request = $data;
        }
    }

    /**
     * Sets the parameters for this request.
     *
     * This method also re-initializes all properties.
     *
     * @param array  $query      - The GET parameters
     * @param array  $request    - The POST parameters
     * @param array  $attributes - The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array  $cookies    - The COOKIE parameters
     * @param array  $files      - The FILES parameters
     * @param array  $server     - The SERVER parameters
     * @param string $content    - The raw body data
     * @param array  $headers    - The headers
     *
     * @api
     */
    public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null, array $headers = null)
    {
        $this->request = $request;
        $this->query = $query;
        $this->attributes = $attributes;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->content = $content;
        $this->headers = is_null($headers) ? $this->getHeadersFromServer($this->server) : $headers;
    }

    /**
     * @return mixed
     */
    public function ip()
    {
        return $this->ip;
    }
    
    /**
     * @return string
     */
    public function scheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function host()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function baseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function currentUrl()
    {
        return $this->currentUrl;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function query($name, $default = null)
    {
        return isset($this->query[$name]) ? $this->query[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function request($name, $default = null)
    {
        return isset($this->request[$name]) ? $this->request[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function server($name, $default = null)
    {
        return isset($this->server[$name]) ? $this->server[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function headers($name, $default = null)
    {
        $headers = array_change_key_case($this->headers);
        $name = strtolower($name);

        return isset($headers[$name]) ? $headers[$name] : $default;
    }
    /**
     * @return array
     */
    public function getQueryList()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getRequestList()
    {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getServerList()
    {
        return $this->server;
    }

    /**
     * @return array
     */
    public function getHeaderList()
    {
        return $this->headers;
    }

    /**
     * Returns the request body content.
     *
     * @param boolean $asResource - If true, a resource will be returned
     * @return string|resource    - The request body content or a resource to read the body stream.
     *
     * @throws LogicException
     */
    public function getContent($asResource = false)
    {
        if (false === $this->content || (true === $asResource && null !== $this->content)) {
            throw new LogicException('getContent() can only be called once when using the resource return type.');
        }

        if (true === $asResource) {
            $this->content = false;

            return fopen('php://input', 'rb');
        }

        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * @param array $server
     * @return array
     */
    private function getHeadersFromServer($server)
    {
        $headers = array();
        foreach ($server as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
                $headers[$key] = $value;
            }
        }

        if (isset($server['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $server['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($server['PHP_AUTH_PW']) ? $server['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add this line to your .htaccess file:
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($server['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $server['HTTP_AUTHORIZATION'];
            } elseif (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('apache_request_headers')) {
                $requestHeaders = (array) apache_request_headers();

                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

                if (isset($requestHeaders['Authorization'])) {
                    $authorizationHeader = trim($requestHeaders['Authorization']);
                }
            }

            if (null !== $authorizationHeader) {
                $headers['AUTHORIZATION'] = $authorizationHeader;
                // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                if (0 === stripos($authorizationHeader, 'basic')) {
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)));
                    if (count($exploded) == 2) {
                        list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                    }
                }
            }
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . $headers['PHP_AUTH_PW']);
        }

        return $headers;
    }
}
