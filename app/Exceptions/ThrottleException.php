<?php

namespace App\Exceptions;

use System\HttpException;

class ThrottleException extends HttpException
{
    public function __construct($message = "Too many requests")
    {
        parent::__construct($message, 429);
    }
}
