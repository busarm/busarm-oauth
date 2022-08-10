<?php

namespace App\Dto\Request;

use System\Dto\BaseDto;

class CreateUserDto extends BaseDto
{
    public string $name;
    public string $email;
    public string $phone;
    public string $dial_code;
    public string $scope;
    public string $password;
    public bool $force = false;
}
