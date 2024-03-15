<?php

namespace UnitTests\OpenXdmod\Tests;

use IntegrationTests\TestHarness\TestFiles;
use Xdmod\PbsResourceParser;

/**
 * PbsResourceParser test class.
 *
 * @coversDefaultClass PbsResourceParser
 */
class PbsResourceParserTest extends \PHPUnit\Framework\TestCase
{
    /** Tests base directory relative to __DIR__ */
    const TESTS_BASE_REL_DIR = '/../../../..';

    const TEST_GROUP = 'unit/pbs-resource-parser';

    private $parser;

    public function setup(): void
    {
        $this->parser = new PbsResourceParser();
    }

    /**
     * @dataProvider resourceListNodesGpuCountProvider
     * @covers ::parseResourceListNodes
     * @covers ::getGpuCountFromResourceListNodes
     */
    public function testGpuCountParsing($resourceListNodes, $gpuCount)
    {
        $nodesData = $this->parser->parseResourceListNodes($resourceListNodes);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromResourceListNodes($nodesData),
            'GPU count'
        );
    }

    public function resourceListNodesGpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . self::TESTS_BASE_REL_DIR);
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'resource-list-nodes-gpu-count');
    }
}
