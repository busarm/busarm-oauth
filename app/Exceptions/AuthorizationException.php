<?php

namespace App\Exceptions;

use System\HttpException;

class AuthorizationException extends HttpException
{
    public function __construct($message = "Unauthorized")
    {
        parent::__construct(403, $message);
    }
}
