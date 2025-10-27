<?php

namespace PostTests\Rest;

use IntegrationTests\BaseTest;
use IntegrationTests\TestHarness\XdmodTestHelper;

class JobViewerTest extends BaseTest
{
    const ENDPOINT = 'rest/v0.1/warehouse/';

    public function setup(): void
    {
        $xdmodConfig = array( 'decodetextasjson' => true );
        $this->xdmodhelper = new XdmodTestHelper($xdmodConfig);
    }


    public function testJobAccountingEncoding() {

        $searchparams = array(
            'realm' => 'Jobs',
            'params' => json_encode(
                array(
                    'resource_id' => 2,
                    'local_job_id' => 11963372
                )
            )
        );

        $this->xdmodhelper->authenticate('cd');

        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertTrue($result[0]['success']);
        $this->assertArrayHasKey('results', $result[0]);
        $this->assertCount(1, $result[0]['results']);

        $jobdata = $result[0]['results'][0];

        $this->assertEquals('Ιωάννης, Γιάννης', $jobdata['name']);
        $this->assertEquals('façade', $jobdata['username']);

        $acctparams = array(
            'realm' => 'Jobs',
            'jobid' => $jobdata['jobid'],
            'infoid' => 0
        );

        $accounting = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs/accounting', $acctparams);

        $this->assertTrue($accounting[0]['success']);

        $compacted = array();
        foreach ($accounting[0]['data'] as $entry) {
            $compacted[$entry['key']] = $entry['value'];
        }

        $this->assertEquals('Ιωάννης, Γιάννης', $compacted['User']);
        $this->assertEquals('façade', $compacted['System Username']);
        $this->assertEquals('schón Straße', $compacted['Name']);
    }
}
