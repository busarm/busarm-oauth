<?php

namespace App\Dto\Page;

use Busarm\PhpMini\Dto\BaseDto;

class LoginPageDto extends BaseDto
{
    public string|null $msg;
    public string $action = '';
    public string $csrf_token;
    public string $redirect_url;
}