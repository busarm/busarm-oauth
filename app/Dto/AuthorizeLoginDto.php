<?php

namespace App\Dto;

use System\Dto\RequestDto;

class AuthorizeLoginDto extends RequestDto
{
    public string $username;
    public string $password;
    public string $csrf_token;
    public string|null $redirect_url;
    public string|null $recaptcha_token;
}
