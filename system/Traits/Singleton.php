<?php

namespace System\Traits;

use System\DI;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 11:21 AM
 */
trait Singleton
{
    /**
     * @return static
     */
    public static function getInstance(): static
    {
        return DI::instantiate(static::class, true);
    }
}
