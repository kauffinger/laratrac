<?php

use Laratrac\Laratrac\Deptrac\MetadataAssembler;
use Laratrac\Laratrac\Deptrac\MetadataMode;

it('parses Source -->|count| Target edges from mermaid output', function () {
    $mermaid = <<<'MMD'
flowchart TD;
    Controller -->|3| Service;
    Service -->|7| Model;
    Job -->|1| Service;
    linkStyle 0 stroke:red,stroke-width:4px;
MMD;

    expect(MetadataAssembler::parseMermaidEdges($mermaid))->toEqual([
        ['from' => 'Controller', 'to' => 'Service', 'count' => 3],
        ['from' => 'Service', 'to' => 'Model', 'count' => 7],
        ['from' => 'Job', 'to' => 'Service', 'count' => 1],
    ]);
});

it('returns no edges when mermaid output has no flow lines', function () {
    expect(MetadataAssembler::parseMermaidEdges("flowchart TD;\n"))->toBe([]);
    expect(MetadataAssembler::parseMermaidEdges(''))->toBe([]);
});

it('reports whether a mode includes layer members', function () {
    expect(MetadataMode::LayersWithMembers->includesMembers())->toBeTrue();
    expect(MetadataMode::GraphOnly->includesMembers())->toBeFalse();
});
