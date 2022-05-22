<?php

namespace System;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class URL
{
    const GOOGLE_FONT_URL = 'https://fonts.googleapis.com/css2?family=Arima+Madurai:wght@500&display=swap';
    const GOOGLE_RECAPTCHA_SCRIPT_URL = 'https://www.google.com/recaptcha/api.js';
    const GOOGLE_RECAPTCHA_VERFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
        
    const APP_PRIVACY_PATH = 'privacy';
    const APP_TERMS_PATH = 'terms';
    const APP_SUPPORT_PATH = 'support';

    /**
     * Get PATH for APP
     *
     * @param string $path
     * @return void
     */
    public static function appUrl($path = '')
    {
        if (ENVIRONMENT == ENV_PROD)
            return "https://busarm.com/" . $path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://staging.busarm.com/" . $path;
        else
            return "http://localhost/" . $path;
    }

    /**
     * Get PATH for Asset
     *
     * @param string $path
     * @return void
     */
    public static function assetUrl($path = '')
    {
        if (ENVIRONMENT == ENV_PROD)
            return "https://cdn.busarm.com/" . $path;
        else  if (ENVIRONMENT == ENV_TEST)
            return "https://cdn.staging.busarm.com/" . $path;
        else
            return "https://cdn.staging.busarm.com/" . $path;
    }

    /**
     * Get base url
     *
     * @param string $path
     * @return string
     */
    public static function baseUrl($path = '', $params = [])
    {
        $url = trim(BASE_URL, '/') . '/' . $path;
        if (!empty($params)) {
            $url .= '?' . ((function_exists('http_build_query')) ? http_build_query($params) : self::buildUrlParams($params));
        }
        return $url;
    }

    /**
     * @param array $params
     * @param string $parent
     * @return string
     */
    public static function buildUrlParams($params, $parent = null)
    {
        $query = '';
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                $query .= $parent ? self::buildUrlParams($param, $parent . "[$key]") : self::buildUrlParams($param, $key);
            } else {
                $query .= ($parent ? urlencode($parent . "[$key]") . "=$param&" : "$key=$param&");
            }
        }
        return trim($query, '&');
    }

    /**
     * @param string $url
     * @param array $params
     * @param bool $override Overide URL query with given params if duplicate found
     * @return string
     */
    public static function parseUrl($url, $params = [], $override = false)
    {
        if (!empty($params)) {
            parse_str(parse_url($url,  PHP_URL_QUERY), $query);

            // Defining a callback function
            $callback = function ($var) {
                return ($var !== NULL && $var !== FALSE && $var !== "");
            };

            if ($override) {
                $params = array_merge(array_filter($query, $callback), array_filter($params, $callback));
            } else {
                $params = array_merge(array_filter($params, $callback), array_filter($query, $callback));
            }

            if (isset($params['ajax'])) {
                unset($params['ajax']);
            }
            if (isset($params['pjax'])) {
                unset($params['pjax']);
            }

            $url = explode('?', $url)[0];
            if (!empty($params)) {
                $url .= '?' . ((function_exists('http_build_query')) ? http_build_query($params) : self::buildUrlParams($params));
            }
        }
        return $url;
    }

    /**
     * Header Redirect
     *	 *
     * @param	string	$uri	URL
     * @param	string	$method	Redirect method
     *			'auto', 'location' or 'refresh'
     * @param	int	$code	HTTP Response status code
     * @return	void
     */
    public static function redirect($uri, $method = 'auto', $code = NULL)
    {
        if (!preg_match('#^(\w+:)?//#i', $uri)) {
            $uri = self::baseUrl($uri);
        }

        // IIS environment likely? Use 'refresh' for better compatibility
        if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== FALSE) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && (empty($code) or !is_numeric($code))) {
            if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                $code = ($_SERVER['REQUEST_METHOD'] !== 'GET')
                    ? 303    // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
                    : 307;
            } else {
                $code = 302;
            }
        }

        switch ($method) {
            case 'refresh':
                header('Refresh:0;url=' . $uri);
                break;
            default:
                header('Location: ' . $uri, TRUE, $code);
                break;
        }
        exit;
    }

    /**
     * Generate secure link
     *
     * @param string $link
     * @return string
     */
    public static function generateSecureLink($link)
    {
        if ($link) {
            $data = CIPHER::encrypt(Configs::ENCRYPTION_KEY(), $link);
            if ($data) {
                return URL::baseUrl('misc/link', ['data' => $data]);
            }
        }
        return null;
    }
}
