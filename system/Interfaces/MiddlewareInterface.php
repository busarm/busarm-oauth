<?php

namespace System\Interfaces;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 28/7/2022
 * Time: 5:22 PM
 */
interface MiddlewareInterface
{
    public function handle(Callable $next = null): mixed;
}