<?php

namespace App\Exceptions;

use System\Exceptions\HttpException;

class ThrottleException extends HttpException
{
    public function __construct($message = "Too many requests")
    {
        parent::__construct($message, 429);
    }
}
