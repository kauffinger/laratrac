<?php

namespace Laratrac\Laratrac\Tests;

use Laratrac\Laratrac\LaratracServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaratracServiceProvider::class,
        ];
    }
}
