<?php

namespace System\Interfaces;

interface ErrorReportingInterface
{
    /**
     * Set up error reporting
     *
     * @return void
     */
    public function setupReporting();

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @return void
     */
    public function reportError($heading, $message);

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException($exception);
}