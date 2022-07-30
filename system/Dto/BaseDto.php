<?php

namespace System\Dto;

use ReflectionNamedType;
use ReflectionObject;
use ReflectionType;
use ReflectionUnionType;

abstract class BaseDto
{
    /**
     * Get array response data
     * @param bool $trim Remove NULL properties
     * @return array
     */
    public function toArray($trim = true)
    {
        $result = [];
        $reflectClass = new ReflectionObject($this);
        foreach ($reflectClass->getProperties() as $property) {
            if ((!$trim || isset($this->{$property->getName()})) && $property->isInitialized($this)) {
                $result[$property->getName()] = $this->{$property->getName()};
            }
        }
        return $result;
    }

    /**
     * Parse object type
     *
     * @param ReflectionType $type
     * @param mixed $data
     * @return mixed
     */
    public static function parseType($type, $data)
    {
        if ($type instanceof ReflectionUnionType) {
            $type = self::getType($data);
        }
        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName();
        }

        $type = strtolower($type);

        if ($type == 'string') {
            $data = is_array($data) || is_object($data) ? json_encode($data) : (string) $data;
        } else if ($type == 'int' || $type == 'integer') {
            $data = intval($data);
        } else if ($type == 'bool' || $type == 'boolean') {
            $data = boolval($data);
        } else if ($type == 'float') {
            $data = floatval($data);
        } else if ($type == 'double') {
            $data = doubleval($data);
        } else if ($type == 'array') {
            $data = is_string($data) ? json_decode($data, true) : (array) $data;
        } else if ($type == 'object') {
            $data = is_string($data) ? json_decode($data) : (object) $data;
        }

        return $data;
    }

    /**
     * Get data type
     *
     * @param interger $data
     * @return string
     */
    public static function getType($data)
    {
        if (is_int($data) || is_numeric($data)) return 'integer';
        if ($data === 'true' || $data === 'false' || is_bool($data)) return 'bool';
        if (is_array($data)) return 'array';
        if (is_object($data)) return 'object';
        if (is_string($data)) return 'string';
        return 'mixed';
    }

    /**
     * Load response with object
     *
     * @param object|null $data
     * @return static
     */
    abstract static function withObject(object $data = null): static;

    /**
     * Load response with array
     *
     * @param object|null $data
     * @return static
     */
    abstract static function withArray(array $data = null): static;
}
