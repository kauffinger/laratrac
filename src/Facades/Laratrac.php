<?php

namespace Laratrac\Laratrac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laratrac\Laratrac\Laratrac
 */
class Laratrac extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Laratrac\Laratrac\Laratrac::class;
    }
}
