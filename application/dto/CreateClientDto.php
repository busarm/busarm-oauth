<?php

namespace Application\Dto;

use System\Dto\RequestDto;

class CreateClientDto extends RequestDto
{
    public int|string $org_id;
    public string $client_name;
    public string $grant_types;
    public string|null $redirect_uri;
    public string|null $user_id;
    public string $scope;
}
