<?php

namespace Laratrac\Laratrac\Deptrac;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds an in-memory deptrac configuration tuned for stock Laravel apps.
 *
 * The class set follows Laravel's default skeleton (`app/Http/Controllers`,
 * `app/Models`, etc.) plus a handful of widely-used community conventions
 * (`app/Services`, `app/Actions`, `app/Repositories`). Layers whose directory
 * does not exist on the host project are dropped so deptrac never sees an
 * empty layer.
 */
class DefaultConfig
{
    /**
     * Layers laratrac auto-detects, keyed by display name.
     *
     * @return array<string, array<string, string>>
     */
    public static function candidateLayers(): array
    {
        return [
            'Controller' => ['directory' => 'app/Http/Controllers'],
            'Middleware' => ['directory' => 'app/Http/Middleware'],
            'Request' => ['directory' => 'app/Http/Requests'],
            'Resource' => ['directory' => 'app/Http/Resources'],
            'Model' => ['directory' => 'app/Models'],
            'Job' => ['directory' => 'app/Jobs'],
            'Event' => ['directory' => 'app/Events'],
            'Listener' => ['directory' => 'app/Listeners'],
            'Mail' => ['directory' => 'app/Mail'],
            'Notification' => ['directory' => 'app/Notifications'],
            'Policy' => ['directory' => 'app/Policies'],
            'Provider' => ['directory' => 'app/Providers'],
            'Console' => ['directory' => 'app/Console'],
            'Service' => ['directory' => 'app/Services'],
            'Action' => ['directory' => 'app/Actions'],
            'Repository' => ['directory' => 'app/Repositories'],
            'Observer' => ['directory' => 'app/Observers'],
            'Rule' => ['directory' => 'app/Rules'],
            'Cast' => ['directory' => 'app/Casts'],
            'Exception' => ['directory' => 'app/Exceptions'],
        ];
    }

    /**
     * Build a deptrac config array for the given Laravel app root.
     *
     * Only layers whose directory exists on disk are included.
     *
     * @return array{deptrac: array<string, mixed>}
     */
    public static function forBasePath(string $basePath): array
    {
        $basePath = rtrim($basePath, '/');
        $layers = [];

        foreach (self::candidateLayers() as $name => $spec) {
            $absolute = $basePath.'/'.$spec['directory'];
            if (File::isDirectory($absolute)) {
                $layers[] = [
                    'name' => $name,
                    'collectors' => [
                        ['type' => 'directory', 'value' => preg_quote($spec['directory'], '#').'/.*'],
                    ],
                ];
            }
        }

        $layerNames = array_map(fn (array $layer) => $layer['name'], $layers);

        return [
            'deptrac' => [
                'paths' => ['app/'],
                'layers' => $layers,
                'ruleset' => self::permissiveRuleset($layerNames),
            ],
        ];
    }

    /**
     * Render a deptrac config array as YAML.
     *
     * @param  array<string, mixed>  $config
     */
    public static function toYaml(array $config): string
    {
        return Yaml::dump($config, inline: 6, indent: 2);
    }

    /**
     * Every layer is allowed to depend on every other layer.
     *
     * laratrac is a mapping tool, not an enforcement tool — we want deptrac
     * to compute the dependency graph without flagging anything as a
     * violation.
     *
     * @param  list<string>  $layerNames
     * @return array<string, list<string>>
     */
    protected static function permissiveRuleset(array $layerNames): array
    {
        $ruleset = [];
        foreach ($layerNames as $name) {
            $ruleset[$name] = array_values(array_filter(
                $layerNames,
                fn (string $other) => $other !== $name,
            ));
        }

        return $ruleset;
    }
}
