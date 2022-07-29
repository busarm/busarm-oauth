<?php

namespace System;

use Exception;

class HttpException extends Exception
{
    protected $title;

    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
        $this->title = Response::$statusTexts[$this->code] ?? null;
    }

    public function getTitle()
    {
        return $this->title;
    }

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
        app()->showMessage($this->getCode() >= 400 ? $this->getCode() : 500, false, $this->getTitle(), $this->getMessage(), $this->getLine(), $this->getFile(), $trace);
    }
}
