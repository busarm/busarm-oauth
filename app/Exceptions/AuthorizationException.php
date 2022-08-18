<?php

namespace App\Exceptions;

use System\Exceptions\HttpException;

class AuthorizationException extends HttpException
{
    public function __construct($message = "Unauthorized")
    {
        parent::__construct($message, 403);
    }
}
