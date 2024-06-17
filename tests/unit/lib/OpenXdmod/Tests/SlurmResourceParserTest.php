<?php

namespace UnitTests\OpenXdmod\Tests;

use IntegrationTests\TestHarness\TestFiles;
use Xdmod\SlurmResourceParser;

/**
 * SlurmResourceParser test class.
 *
 * @coversDefaultClass SlurmResourceParser
 */
class SlurmResourceParserTest extends \PHPUnit\Framework\TestCase
{
    /** Tests base directory relative to __DIR__ */
    const TESTS_BASE_REL_DIR = '/../../../..';

    const TEST_GROUP = 'unit/slurm-resource-parser';

    private $parser;

    public function setup(): void
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
