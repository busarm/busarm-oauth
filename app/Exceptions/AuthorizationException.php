<?php

namespace App\Exceptions;

use Busarm\PhpMini\Exceptions\HttpException;

class AuthorizationException extends HttpException
{
    public function __construct($message = "Unauthorized")
    {
        parent::__construct($message, 403);
    }
}
