<?php

namespace Laratrac\Laratrac\Deptrac;

enum MetadataMode: string
{
    /** Layer graph only — names + edges, no class lists. */
    case GraphOnly = 'graph_only';

    /** Layer graph + the FQCNs that make up each layer. The default. */
    case LayersWithMembers = 'layers_with_members';

    public function includesMembers(): bool
    {
        return $this === self::LayersWithMembers;
    }
}
