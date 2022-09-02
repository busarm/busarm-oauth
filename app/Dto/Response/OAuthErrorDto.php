<?php
namespace App\Dto\Response;

use Busarm\PhpMini\Dto\ResponseDto;

class OAuthErrorDto extends ResponseDto
{
    public string|null $error;
    public string|null $error_description;
}
