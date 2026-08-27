<?php

namespace UnitTests\OpenXdmod\Tests;

use IntegrationTests\TestHarness\TestFiles;
use Xdmod\LsfResourceParser;

/**
 * LsfResourceParser test class.
 *
 * @coversDefaultClass LsfResourceParser
 */
class LsfResourceParserTest extends \PHPUnit\Framework\TestCase
{
    /** Tests base directory relative to __DIR__ */
    const TESTS_BASE_REL_DIR = '/../../../..';

    const TEST_GROUP = 'unit/lsf-resource-parser';

    private $parser;

    public function setup(): void
    {
        $this->parser = new LsfResourceParser();
    }

    /**
     * @dataProvider effectiveResReqGpuCountProvider
     * @covers ::parseResourceRequirement
     * @covers ::getGpuCountFromRusage
     */
    public function testEffectiveResReqGpuCountParsing($resReq, $gpuCount)
    {
        $rusage = $this->parser->parseResourceRequirement($resReq);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromRusage($rusage),
            'GPU count'
        );
    }

    public function effectiveResReqGpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . self::TESTS_BASE_REL_DIR);
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'effective-res-req-gpu-count');
    }
}
