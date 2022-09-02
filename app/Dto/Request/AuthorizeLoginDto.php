<?php

namespace App\Dto\Request;

use Busarm\PhpMini\Dto\BaseDto;

class AuthorizeLoginDto extends BaseDto
{
    public string $username;
    public string $password;
    public string $csrf_token;
    public string|null $redirect_url;
    public string|null $recaptcha_token;
}
