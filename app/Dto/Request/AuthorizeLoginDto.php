<?php

namespace App\Dto\Request;

use System\Dto\BaseDto;

class AuthorizeLoginDto extends BaseDto
{
    public string $username;
    public string $password;
    public string $csrf_token;
    public string|null $redirect_url;
    public string|null $recaptcha_token;
}
