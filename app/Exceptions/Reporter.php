<?php

namespace App\Exceptions;

use System\ErrorReporter;

class Reporter extends ErrorReporter
{
    /** @var \Bugsnag\Client */
    private $bugsnag;

    /**
     * Set up error handling
     *
     * @return void
     */
    public function setup()
    {
        if ($key = BUGSNAG_KEY) {
            $this->bugsnag = \Bugsnag\Client::make($key);
            $this->bugsnag->setReleaseStage(ENVIRONMENT);
            $this->bugsnag->setAppType(is_cli() ? "Console" : "HTTP");
            \Bugsnag\Handler::register($this->bugsnag);
        }
    }

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
        if (!empty($this->bugsnag)) {
            $this->bugsnag->leaveBreadcrumb($crumb, $type, $metadata);
        }
        parent::leaveBreadcrumbs($crumb, $type, $metadata);
    }

    /**
     * Report Error
     *
     * @param string $heading
     * @param string $message
     * @return void
     */
    public  function reportError($heading, $message)
    {
        if (!empty($this->bugsnag)) {
            $this->bugsnag->notifyError($heading, $message);
        }
        parent::reportError($heading, $message);
    }

    /**
     * Report Exception
     *
     * @param \Throwable $exception
     * @return void
     */
    public function reportException($exception)
    {
        if (!empty($this->bugsnag)) {
            $this->bugsnag->notifyException($exception);
        }
        parent::reportException($exception);
    }
}
