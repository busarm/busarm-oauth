<?php

namespace System\Interfaces;

interface SingletonInterface
{
    public function setInstance();
    public static function getInstance(...$params): static;
}