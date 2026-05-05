<?php

namespace Laratrac\Laratrac;

use Illuminate\Support\ServiceProvider;
use Laratrac\Laratrac\Commands\GuideCommand;
use Laratrac\Laratrac\Commands\InitCommand;
use Laratrac\Laratrac\Commands\JsonCommand;
use Laratrac\Laratrac\Commands\MermaidCommand;

class LaratracServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GuideCommand::class,
                InitCommand::class,
                JsonCommand::class,
                MermaidCommand::class,
            ]);
        }
    }
}
