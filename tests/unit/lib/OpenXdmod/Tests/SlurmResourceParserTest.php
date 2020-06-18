<?php

namespace OpenXdmod\Tests;

use TestHarness\TestFiles;
use Xdmod\SlurmResourceParser;

/**
 * SlurmResourceParser test class.
 *
 * @package OpenXdmod
 * @subpackage Tests
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
     * @dataProvider reqGresGpuCountProvider
     * @covers ::parseReqGres
     * @covers ::getGpuCountFromGres
     */
    public function testReqGresGpuCountParsing($reqGres, $gpuCount)
    {
        $gresData = $this->parser->parseReqGres($reqGres);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromGres($gresData),
            'GPU count'
        );
    }

    /**
     * @dataProvider allocTresGpuCountProvider
     * @covers ::parseAllocTres
     * @covers ::getGpuCountFromTres
     */
    public function testAllocTresGpuCountParsing($allocTres, $gpuCount)
    {
        $tresData = $this->parser->parseAllocTres($allocTres);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromTres($tresData),
            'GPU count'
        );
    }

    public function reqGresGpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . self::TESTS_BASE_REL_DIR);
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'req-gres-gpu-count');
    }

    public function allocTresGpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . self::TESTS_BASE_REL_DIR);
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'alloc-tres-gpu-count');
    }
}
