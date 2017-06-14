<?php

namespace IntegrationTests\Controllers;

class UsageExplorerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->helper = new \TestHarness\XdmodTestHelper();
    }

    /**
     * @dataProvider corruptDataProvider
     */
    public function testCorruptRequestData($input, $expectedMessage)
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, $input);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 400);
        $this->assertEquals($response[0]['message'], $expectedMessage);
    }

    public function corruptDataProvider()
    {
        $defaultJson = <<<EOF
        {
            "public_user": "true",
            "realm": "Jobs",
            "group_by": "none",
            "start_date": "2017-05-01",
            "end_date": "2017-05-31",
            "statistic": "job_count",
            "operation": "get_charts",
            "controller_module": "user_interface"
        }
EOF;
        $tests = array();

        $input = json_decode($defaultJson, true);
        unset($input['end_date']);
        $tests[] = array($input, 'end_date param is not in the correct format of Y-m-d.');

        $input = json_decode($defaultJson, true);
        unset($input['start_date']);
        $tests[] = array($input, 'start_date param is not in the correct format of Y-m-d.');

        $input = json_decode($defaultJson, true);
        $input['group_by'] = 'elephants';
        $tests[] = array($input, 'Query: Unknown Group By "elephants" Specified');

        return $tests;
    }

    /**
     * Checks the structure of the get_tabs endpoint.
     */
    public function testGetTabs()
    {
        $response = $this->helper->post('/controllers/user_interface.php', null, array('operation' => 'get_tabs', 'public_user' => 'true'));

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals($response[1]['http_code'], 200);


        $dwdata = $response[0];

        $this->assertArrayHasKey('totalCount', $dwdata);
        $this->assertArrayHasKey('data', $dwdata);
        $this->assertEquals($dwdata['totalCount'], count($dwdata['data']));

        foreach($dwdata['data'] as $entry)
        {
            $this->assertArrayHasKey('tabs', $entry);

            // This is a funny one - the data is actually json encoded.
            $tabdata = json_decode($entry['tabs'], true);

            $this->assertTrue(count($tabdata) > 0);
        }
    }
}
