<?php

namespace System\Interfaces;

interface LoggerInterface
{
    /**
     * Log error
     *
     * @param string $level @see `Psr\Log\LogLevel`
     * @param mixed $message
     * @param array $errorContext
     * @return void
     */
    public function log($level, $message, $context = []);

    /**
     * Log error
     *
     * @param mixed $message
     * @param array $context
     * @return void
     */
    public function logError($message, $context = []);

    /**
     * Log info
     *
     * @param mixed $message
     * @return void
     */
    public function logInfo($message);

    /**
     * Log debug
     *
     * @param mixed $message
     * @return void
     */
    public function logDebug($message);

    /**
     * Log Warning
     *
     * @param mixed $message
     * @param array $errorContext
     * @return void
     */
    public function logWarning($message);
}