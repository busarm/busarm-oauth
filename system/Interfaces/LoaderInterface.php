<?php

namespace System\Interfaces;

interface LoaderInterface
{
    public function view(string $path, $params = [], $return = true): ?string;
    public function config(string $path);
}