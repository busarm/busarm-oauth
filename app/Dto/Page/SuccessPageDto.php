<?php

namespace App\Dto\Page;

use Busarm\PhpMini\Dto\BaseDto;

class SuccessPageDto extends BaseDto
{
    public string $email;
    public string|null $msg;
}
