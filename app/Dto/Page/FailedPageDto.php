<?php

namespace App\Dto\Page;

use Busarm\PhpMini\Dto\BaseDto;

class FailedPageDto extends BaseDto
{
    public string $msg;
    public string|null $desc;
}
