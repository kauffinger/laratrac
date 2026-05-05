<?php

namespace Laratrac\Laratrac\Deptrac;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Thin wrapper around the deptrac CLI.
 *
 * Resolves the binary, locates (or materializes) a config file, and shells
 * out to deptrac for each subcommand we need. Materialized config files are
 * cleaned up by calling cleanup() — typically inside a try/finally.
 */
class DeptracRunner
{
    protected ?string $materializedConfigPath = null;

    public function __construct(
        protected string $basePath,
        protected ?string $binary = null,
        protected ?string $configPath = null,
    ) {}

    /**
     * Path to the deptrac config file the runner will use.
     *
     * If the host project ships its own `deptrac.yaml` / `deptrac.config.php`
     * we use that. Otherwise we write the in-memory Laravel default to a
     * temp file inside the project root (cleaned up by cleanup()).
     */
    public function configPath(): string
    {
        if ($this->configPath !== null) {
            return $this->configPath;
        }

        foreach (['deptrac.yaml', 'deptrac.yml', 'deptrac.config.php'] as $candidate) {
            $absolute = $this->basePath.'/'.$candidate;
            if (File::isFile($absolute)) {
                return $absolute;
            }
        }

        return $this->materializeDefault();
    }

    /**
     * True when the host project has no deptrac config and we're running
     * against the auto-detected Laravel default.
     */
    public function usingDefaults(): bool
    {
        if ($this->configPath !== null) {
            return false;
        }

        foreach (['deptrac.yaml', 'deptrac.yml', 'deptrac.config.php'] as $candidate) {
            if (File::isFile($this->basePath.'/'.$candidate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run `deptrac analyse` with the given formatter and (optional) output
     * path. Returns the captured stdout for stdout-bound formatters and the
     * file contents for file-bound formatters.
     *
     * Deptrac exits non-zero when violations are found. Since laratrac is a
     * mapping tool (we ship a permissive ruleset), we tolerate exit codes 0
     * and 1; any other code is a real failure.
     */
    public function analyse(string $formatter, ?string $outputPath = null): string
    {
        $args = ['analyse', '--no-progress', '--formatter='.$formatter];
        if ($outputPath !== null) {
            $args[] = '--output='.$outputPath;
        }

        $result = $this->run($args);

        throw_unless(
            in_array($result->exitCode(), [0, 1], true),
            DeptracException::fromResult('analyse', $result),
        );

        return $outputPath !== null && File::exists($outputPath)
            ? File::get($outputPath)
            : $result->output();
    }

    /**
     * Layer names declared in the deptrac config file.
     *
     * Reads YAML directly so the result includes layers that contribute no
     * edges (which the mermaid/graphviz formatters drop). Returns an empty
     * list for PHP-based configs we can't statically parse.
     *
     * @return list<string>
     */
    public function layerNames(): array
    {
        $path = $this->configPath();
        if (! Str::endsWith($path, ['.yaml', '.yml'])) {
            return [];
        }

        $parsed = Yaml::parseFile($path);
        $layers = $parsed['deptrac']['layers'] ?? [];
        $names = [];
        foreach ($layers as $layer) {
            if (is_array($layer) && isset($layer['name'])) {
                $names[] = (string) $layer['name'];
            } elseif (is_string($layer)) {
                $names[] = $layer;
            }
        }

        return $names;
    }

    /**
     * Members of a single layer, as returned by `deptrac debug:layer <name>`.
     *
     * @return list<string>
     */
    public function debugLayer(string $layerName): array
    {
        $result = $this->run(['debug:layer', $layerName]);

        throw_unless(
            $result->exitCode() === 0,
            DeptracException::fromResult("debug:layer {$layerName}", $result),
        );

        return $this->parseDebugLayer($result->output());
    }

    /**
     * Tokens not assigned to any layer. Useful for the agent guide.
     *
     * @return list<string>
     */
    public function debugUnassigned(): array
    {
        $result = $this->run(['debug:unassigned']);

        throw_unless(
            in_array($result->exitCode(), [0, 2], true),
            DeptracException::fromResult('debug:unassigned', $result),
        );

        return collect(preg_split('/\r?\n/', trim($result->output())) ?: [])
            ->reject(fn (string $line) => $line === '')
            ->values()
            ->all();
    }

    /**
     * Remove any temp config the runner materialized.
     *
     * Safe to call multiple times. Always call this in a finally block when
     * the host project doesn't ship its own deptrac config.
     */
    public function cleanup(): void
    {
        if ($this->materializedConfigPath !== null) {
            File::delete($this->materializedConfigPath);
            $this->materializedConfigPath = null;
        }
    }

    /**
     * @param  list<string>  $args
     */
    protected function run(array $args)
    {
        return Process::path($this->basePath)->run(array_merge(
            [$this->binary(), '--config-file='.$this->configPath()],
            $args,
        ));
    }

    protected function binary(): string
    {
        if ($this->binary !== null) {
            return $this->binary;
        }

        $candidate = $this->basePath.'/vendor/bin/deptrac';

        throw_unless(File::isFile($candidate), DeptracException::binaryMissing($candidate));

        return $candidate;
    }

    protected function materializeDefault(): string
    {
        if ($this->materializedConfigPath !== null) {
            return $this->materializedConfigPath;
        }

        // Deptrac resolves the `paths:` entries relative to the config
        // file's location, so we have to drop the temp file inside the
        // project root. Str::random gives a short, collision-free suffix.
        $config = DefaultConfig::forBasePath($this->basePath);
        $tmp = $this->basePath.'/.laratrac-deptrac-'.Str::random(8).'.yaml';
        File::put($tmp, DefaultConfig::toYaml($config));

        return $this->materializedConfigPath = $tmp;
    }

    /**
     * Deptrac's `debug:layer` output is a space-aligned Symfony console
     * table:
     *
     *      ----------------------------- ------------
     *       LayerName                     Token Type
     *      ----------------------------- ------------
     *       Vendor\Pkg\ClassA             class-like
     *       Vendor\Pkg\ClassB             class-like
     *      ----------------------------- ------------
     *
     * We skip everything until the second separator row, then treat the
     * first column of each remaining data row as a token.
     *
     * @return list<string>
     */
    protected function parseDebugLayer(string $output): array
    {
        $tokens = [];
        $separatorsSeen = 0;

        foreach (preg_split('/\r?\n/', $output) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^-+(\s+-+)*$/', $trimmed)) {
                $separatorsSeen++;

                continue;
            }

            if ($separatorsSeen < 2) {
                continue;
            }

            $cells = preg_split('/\s{2,}/', $trimmed) ?: [];
            $candidate = $cells[0] ?? '';
            if ($candidate !== '') {
                $tokens[] = $candidate;
            }
        }

        return array_values(array_unique($tokens));
    }
}
