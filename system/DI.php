<?php

namespace System;

use Closure;
use Exception;
use System\Dto\RequestDto;
use System\Interfaces\SingletonInterface;

/**
 * Dependency Injector
 * @source https://www.php.net/manual/en/reflectionnamedtype.getname.php#122909
 */
class DI
{
    /**
     * Instantiate class with dependencies
     *
     * @param string $class
     * @param bool $caching Allow instantiating using app singletons
     * @return object
     */
    public static function instantiate($class, $caching = true)
    {
        if ($caching && ($singleton = app()->getSingleton($class))) return $singleton;
        else if (method_exists($class, '__construct')) $instance = new $class(...self::resolveMethodDependencies($class, '__construct'));
        else $instance = new $class;
        // Add instance as singleton is supported
        if ($caching && ($instance instanceof SingletonInterface)) {
            app()->addSingleton($class, $instance);
        }
        return $instance;
    }

    /**
     * Resolve dependendies for class method
     *
     * @param string $class
     * @param string $method
     * @return array
     */
    public static function resolveMethodDependencies($class, $method)
    {
        $reflection = new \ReflectionMethod($class, $method);
        return self::resolveDependencies($reflection->getParameters());
    }

    /**
     * Resolve dependendies for class method
     *
     * @param Closure $callable
     * @return array
     */
    public static function resolveCallableDependencies(Closure $callable)
    {
        $reflection = new \ReflectionFunction($callable);
        return self::resolveDependencies($reflection->getParameters());
    }

    /**
     * Resolve dependendies
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     */
    public static function resolveDependencies(array $parameters)
    {
        $params = [];
        foreach ($parameters as $param) {
            if ($type = $param->getType()) {
                // If type is an interface - Get app interface binding
                if (interface_exists($type->getName())) {
                    if (!($className = app()->getBinding($type->getName()))) {
                        throw new Exception("No interface binding exists for " . $type->getName());
                    }
                }
                // If type can't be instantiated (e.g scalar types) - skip loop
                else if (!$type || !self::instatiatable($type)) continue;
                // Get class name
                else $className = $type->getName();
                // Resolve dependencies for type
                $instance = self::instantiate($className);
                // If type is an Request Dto - Parse request
                if ($instance instanceof RequestDto) {
                    $instance->load(app()->request->getRequestList(), true);
                }
                $params[] = $instance;
            }
        }
        return $params;
    }

    /**
     * Check if type can be instantiated
     *
     * @param string $type
     * @return bool
     */
    protected static function instatiatable($type)
    {
        // Add conditon if something is leftout.
        // This is to ensure that the type is existing class.
        return $type != 'Closure' && !is_callable($type) && class_exists($type);
    }
}
