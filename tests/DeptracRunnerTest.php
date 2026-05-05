<?php

use Illuminate\Support\Str;
use Laratrac\Laratrac\Deptrac\DeptracRunner;

/**
 * End-to-end tests that invoke the real deptrac binary against laratrac's
 * own source tree. They double as a smoke-test that our subprocess plumbing
 * (binary path, config flag ordering, output parsing) hasn't drifted from
 * the deptrac CLI.
 */
beforeEach(function () {
    // The fixture lives inside the project root because deptrac resolves
    // `paths:` relative to the config file's location.
    $this->fixture = dirname(__DIR__).'/.laratrac-runner-test-'.Str::random(8).'.yaml';
    file_put_contents($this->fixture, <<<'YAML'
        deptrac:
          paths:
            - src/
          layers:
            - name: Deptrac
              collectors:
                - type: directory
                  value: src/Deptrac/.*
            - name: Commands
              collectors:
                - type: directory
                  value: src/Commands/.*
          ruleset:
            Deptrac: [Commands]
            Commands: [Deptrac]
        YAML);

    $this->runner = new DeptracRunner(
        basePath: dirname(__DIR__),
        configPath: $this->fixture,
    );
});

afterEach(function () {
    @unlink($this->fixture);
    $this->runner->cleanup();
});

it('reports the configured layer names from yaml', function () {
    expect($this->runner->layerNames())->toEqualCanonicalizing(['Deptrac', 'Commands']);
});

it('lists members of a layer via debug:layer', function () {
    $members = $this->runner->debugLayer('Deptrac');

    expect($members)->toContain('Laratrac\\Laratrac\\Deptrac\\DefaultConfig');
    expect($members)->toContain('Laratrac\\Laratrac\\Deptrac\\DeptracRunner');
});

it('produces a mermaidjs flowchart from analyse', function () {
    $mermaid = $this->runner->analyse('mermaidjs');

    expect($mermaid)->toStartWith('flowchart');
});

it('flags the user-provided config path as not the default', function () {
    expect($this->runner->usingDefaults())->toBeFalse();
    expect($this->runner->configPath())->toBe($this->fixture);
});
