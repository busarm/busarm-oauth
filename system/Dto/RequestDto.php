<?php

namespace System\Dto;

use ReflectionObject;

class RequestDto extends BaseDto
{
    /**
     * Load dto with array
     *
     * @param array|object|null $data
     * @return static
     */
    public static function with(array|object|null $data): static
    {
        $response = new static();
        if ($data) $response->load((array)$data, true);
        return $response;
    }
}
