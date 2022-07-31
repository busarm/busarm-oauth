<?php

namespace Application\Dto;

use System\Dto\RequestDto;

class CreateUserDto extends RequestDto
{
    public string $name;
    public string $email;
    public string $phone;
    public string $dial_code;
    public string $scope;
    public string $password;
    public bool $force = false;
}
