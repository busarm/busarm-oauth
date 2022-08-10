<?php

namespace App\Dto\Request;

use System\Dto\BaseDto;

class CreateClientDto extends BaseDto
{
    public int|string $org_id;
    public string $client_name;
    public string $grant_types;
    public string|null $redirect_uri;
    public string|null $user_id;
    public string $scope;
}
