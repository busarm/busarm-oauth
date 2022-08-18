<?php

namespace System\Exceptions;

class BadRequestException extends HttpException
{
    public function __construct($message = "Invalid request")
    {
        parent::__construct($message, 400);
    }
}
