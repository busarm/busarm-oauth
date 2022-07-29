<?php

namespace System;

use Exception;
use System\Interfaces\LoaderInterface;

class Loader implements LoaderInterface
{
    const VIEW_PATH =  FCPATH . "application/views/";
    const CONFIG_PATH =  FCPATH . "application/configs/";

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
        $path = self::VIEW_PATH . $path . '.php';
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
            else throw new Exception("View file '$path' not found");
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
        $path = self::CONFIG_PATH . $path . '.php';
        if (file_exists($path)) {
            require_once $path;
        } else {
            throw new Exception("Config file '$path' not found");
        }
    }
}
