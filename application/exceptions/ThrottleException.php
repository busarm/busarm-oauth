<?php

namespace Application\Exceptions;

use System\HttpException;

class ThrottleException extends HttpException
{
    public function __construct($message = "Too many requests")
    {
        parent::__construct(429, $message);
        $this->title = "Too many requests";
    }
}
