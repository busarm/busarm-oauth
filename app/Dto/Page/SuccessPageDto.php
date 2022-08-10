<?php

namespace App\Dto\Page;

use System\Dto\BaseDto;

class SuccessPageDto extends BaseDto
{
    public string $email;
    public string|null $msg;
}
