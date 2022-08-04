<?php

namespace System;

use Exception;

class HttpException extends Exception
{
    /**
     * Exception handler
     *
     * @return void
     */
    public function handler()
    {
        $trace = array_map(function ($instance) {
            return [
                'file' => $instance['file'] ?? null,
                'line' => $instance['line'] ?? null,
                'class' => $instance['class'] ?? null,
                'function' => $instance['function'] ?? null,
            ];
        }, $this->getTrace());
        app()->showMessage($this->getCode() >= 400 ? $this->getCode() : 500, $this->getMessage(), $this->getLine(), $this->getFile(), $trace);
    }
}
