<?php

namespace Application\Dto;

use System\Dto\RequestDto;

class UpdateClientDto extends RequestDto
{
    public string|null $client_name;
    public string|null $client_secret;
    public string|null $grant_types;
    public string|null $redirect_uri;
    public string|null $scope;
    public string|null $remove_scope;
}
