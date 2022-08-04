<?php

namespace System;

use Exception;
use System\Interfaces\LoaderInterface;

class Loader implements LoaderInterface
{

    /**
     * Fetches print result intead of sending it to the output buffer
     *
     * @param string $path
     * @param array $data
     * @return string The rendered content
     */
    public static function load($path,  $data = null)
    {
        ob_start();
        if (is_array($data)) extract($data);
        include $path;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Load View File
     * @param $path
     * @param array $vars
     * @param bool $return
     * @return string
     * @throws Exception
     */
    public function view($path, $vars = array(), $return = false): ?string
    {
        $path = APP_BASE_PATH . app()->path . DIRECTORY_SEPARATOR . app()->viewPath . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            $content = $this->load($path, $vars);
            if ($return) return $content;
            else echo $content;
        } else {
            if ($return) return null;
            else throw new Exception("Loader Error: View file '$path' not found");
        }
        return null;
    }

    /**
     * Load Config File
     * @param $path
     * @throws Exception
     * @return mixed
     */
    public function config($path)
    {
        $path = APP_BASE_PATH . app()->path . DIRECTORY_SEPARATOR . app()->configPath . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            return require_once $path;
        } else {
            throw new Exception("Loader Error: Config file '$path' not found");
        }
    }
}
