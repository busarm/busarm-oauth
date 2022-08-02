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
     * Leave breadcrumbs for issue tracking
     *
     * @param mixed $crumb
     * @param string|null $type
     * @param array $metadata
     * @return void
     */
    public function leaveBreadcrumbs($crumb, string|null $type = null, array $metadata = []);

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