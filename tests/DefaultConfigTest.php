<?php

use Laratrac\Laratrac\Deptrac\DefaultConfig;
use Symfony\Component\Yaml\Yaml;

it('only emits layers whose directory exists on disk', function () {
    $base = sys_get_temp_dir().'/laratrac-test-'.uniqid();
    mkdir($base.'/app/Http/Controllers', recursive: true);
    mkdir($base.'/app/Models', recursive: true);

    $config = DefaultConfig::forBasePath($base);

    $names = array_map(fn ($l) => $l['name'], $config['deptrac']['layers']);

    expect($names)->toContain('Controller')
        ->and($names)->toContain('Model')
        ->and($names)->not->toContain('Job') // app/Jobs doesn't exist
        ->and($names)->not->toContain('Service');

    cleanupTmp($base);
});

it('emits a permissive ruleset where every layer allows every other', function () {
    $base = sys_get_temp_dir().'/laratrac-test-'.uniqid();
    mkdir($base.'/app/Http/Controllers', recursive: true);
    mkdir($base.'/app/Models', recursive: true);
    mkdir($base.'/app/Services', recursive: true);

    $config = DefaultConfig::forBasePath($base);

    $ruleset = $config['deptrac']['ruleset'];
    expect(array_keys($ruleset))->toEqualCanonicalizing(['Controller', 'Model', 'Service']);
    expect($ruleset['Controller'])->toEqualCanonicalizing(['Model', 'Service']);
    expect($ruleset['Model'])->toEqualCanonicalizing(['Controller', 'Service']);

    cleanupTmp($base);
});

it('serializes to valid YAML that round-trips through symfony/yaml', function () {
    $base = sys_get_temp_dir().'/laratrac-test-'.uniqid();
    mkdir($base.'/app/Models', recursive: true);

    $config = DefaultConfig::forBasePath($base);
    $yaml = DefaultConfig::toYaml($config);

    expect(Yaml::parse($yaml))->toEqual($config);

    cleanupTmp($base);
});

function cleanupTmp(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($dir);
}
