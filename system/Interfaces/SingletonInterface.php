<?php

namespace System\Interfaces;

interface SingletonInterface
{
    public static function getInstance(): static;
}