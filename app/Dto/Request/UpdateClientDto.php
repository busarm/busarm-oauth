<?php

namespace App\Dto\Request;

use System\Dto\BaseDto;

class UpdateClientDto extends BaseDto
{
    public string|null $client_name;
    public string|null $client_secret;
    public string|null $grant_types;
    public string|null $redirect_uri;
    public string|null $scope;
    public string|null $remove_scope;
}
