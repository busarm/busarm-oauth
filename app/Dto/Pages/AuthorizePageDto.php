<?php

namespace App\Dto\Pages;

use System\Dto\RequestDto;

class AuthorizePageDto extends RequestDto
{
    public string|null $client_name;
    public string|null $org_name;
    public string|null $user_name;
    public string|null $user_email;
    public array $scopes;
    public string $action;
}
