<?php

namespace Laratrac\Laratrac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laratrac\Laratrac\Deptrac\DeptracRunner;

class MermaidCommand extends Command
{
    public $signature = 'laratrac:mermaid
        {--out= : Path to write the .mmd file to (defaults to .laratrac/diagram.mmd)}
        {--stdout : Print to stdout instead of writing to disk}';

    public $description = 'Render a Mermaid flowchart of the app\'s layer dependencies.';

    public function handle(): int
    {
        $runner = new DeptracRunner(base_path());

        try {
            $mermaid = $runner->analyse('mermaidjs');
        } finally {
            $runner->cleanup();
        }

        if ($this->option('stdout')) {
            $this->line($mermaid);

            return self::SUCCESS;
        }

        $out = (string) ($this->option('out') ?: base_path('.laratrac/diagram.mmd'));
        File::ensureDirectoryExists(dirname($out));
        File::put($out, $mermaid);

        $this->components->info("Wrote diagram to {$out}");

        return self::SUCCESS;
    }
}
