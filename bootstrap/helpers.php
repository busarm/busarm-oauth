<?php

use System\App;

if (!function_exists('is_cli')) {
    /**
     * Is CLI?
     *
     * Test to see if a request was made from the command line.
     *
     * @return 	bool
     */
    function is_cli()
    {
        return (PHP_SAPI === 'cli' or defined('STDIN'));
    }
}


if (!function_exists('getallheaders')) {
    /**
     * Define 'getallheaders' if doesn't exist
     * @return	array
     */
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}


if (!function_exists('env')) {
    /**
     * Get Server Variable
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    function env($name, $default = null)
    {
        return (!empty($data = @getenv($name)) ? $data : $default);
    }
}


if (!function_exists('is_https')) {
    /**
     * Check if https enabled*/
    function is_https()
    {
        if (!empty(env('HTTPS')) && strtolower(env('HTTPS')) !== 'off') {
            return TRUE;
        } elseif (!empty(env('HTTP_X_FORWARDED_PROTO')) && strtolower(env('HTTP_X_FORWARDED_PROTO')) === 'https') {
            return TRUE;
        } elseif (!empty(env('HTTP_FRONT_END_HTTPS')) && strtolower(env('HTTP_FRONT_END_HTTPS')) !== 'off') {
            return TRUE;
        }
        return FALSE;
    }
}


if (!function_exists('get_ip_address')) {
    /**
     * Get Ip of users
     *
     */
    function get_ip_address()
    {
        // check for shared internet/ISP IP
        if (!empty(env('HTTP_CLIENT_IP')) && validate_ip(env('HTTP_CLIENT_IP'))) {
            return env('HTTP_CLIENT_IP');
        }
        // check for IPs passing through proxies
        if (!empty(env('HTTP_X_FORWARDED_FOR'))) {
            // check if multiple ips exist in var
            if (strpos(env('HTTP_X_FORWARDED_FOR'), ',') !== false) {
                $iplist = explode(',', env('HTTP_X_FORWARDED_FOR'));
                foreach ($iplist as $ip) {
                    if (validate_ip($ip))
                        return $ip;
                }
            } else {
                if (validate_ip(env('HTTP_X_FORWARDED_FOR')))
                    return env('HTTP_X_FORWARDED_FOR');
            }
        }
        if (!empty(env('HTTP_X_FORWARDED')) && validate_ip(env('HTTP_X_FORWARDED')))
            return env('HTTP_X_FORWARDED');

        if (!empty(env('HTTP_X_CLUSTER_CLIENT_IP')) && validate_ip(env('HTTP_X_CLUSTER_CLIENT_IP')))
            return env('HTTP_X_CLUSTER_CLIENT_IP');

        if (!empty(env('HTTP_FORWARDED_FOR')) && validate_ip(env('HTTP_FORWARDED_FOR')))
            return env('HTTP_FORWARDED_FOR');

        if (!empty(env('HTTP_FORWARDED')) && validate_ip(env('HTTP_FORWARDED')))
            return env('HTTP_FORWARDED');

        // return unreliable ip since all else failed
        return env('REMOTE_ADDR');
    }
}


if (!function_exists('validate_ip')) {

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     * @param $ip
     * @return bool
     */
    function validate_ip($ip)
    {
        if (strtolower($ip) === 'unknown')
            return false;
        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {

            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);

            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) return false;
            if ($ip >= 167772160 && $ip <= 184549375) return false;
            if ($ip >= 2130706432 && $ip <= 2147483647) return false;
            if ($ip >= 2851995648 && $ip <= 2852061183) return false;
            if ($ip >= 2886729728 && $ip <= 2887778303) return false;
            if ($ip >= 3221225984 && $ip <= 3221226239) return false;
            if ($ip >= 3232235520 && $ip <= 3232301055) return false;
            if ($ip >= 4294967040) return false;
        }

        return true;
    }
}



if (!function_exists('get_server_protocol')) {

    /**
     * Get server protocol or http version
     * @return bool
     */
    function get_server_protocol()
    {
        return (!empty(env('SERVER_PROTOCOL')) && in_array(env('SERVER_PROTOCOL'), array('HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0'), TRUE))
            ? env('SERVER_PROTOCOL') : 'HTTP/1.1';
    }
}


if (!function_exists('app')) {
    /**
     * Get app instance
     * @return \System\App
     */
    function app()
    {
        return \System\App::getInstance();;
    }
}


if (!function_exists('out')) {
    /**
     * Send output of data
     * @param string $data
     * @param int $code
     */
    function out($data = null, $code = 0)
    {
        header("Content-type: application/json");
        print_r(json_encode($data, JSON_PRETTY_PRINT) ?: $data);
        print_r(PHP_EOL);
        exit($code);
    }
}
