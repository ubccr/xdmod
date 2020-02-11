<?php

namespace OpenXdmod\Tests;

use TestHarness\TestFiles;
use Xdmod\SlurmGresParser;

/**
 * SlurmGresParser test class.
 *
 * @package OpenXdmod
 * @subpackage Tests
 */
class SlurmGresParserTest extends \PHPUnit_Framework_TestCase
{
    const TEST_GROUP = 'unit/slurm-gres-parser';

    private $parser;

    public function setUp()
    {
        $this->parser = new SlurmGresParser();
    }

    /**
     * @dataProvider gpuCountProvider
     */
    public function testGpuCountParsing($reqGres, $gpuCount)
    {
        $gresData = $this->parser->parseReqGres($reqGres);
        $this->assertEquals(
            $gpuCount,
            $this->parser->getGpuCountFromGres($gresData),
            'GPU count'
        );
    }

    public function gpuCountProvider()
    {
        $testFiles = new TestFiles(__DIR__ . '/../../../..');
        return $testFiles->loadJsonFile(self::TEST_GROUP, 'gpu-count');
    }
}
