<?php
namespace App\Dto\Response;

use System\Dto\ResponseDto;

class OAuthErrorDto extends ResponseDto
{
    public string|null $error;
    public string|null $error_description;
}
