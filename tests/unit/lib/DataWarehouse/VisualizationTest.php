<?php

namespace UnitTesting\DataWarehouse;

class VisualizationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // Cut and pasted from the colors1.json file.
        $this->expected = array(
            0xFFFFFF, 0x1199FF, 0xDB4230, 0x4E665D, 0xF4A221, 0x66FF00, 0x33ABAB, 0xA88D95,
            0x789ABC, 0xFF99CC, 0x00CCFF, 0xFFBC71, 0xA57E81, 0x8D4DFF, 0xFF6666, 0xCC99FF,
            0x2F7ED8, 0x0D233A, 0x8BBC21, 0x910000, 0x1AADCE, 0x492970, 0xF28F43, 0x77A1E5,
            0x3366FF, 0xFF6600, 0x808000, 0xCC99FF, 0x008080, 0xCC6600, 0x9999FF, 0x99FF99,
            0x969696, 0xFF00FF, 0xFFCC00, 0x666699, 0x00FFFF, 0x00CCFF, 0x993366, 0x3AAAAA,
            0xC0C0C0, 0xFF99CC, 0xFFCC99, 0xCCFFCC, 0xCCFFFF, 0x99CCFF, 0x339966, 0xFF9966,
            0x69BBED, 0x33FF33, 0x6666FF, 0xFF66FF, 0x99ABAB, 0xAB8722, 0xAB6565, 0x990099,
            0x999900, 0xCC3300, 0x669999, 0x993333, 0x339966, 0xC42525, 0xA6C96A, 0x111111);
    }

    public function tearDown() {
    }

    
    public function testGetLotsOfColours()
    {
        $count = 65;

        $v = \DataWarehouse\Visualization::getColors($count);

        $this->assertEquals(count($v), 65);
    }

    public function testGetFewColours()
    {
        $v = \DataWarehouse\Visualization::getColors(6);
        $this->assertEquals($v, $this->expected);
    }

    public function testGetSomeColours()
    {
        $v = \DataWarehouse\Visualization::getColors(64);
        $this->assertEquals($v, $this->expected);
    }

    public function testNullCount()
    {
        $v = \DataWarehouse\Visualization::getColors(null);
        $this->assertEquals($v, $this->expected);
    }

    public function testNoWhite()
    {
        $ncolours = 10;

        $v = \DataWarehouse\Visualization::getColors($ncolours, 0, false);
        $this->assertEquals($v, array_slice($this->expected, 1) );
        $this->assertGreaterThanOrEqual($ncolours, count($v));
    }

    public function testArraySizes()
    {
        for($i = 0; $i < 300; $i++)
        {
            $v = \DataWarehouse\Visualization::getColors($i, 0, false);
            $this->assertGreaterThanOrEqual($i, count($v));

            $v = \DataWarehouse\Visualization::getColors($i, 0, true);
            $this->assertGreaterThanOrEqual($i, count($v));
        }
    }
}
