<?php

namespace App\Dto\Pages;

use System\Dto\RequestDto;

class SuccessPageDto extends RequestDto
{
    public string $email;
    public string|null $msg;
}
