<?php

namespace System;

use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 21/5/2022
 * Time: 1:17 AM
 */
class Logger
{
    private $logger;

    /**
     * @param int $verbosity @see `ConsoleOutput::VERBOSITY_*`
     */
    public function __construct($verbosity = ConsoleOutput::VERBOSITY_NORMAL)
    {
        $this->logger = new ConsoleLogger(new ConsoleOutput($verbosity, true));
    }

    /**
     * Log error
     *
     * @param string $level @see `Psr\Log\LogLevel`
     * @param mixed $message
     * @param array $errorContext
     * @return void
     */
    public function log($level, $message, $context = [])
    {
        if (is_array($message) || is_object($message))
            $message = json_encode($message, JSON_PRETTY_PRINT);
        $this->logger->log($level, $message, $context);
    }

    /**
     * Log error
     *
     * @param mixed $message
     * @param array $context
     * @return void
     */
    public function logError($message, $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Log info
     *
     * @param mixed $message
     * @return void
     */
    public function logInfo($message)
    {
        $this->log(LogLevel::INFO, $message);
    }

    /**
     * Log debug
     *
     * @param mixed $message
     * @return void
     */
    public function logDebug($message)
    {
        $this->log(LogLevel::DEBUG, $message);
    }

    /**
     * Log Warning
     *
     * @param mixed $message
     * @param array $errorContext
     * @return void
     */
    public function logWarning($message)
    {
        $this->log(LogLevel::WARNING, $message);
    }
}
