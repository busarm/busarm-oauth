<?php

namespace App\Exceptions;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Busarm\PhpMini\ErrorReporter;

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
            $this->bugsnag->setReleaseStage(app()->env);
            $this->bugsnag->setAppType(is_cli() ? "Console" : "HTTP");
            \Bugsnag\Handler::register($this->bugsnag);
        }
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
        if (!empty($this->bugsnag)) {
            $this->bugsnag->leaveBreadcrumb($title, Breadcrumb::ERROR_TYPE, $metadata);
        }
        parent::leaveCrumbs($title, $metadata);
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
        if (!empty($this->bugsnag)) {
            $this->bugsnag->notifyError($heading, $message);
        }
        parent::reportError($heading, $message, $file, $line);
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
