<?php

namespace Laratrac\Laratrac\Deptrac;

/**
 * Combines deptrac's per-command outputs into a single JSON document tuned
 * for AI agents. Stays a pure assembler — all I/O happens in the runner.
 */
class MetadataAssembler
{
    public function __construct(
        protected DeptracRunner $runner,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function assemble(MetadataMode $mode = MetadataMode::LayersWithMembers): array
    {
        $mermaid = $this->runner->analyse('mermaidjs');
        $dependencies = self::parseMermaidEdges($mermaid);

        $layerNames = $this->runner->layerNames();
        if ($layerNames === []) {
            // Fall back to layers we can see in the edge graph (PHP-config users).
            $layerNames = self::layerNamesFromEdges($dependencies);
        }

        $layers = [];
        foreach ($layerNames as $name) {
            $entry = ['name' => $name];

            if ($mode->includesMembers()) {
                $members = $this->runner->debugLayer($name);
                $entry['member_count'] = count($members);
                $entry['members'] = $members;
            }

            $layers[] = $entry;
        }

        return [
            'generated_at' => now()->toAtomString(),
            'config_path' => $this->runner->configPath(),
            'using_defaults' => $this->runner->usingDefaults(),
            'mode' => $mode->value,
            'layers' => $layers,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * Extract `Source -->|count| Target;` edges from deptrac's mermaid output.
     *
     * @return list<array{from: string, to: string, count: int}>
     */
    public static function parseMermaidEdges(string $mermaid): array
    {
        $edges = [];
        foreach (preg_split('/\r?\n/', $mermaid) ?: [] as $line) {
            if (! preg_match('/^\s*([A-Za-z0-9_]+)\s*-->\|(\d+)\|\s*([A-Za-z0-9_]+)\s*;?\s*$/', $line, $m)) {
                continue;
            }
            $edges[] = [
                'from' => $m[1],
                'to' => $m[3],
                'count' => (int) $m[2],
            ];
        }

        return $edges;
    }

    /**
     * @param  list<array{from: string, to: string, count: int}>  $edges
     * @return list<string>
     */
    protected static function layerNamesFromEdges(array $edges): array
    {
        $seen = [];
        foreach ($edges as $edge) {
            $seen[$edge['from']] = true;
            $seen[$edge['to']] = true;
        }

        return array_keys($seen);
    }
}
