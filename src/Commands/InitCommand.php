<?php

namespace Laratrac\Laratrac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laratrac\Laratrac\Deptrac\DefaultConfig;

class InitCommand extends Command
{
    public $signature = 'laratrac:init
        {--force : Overwrite an existing deptrac.yaml}';

    public $description = 'Materialize a Laravel-tuned deptrac.yaml at the project root.';

    public function handle(): int
    {
        $target = base_path('deptrac.yaml');

        if (File::exists($target) && ! $this->option('force')) {
            $this->components->error("deptrac.yaml already exists at {$target}. Pass --force to overwrite.");

            return self::FAILURE;
        }

        $config = DefaultConfig::forBasePath(base_path());
        File::put($target, DefaultConfig::toYaml($config));

        $this->components->info('Wrote deptrac.yaml with '.count($config['deptrac']['layers']).' auto-detected Laravel layers.');
        $this->components->bulletList([
            'Edit it freely — laratrac picks it up automatically on the next run.',
            'Run `php artisan laratrac:guide` for guidance an agent can follow.',
        ]);

        return self::SUCCESS;
    }
}
