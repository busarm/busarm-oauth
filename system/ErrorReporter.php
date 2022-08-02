<?php

namespace System;

use Exception;
use System\Interfaces\ErrorReportingInterface;

class ErrorReporter implements ErrorReportingInterface
{
    protected array $breadCrumbs = [];

    /**
     * Set up error reporting
     *
     * @return void
     */
    public function setupReporting() {}
    
    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $crumb
     * @param string|null $type
     * @param array $metadata
     * @return void
     */
    public function leaveBreadcrumbs($crumb, string|null $type = null, array $metadata = [])
    {
        $this->breadCrumbs[] = [
            'crumb' => $crumb,
            'type' => $crumb,
            'metadata' => $crumb,
        ];
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
        log_error($this->breadCrumbs);
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
        log_error($this->breadCrumbs);
        log_exception($exception);
    }
}
