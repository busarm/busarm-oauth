<?php

namespace App\Exceptions;

use Busarm\PhpMini\Exceptions\HttpException;

class AuthenticationException extends HttpException
{
    public function __construct($message = "Unauthenticated")
    {
        parent::__construct($message, 401);
    }
}
