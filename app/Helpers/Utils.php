<?php

namespace App\Helpers;

use DateTime;
use DateTimeZone;
use Busarm\PhpMini\Crypto;

use function Busarm\PhpMini\Helpers\app;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Utils
{
    
    /**
     * In array case-insensitive
     *
     * @param string $needle
     * @param string[]|array $haystack
     * @return void
     */
    public function inArrayi($needle, $haystack)
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

    /**
     * Explode array
     * @param mixed $data
     * @param string $delimiter
     * @return array
     */
    public static function explode($data, $delimiter = " ")
    {
        $res = [];
        if (!empty($data)) {
            if (is_array($data)) {
                $res = $data;
            } else {
                if (is_string($data) && !empty($arr = json_decode($data, true))) {
                    $res = $arr;
                } else if (is_string($data)) {
                    $res = explode($delimiter, $data);
                }
            }
        }
        return $res;
    }

    /**
     * Implode data
     * @param mixed $data
     * @param string $glue
     * @return string
     */
    public static function implode($data, $glue = " ")
    {
        $res = "";
        if (!empty($data)) {
            if (is_array($data)) {
                $res = implode($glue, $data);
            } else {
                if (is_string($data) && !empty($arr = json_decode($data))) {
                    $res = implode($glue, $arr);
                } else if (is_string($data)) {
                    $res = $data;
                }
            }
        }
        return trim($res);
    }

    /**
     * Generate CSRF TOKEN
     * 
     * @return string $key
     * @return bool $force Force generate new key even if it exists
     * @return string
     */
    public static function generateCsrfToken($key = null, $force = false)
    {
        if ($force || (empty($key) && empty($key = self::getCookie("csrf_key")))) {
            $key = md5(uniqid(app()->request->ip()));
            self::setCookie("csrf_key", $key);
        }
        $date = new DateTime("now", new DateTimeZone("GMT"));
        return sha1(sprintf("%s:%s:%s", $key, app()->request->ip(), $date->format('Y-m-d H')));
    }


    /**
     * Get CSRF Token
     * @return string
     */
    public static function getCsrfToken()
    {
        if (!empty($key = self::getCookie("csrf_key"))) {
            return self::generateCsrfToken($key);
        }
        return null;
    }

    /**
     * CSRF Validation
     * @param string $csrf_token
     * @return boolean
     */
    public static function validateCsrf($csrf_token)
    {
        if ($csrf_token) {
            return $csrf_token == self::getCsrfToken();
        }
        return false;
    }

    /**
     * Get cookie
     * @param string $name
     * @param string $ipAddress
     * @return mixed
     */
    public static function getCookie($name, $ipAddress = NULL)
    {
        $value = $_COOKIE[COOKIE_PREFIX . '_' . $name] ?? null;
        if (!empty($value)) {
            return Crypto::decrypt(ENCRYPTION_KEY . ($ipAddress ? md5($ipAddress) : ''), $value) ?: NULL;
        }
        return null;
    }

    /**
     * Pull cookie - Get and delete cookie
     * @param string $name
     * @param string $ipAddress
     * @return mixed
     */
    public static function pullCookie($name, $ipAddress = NULL)
    {
        $value = self::getCookie($name, $ipAddress);
        if ($value) {
            self::deleteCookie($name);
        }
        return $value;
    }

    /**
     * Set cookie
     *
     * @param string $name
     * @param string $value
     * @param integer $duration
     * @param string $ipAddress
     * @return bool
     */
    public static function setCookie($name, $value, $duration = 3600, $ipAddress = NULL)
    {
        $value = !empty($value) ? Crypto::encrypt(ENCRYPTION_KEY . ($ipAddress ? md5($ipAddress) : ''), $value) : "";
        return setcookie(COOKIE_PREFIX . '_' . $name, $value, time() + $duration, "/");
    }

    /**
     * Delete cookie
     * @param String $name
     * @return bool
     */
    public static function deleteCookie($name)
    {
        return self::setCookie($name, "", 1);
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
