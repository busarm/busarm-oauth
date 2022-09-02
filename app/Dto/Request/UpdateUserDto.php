<?php

namespace App\Dto\Request;

use Busarm\PhpMini\Dto\BaseDto;

class UpdateUserDto extends BaseDto
{
    public string|null $name;
    public string|null $email;
    public string|null $phone;
    public string|null $dial_code;
    public string|null $password;
    public string|null $scope;
    public string|null $remove_scope;
}
