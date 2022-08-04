<?php

namespace App\Dto\Pages;

use System\Dto\RequestDto;

class FailedPageDto extends RequestDto
{
    public string $msg;
    public string|null $desc;
}
