<?php
namespace Application\Dto;

use ReflectionObject;
use System\Dto\ResponseDto;

class OAuthErrorDto extends ResponseDto
{
    /**  @var bool */
    public string|null $error;
    /** @var string */
    public string|null $error_description;
}
