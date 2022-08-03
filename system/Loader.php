<?php

namespace System;

use Exception;
use System\Interfaces\LoaderInterface;

class Loader implements LoaderInterface
{
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
            ob_start();
            if (!empty($vars)) extract($vars);
            include $path;
            $content = ob_get_contents();
            ob_end_clean();
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
     */
    public function config($path)
    {
        $path = APP_BASE_PATH . app()->path . DIRECTORY_SEPARATOR . app()->configPath . DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($path)) {
            require_once $path;
        } else {
            throw new Exception("Loader Error: Config file '$path' not found");
        }
    }
}
