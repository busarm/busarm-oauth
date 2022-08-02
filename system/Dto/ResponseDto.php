<?php

namespace System\Dto;

use ReflectionObject;

class ResponseDto extends BaseDto
{
    /**  @var bool */
    public bool $success;
    /** @var string */
    public string|null $message;
    /** @var object */
    public object|array|null $data;
    /** @var string */
    public string|null $env;
    /** @var string */
    public string|null $ip;
    /** @var string */
    public string|null $version;
    /** @var string */
    public string|null $line;
    /** @var string */
    public string|null $file;
    /** @var array */
    public array|null $backtrace;
    /** @var int */
    public int|null $duration;

    /**
     * Load dto with array
     *
     * @param array|object|null $data
     * @return static
     */
    public static function with(array|object|null $data): static
    {
        $response = new static();
        if ($data) $response->load((array)$data);
        return $response;
    }
}
