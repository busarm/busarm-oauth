<?php

namespace System\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct($message = "Not found")
    {
        parent::__construct($message, 404);
    }
}
