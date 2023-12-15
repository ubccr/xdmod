<?php

namespace UnitTests\DataWarehouse;

class ExportBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->_dummydata = array(array(
            'headers' => array('Column1', 'Column2'),
            'duration' => array( 'start' => '2014-01-01', 'end' => '2015-01-01'),
            'title' =>  array('title' => 'Title'),
            'title2' => array('parameters' => array('param1=value1') ),
            'rows' => array(array('Column1' => 'value1', 'Column2' => 'value2'))
        ));
        parent::__construct($name, $data, $dataName);
    }

    private function exportHelper($format, $inline, $filename)
    {
        $result = \DataWarehouse\ExportBuilder::export($this->_dummydata, $format, $inline, $filename);

        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('Content-type', $result['headers']);

        if(!$inline) {
            $this->assertArrayHasKey('Content-Disposition', $result['headers']);
        }

        return $result;
    }

    public function testExportJson() {

        $result = $this->exportHelper('json', true, 'filename');

        $this->assertEquals('application/json', $result['headers']['Content-type']);

        $data = json_decode($result['results'], true);

        foreach($data as $datum)
        {
            $this->assertArrayHasKey('title', $datum);
        }
    }

    public function testExportXml() {

        $result = $this->exportHelper('xml', true, 'filename');

        $this->assertEquals('text/xml', $result['headers']['Content-type']);

        $parsedxml = simplexml_load_string($result['results']);

        $this->assertObjectHasProperty('rows', $parsedxml);
        $this->assertEquals('value1', $parsedxml->rows[0]->row->cell[0]->value);
        $this->assertEquals('value2', $parsedxml->rows[0]->row->cell[1]->value);
    }

    public function testExportXls() {

        $result = $this->exportHelper('xls', false, 'filename');

        $this->assertEquals('application/vnd.ms-excel', $result['headers']['Content-type']);
        $this->assertEquals('attachment; filename="filename.xls"', $result['headers']['Content-Disposition']);

        $expected = <<<EOF
title
Title
parameters
param1=value1
start,end
2014-01-01,2015-01-01
---------
Column1,Column2
value1,value2
---------

EOF;

        $this->assertEquals($expected, $result['results']);
    }

    public function testExportCsv() {

        $result = $this->exportHelper('csv', false, 'filename');

        $this->assertEquals('application/xls', $result['headers']['Content-type']);
        $this->assertEquals('attachment; filename="filename.csv"', $result['headers']['Content-Disposition']);

        $expected = <<<EOF
title
Title
parameters
param1=value1
start,end
2014-01-01,2015-01-01
---------
Column1,Column2
value1,value2
---------

EOF;

        $this->assertEquals($expected, $result['results']);
    }

    public function testExportBananas()
    {
        $this->expectExceptionMessage("Unsupported export format bananas");
        $this->expectException(\Exception::class);
        $this->exportHelper('bananas', false, 'yes we have no bananas');
    }
}
