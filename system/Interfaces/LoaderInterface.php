<?php

namespace System\Interfaces;

interface LoaderInterface
{
    public function view(string $path, $params = [], $return = false): ?string;
    public function config(string $path);
}