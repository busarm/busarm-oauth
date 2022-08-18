<?php

namespace System\Errors;

use Error;

class SystemError extends Error
{

    /**
     * Error handler
     *
     * @return void
     */
    public function handler()
    {
        app()->reporter->reportException($this);
        $trace = array_map(function ($instance) {
            return [
                'file' => $instance['file'] ?? null,
                'line' => $instance['line'] ?? null,
                'class' => $instance['class'] ?? null,
                'function' => $instance['function'] ?? null,
            ];
        }, $this->getTrace());
        app()->showMessage(500, $this->getMessage(), $this->getCode(), $this->getLine(), $this->getFile(), $trace);
    }
}
