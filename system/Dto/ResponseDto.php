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
    public string|null $file_path;
    /** @var array */
    public array|null $backtrace;
    
    /**
     * Load response with object
     *
     * @param object|null $data
     * @return static
     */
    static function withObject(object $data = null): static
    {
        $response = new static();
        if ($data) {
            $reflectClass = new ReflectionObject($response);
            foreach ($reflectClass->getProperties() as $property) {
                if (isset($data->{$property->getName()})) {
                    $content = $data->{$property->getName()};
                    $response->{$property->getName()} = self::parseType($property->getType() || self::getType($content), $content);
                }
            }
        }
        return $response;
    }

    /**
     * Load response with array
     *
     * @param object|null $data
     * @return static
     */
    static function withArray(array $data = null): static
    {
        $response = new static();
        if ($data) {
            $reflectClass = new ReflectionObject($response);
            foreach ($reflectClass->getProperties() as $property) {
                if (isset($data[$property->getName()])) {
                    $response->{$property->getName()} = self::parseType($property->getType(), $data[$property->getName()]);
                }
            }
        }
        return $response;
    }
}
