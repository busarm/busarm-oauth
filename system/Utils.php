<?php

namespace System;

use DateTime;
use DateTimeZone;
use Phinx\Config\Config;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Utils
{
    /**Generate CSRF TOKEN
     * @return string
     */
    public static function generate_csrf_token($key = null)
    {
        if (empty($key) && empty($key = self::get_cookie("csrf_key"))) {
            $key = md5(uniqid(IPADDRESS));
            self::set_cookie("csrf_key", $key);
        }
        $dateObj = new DateTime("now", new DateTimeZone("GMT"));
        $csrf_token = sha1(sprintf("%s:%s:%s", $key, IPADDRESS, $dateObj->format('Y-m-d H')));
        return $csrf_token;
    }


    /**Get CSRF TOKEN
     * @return string
     */
    public static function get_csrf_token()
    {
        if (!empty($key = self::get_cookie("csrf_key"))) {
            return self::generate_csrf_token($key);
        }
        return null;
    }

    /**CSRF Validation
     * @param string $csrf_token
     * @return array|boolean
     */
    public static function validate_csrf_token($csrf_token)
    {
        if ($csrf_token) {
            return $csrf_token == self::get_csrf_token();
        }
        return false;
    }

    /**
     * Get cookie
     * @param String $name
     * @return void
     */
    public static function get_cookie($name)
    {
        return !empty($_COOKIE["oauth_" . $name]) ? @$_COOKIE[Configs::COOKIE_PREFIX . $name] : null;
    }

    /**
     * Pull cookie - Get and delete cooke
     * @param String $name
     * @return void
     */
    public static function pull_cookie($name)
    {
        $value = self::get_cookie($name);
        if ($value)
            self::delete_cookie($name);
        return $value;
    }

    /**
     * Set cookie
     *
     * @param [type] $name
     * @param [type] $value
     * @param integer $duration
     * @return bool
     */
    public static function set_cookie($name, $value, $duration = 3600)
    {
        return setcookie(Configs::COOKIE_PREFIX . $name, $value, time() + $duration, "/");
    }

    /**
     * Delete cookie
     * @param String $name
     * @return bool
     */
    public static function delete_cookie($name)
    {
        return self::set_cookie($name, "", -3600);
    }


    /**
     * Case Insensitive search
     * @param $fileName string
     * @param $caseSensitive bool
     * @return mixed
     */
    public static function fileExists($fileName, $caseSensitive = true)
    {

        if (file_exists($fileName)) {
            return $fileName;
        }
        if ($caseSensitive) return false;

        // Handle case insensitive requests
        $directoryName = dirname($fileName);
        $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower($fileName);
        foreach ($fileArray as $file) {
            if (strtolower($file) == $fileNameLowerCase) {
                return $file;
            }
        }
        return false;
    }
}
