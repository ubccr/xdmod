<?php

namespace OpenXdmod\Tests;

use TestHarness\TestFiles;
use Xdmod\PbsResourceParser;

/**
 * PbsResourceParser test class.
 *
 * @package OpenXdmod
 * @subpackage Tests
 * @coversDefaultClass \Xdmod\PbsResourceParser
 */
class PbsResourceParserTest extends \PHPUnit_Framework_TestCase
{
    /** Tests base directory relative to __DIR__ */
    const TESTS_BASE_REL_DIR = '/../../../..';

    const TEST_GROUP = 'unit/pbs-resource-parser';

    private $parser;

    public function setUp()
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
