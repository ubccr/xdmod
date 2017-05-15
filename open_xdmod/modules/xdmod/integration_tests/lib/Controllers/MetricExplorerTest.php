<?php

namespace IntegrationTests\Controllers;

class MetricExplorerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->helper = new \TestHarness\XdmodTestHelper();
    }

    /**
     * Checks the structure of the DwDescripter response.
     */
    public function testGetDwDescripter()
    {
        $this->helper->authenticate('po');

        $response = $this->helper->post('/controllers/metric_explorer.php', null, array('operation' => 'get_dw_descripter'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);


        $dwdata = $response[0];

        $this->assertArrayHasKey('totalCount', $dwdata);
        $this->assertArrayHasKey('data', $dwdata);
        $this->assertEquals($dwdata['totalCount'], count($dwdata['data']));

        foreach($dwdata['data'] as $entry)
        {
            $this->assertArrayHasKey('realms', $entry);
            foreach($entry['realms'] as $realm)
            {
                $this->assertArrayHasKey('dimensions', $realm);
                $this->assertArrayHasKey('metrics', $realm);
            }
        }
    }

    /**
     * checks that you need to be authenticated to get_dw_descripter
     */
    public function testGetDwDescripterNoAuth()
    {
        // note - not authenticated

        $response = $this->helper->post('/controllers/metric_explorer.php', null, array('operation' => 'get_dw_descripter'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 401);
    }
}
