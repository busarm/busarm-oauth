<?php

namespace System;

use Exception;
use System\Interfaces\ErrorReportingInterface;

class ErrorReporter implements ErrorReportingInterface
{
    /**
     * Set up error reporting
     *
     * @return void
     */
    public function setupReporting() {
    }
    
    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @return void
     */
    public function reportError($heading, $message)
    {
        log_error($message);
    }

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException($exception)
    {
        log_exception($exception);
    }
}
