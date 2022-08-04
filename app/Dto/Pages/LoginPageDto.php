<?php

namespace App\Dto\Pages;

use System\Dto\RequestDto;

class LoginPageDto extends RequestDto
{
    public string|null $msg;
    public string $action = '';
    public string $csrf_token;
    public string $redirect_url;
}