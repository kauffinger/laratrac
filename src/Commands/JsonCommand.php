<?php

namespace Laratrac\Laratrac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laratrac\Laratrac\Deptrac\DeptracRunner;
use Laratrac\Laratrac\Deptrac\MetadataAssembler;
use Laratrac\Laratrac\Deptrac\MetadataMode;

class JsonCommand extends Command
{
    public $signature = 'laratrac:json
        {--out= : Path to write the JSON to (defaults to .laratrac/metadata.json)}
        {--stdout : Print to stdout instead of writing to disk}
        {--mode=layers_with_members : One of: graph_only, layers_with_members}';

    public $description = 'Emit a JSON metadata file that helps AI agents understand the app.';

    public function handle(): int
    {
        $mode = MetadataMode::tryFrom((string) $this->option('mode'));

        if ($mode === null) {
            $this->components->error("Unknown --mode={$this->option('mode')}. Expected: graph_only or layers_with_members.");

            return self::FAILURE;
        }

        $runner = new DeptracRunner(base_path());

        try {
            $metadata = (new MetadataAssembler($runner))->assemble($mode);
        } finally {
            $runner->cleanup();
        }

        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($this->option('stdout')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $out = (string) ($this->option('out') ?: base_path('.laratrac/metadata.json'));
        File::ensureDirectoryExists(dirname($out));
        File::put($out, $json);

        $this->components->info("Wrote metadata to {$out}");
        $this->components->twoColumnDetail('Layers', (string) count($metadata['layers']));
        $this->components->twoColumnDetail('Edges', (string) count($metadata['dependencies']));
        $this->components->twoColumnDetail('Mode', $mode->value);
        $this->components->twoColumnDetail('Source', $metadata['using_defaults'] ? 'auto-detected' : 'deptrac.yaml');

        return self::SUCCESS;
    }
}
