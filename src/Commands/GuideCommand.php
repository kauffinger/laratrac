<?php

namespace Laratrac\Laratrac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GuideCommand extends Command
{
    public $signature = 'laratrac:guide
        {--out= : Path to write the guide to (defaults to .laratrac/AGENT_GUIDE.md)}
        {--stdout : Print to stdout instead of writing to disk}';

    public $description = 'Print the laratrac customization guide intended for the user\'s coding agent.';

    public function handle(): int
    {
        $guide = File::get(__DIR__.'/stubs/agent-guide.md');

        if ($this->option('stdout')) {
            $this->line($guide);

            return self::SUCCESS;
        }

        $out = (string) ($this->option('out') ?: base_path('.laratrac/AGENT_GUIDE.md'));
        File::ensureDirectoryExists(dirname($out));
        File::put($out, $guide);

        $this->components->info("Wrote agent guide to {$out}");
        $this->line('Point your coding agent at it: "Read '.$out.' and customize laratrac for this project."');

        return self::SUCCESS;
    }
}
