<?php

namespace OpenXdmod\Tests;

use TestHarness\TestFiles;
use Xdmod\SlurmResourceParser;

/**
 * SlurmResourceParser test class.
 *
 * @coversDefaultClass \Xdmod\SlurmResourceParser
 */
class SlurmResourceParserTest extends \PHPUnit_Framework_TestCase
{
    /** Tests base directory relative to __DIR__ */
    const TESTS_BASE_REL_DIR = '/../../../..';

    const TEST_GROUP = 'unit/slurm-resource-parser';

    private $parser;

    public function setUp()
    {
        $this->parser = new SlurmResourceParser();
    }

    /**
     * @dataProvider allocTresGpuCountProvider
     * @covers ::parseTres
     * @covers ::getGpuCountFromTres
     */
    public function testAllocTresGpuCountParsing($allocTres, $gpuCount)
    {
        $tresData = $this->parser->parseTres($allocTres);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromTres($tresData),
            'GPU count'
        );
    }

    public function allocTresGpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . self::TESTS_BASE_REL_DIR);
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'alloc-tres-gpu-count');
    }
}
