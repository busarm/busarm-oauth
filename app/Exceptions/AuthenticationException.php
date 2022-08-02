<?php

namespace App\Exceptions;

use System\HttpException;

class AuthenticationException extends HttpException
{
    public function __construct($message = "Unauthenticated")
    {
        parent::__construct(401, $message);
    }
}
