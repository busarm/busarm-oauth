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
     * @return bool
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
