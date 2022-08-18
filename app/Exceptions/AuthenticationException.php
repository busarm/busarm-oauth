<?php

namespace App\Exceptions;

use System\Exceptions\HttpException;

class AuthenticationException extends HttpException
{
    public function __construct($message = "Unauthenticated")
    {
        parent::__construct($message, 401);
    }
}
