<?php
/**
 * PBS resource list parser.
 */

namespace Xdmod;

/**
 * Contains functions related to PBS's Resource_List accounting log fields.
 */
class PbsResourceParser
{
    /**
     * Parse Resource_List.nodes from PBS accounting logs.
     *
     * @param string $resourceListNodes Value from Resource_List.nodes field.
     * @return array[] Parsed data. An array of arrays for each resource.
     */
    public function parseResourceListNodes($resourceListNodes) {
        if ($resourceListNodes === '') {
            return [];
        }

        return array_map(
            function ($resource) {
                return explode('=', $resource);
            },
            explode(':', $resourceListNodes)
        );
    }

    /**
     * Determine the GPU count from parsed resource list data.
     *
     * @see \Xdmod\PbsResourceParser::parseResourceListNodes
     *
     * @param array $nodeData Parsed Resource_List.nodes data.
     * @return int The GPU count.
     */
    public function getGpuCountFromResourceListNodes(array $nodeData)
    {
        if (count($nodeData) < 2) {
            return 0;
        }

        // The first element of the nodes resource list is either an integer or
        // the name of a node.
        $nodeCount = ctype_digit($nodeData[0][0]) ? (int)$nodeData[0][0] : 1;

        foreach ($nodeData as $resource) {
            if ($resource[0] === 'gpus' && count($resource) > 1) {
                return $resource[1] * $nodeCount;
            } elseif ($resource[0] === 'gpu' && count($resource) > 1) {
                // Standard PBS uses "gpus", but SDSC Comet has been using
                // "gpu" in the Resource_List.nodect field.
                return $resource[1] * $nodeCount;
            }
        }

        return 0;
    }
}
