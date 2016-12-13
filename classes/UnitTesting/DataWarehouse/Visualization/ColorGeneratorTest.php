
<?php
require_once dirname(__FILE__).'/../../../../configuration/linker.php';

class ColorGeneratorTest extends PHPUnit_Framework_TestCase
{
	function setUp()
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

        $this->inputdata = array( 
            array( "name" => "data1", "short_name" => "d1", "value" => "7" ),
            array( "name" => "data2", "short_name" => "d2", "value" => "2" ),
            array( "name" => "data3", "short_name" => "d3", "value" => "7" ),
            array( "name" => "data4", "short_name" => "d4", "value" => "5" ),
        );
    }
	
	function tearDown() {
	}
	
    public function testRoundRobinMapping()
    {
        // Build large array of data to trigger the round robin mode

        $dummydata = array();
        for($i = 0; $i < (\DataWarehouse\Visualization\ColorGenerator::COLOR_MAP_THRESHOLD + 1); $i++)
        {
            $dummydata[] = array("name" => "n", "value" => 1);
        }

        $c = new \DataWarehouse\Visualization\ColorGenerator($dummydata, 10, false);
        
        $expectedColors = array_slice($this->expected,1);
        $totalCols = count($expectedColors);
        for($i = 0; $i < $totalCols * 2; $i++)
        {
            $this->assertEquals( $c->getColor("ignore me"), $expectedColors[ $i % $totalCols ] );
        }
    }

    public function testFixedMapping()
    {
        $c = new \DataWarehouse\Visualization\ColorGenerator($this->inputdata, 2, false);

        $results = array();

        foreach($this->inputdata as $row)
        {
            $result = $c->getColor($row["name"]);

            $this->assertEquals( false, array_key_exists($result, $results) );

            $results[$result] = 1;
        }

        $result = $c->getColor("Ave of 10 others");
        $this->assertEquals( false, array_key_exists($result, $results) );

    }

    public function testFixedShortMapping()
    {
        $c = new \DataWarehouse\Visualization\ColorGenerator($this->inputdata, 2, true);

        $results = array();

        foreach($this->inputdata as $row)
        {
            $result = $c->getColor($row["short_name"]);

            $this->assertEquals( false, array_key_exists($result, $results) );

            $results[$result] = 1;
        }

        $result = $c->getColor("Ave of 10 others");
        $this->assertEquals( false, array_key_exists($result, $results) );

    }
}

?>
