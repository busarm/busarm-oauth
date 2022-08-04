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
    public function setupReporting()
    {
    }

    /**
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $title
     * @param array $metadata
     * @return void
     */
    public function leaveCrumbs($title, array $metadata = [])
    {
        $this->breadCrumbs[] = [
            'Title' => $title,
            'Metadata' => $metadata,
        ];
    }

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     */
    public function reportError($heading, $message, $file = null, $line = null)
    {
        $contexts = [];
        if ($file) $contexts[] = $file . ':' . ($line ?? 0);
        log_debug([
            'Crumbs' => $this->breadCrumbs,
            'Contexts' => $contexts,
        ]);
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
        $contexts = array_map(function ($instance) {
            return ($instance['file'] ?? $instance['class'] ?? '') . ':' . ($instance['line'] ?? '0');
        }, $exception->getTrace());
        log_debug([
            'Crumbs' => $this->breadCrumbs,
            'Contexts' => $contexts,
        ]);
        log_exception($exception);
    }
}
