<?php

namespace App\Dto\Page;

use Busarm\PhpMini\Dto\BaseDto;

class AuthorizePageDto extends BaseDto
{
    public string|null $client_name;
    public string|null $org_name;
    public string|null $user_name;
    public string|null $user_email;
    public array $scopes;
    public string $action;
}
