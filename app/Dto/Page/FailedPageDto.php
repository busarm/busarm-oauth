<?php

namespace App\Dto\Page;

use System\Dto\BaseDto;

class FailedPageDto extends BaseDto
{
    public string $msg;
    public string|null $desc;
}
