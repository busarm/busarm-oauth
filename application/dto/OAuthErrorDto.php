<?php
namespace Application\Dto;

use ReflectionObject;
use System\Dto\ResponseDto;

class OAuthErrorDto extends ResponseDto
{
    public string|null $error;
    public string|null $error_description;
}
