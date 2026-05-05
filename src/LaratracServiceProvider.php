<?php

namespace Laratrac\Laratrac;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Laratrac\Laratrac\Commands\LaratracCommand;

class LaratracServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laratrac')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laratrac_table')
            ->hasCommand(LaratracCommand::class);
    }
}
