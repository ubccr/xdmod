<?php

namespace IntegrationTests\REST\Warehouse;

use IntegrationTests\BaseTest;

class JobViewerTest extends BaseTest
{
    const ENDPOINT = 'rest/v0.1/warehouse/';

    public function setUp()
    {
        $xdmodConfig = array( 'decodetextasjson' => true );
        $this->xdmodhelper = new \TestHarness\XdmodTestHelper($xdmodConfig);
    }

    private static function getDimensions() {
        return array(
            'nsfdirectorate',
            'parentscience',
            'gpucount',
            'jobsize',
            'jobwaittime',
            'jobwalltime',
            'nodecount',
            'pi',
            'fieldofscience',
            'qos',
            'queue',
            'resource',
            'resource_type',
            'username',
            'person'
        );
    }

    /**
     * Note that this test intentionally hardcodes the available dimensions so
     * that we can confirm that the dimensions are all present and correct for
     * fresh installs and for upgrades. Needless to say, the expected results
     * must be updated when the SUPReMM schema changes.
     */
    public function testDimensions()
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->xdmodhelper->authenticate('cd');
        $queryparams = array(
            'realm' => 'Jobs'
        );
        $response = $this->xdmodhelper->get(self::ENDPOINT . 'dimensions', $queryparams);

        $this->assertEquals(200, $response[1]['http_code']);

        $resdata = $response[0];

        $this->assertArrayHasKey('success', $resdata);
        $this->assertTrue($resdata['success']);

        $dimids = array();
        foreach ($resdata['results'] as $dimension) {
            $dimids[] = $dimension['id'];
        }

        $this->assertEquals(self::getDimensions(), $dimids);

        $this->xdmodhelper->logout();
    }

    public function dimensionsProvider()
    {
        $xdmodhelper = new \TestHarness\XdmodTestHelper(array('decodetextasjson' => true));
        $xdmodhelper->authenticate('cd');

        $testCases = array();
        foreach (self::getDimensions() as $dimension) {
            $testCases[] = array($xdmodhelper, $dimension);
        }
        return $testCases;
    }

    /**
     * Check that all dimensions have at least one dimension value.
     *
     * @dataProvider dimensionsProvider
     */
    public function testDimensionValues($xdmodhelper, $dimension)
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $queryparams = array(
            'realm' => 'Jobs'
        );
        $response = $xdmodhelper->get(self::ENDPOINT . 'dimensions/' . $dimension, $queryparams);

        $this->assertEquals(200, $response[1]['http_code']);

        $resdata = $response[0];

        $this->assertArrayHasKey('success', $resdata);
        $this->assertTrue($resdata['success']);
        $this->assertGreaterThan(0, count($resdata['results']));
    }

    public function testResourceEndPoint()
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->xdmodhelper->authenticate('cd');

        $queryparams = array(
            'realm' => 'Jobs'
        );

        $response = $this->xdmodhelper->get(self::ENDPOINT . 'dimensions/resource', $queryparams);

        $this->assertEquals(200, $response[1]['http_code']);

        $resdata = $response[0];

        $this->assertArrayHasKey('success', $resdata);
        $this->assertTrue($resdata['success']);

        foreach($resdata['results'] as $resource)
        {
            $this->assertArrayHasKey('id', $resource);
            $this->assertArrayHasKey('name', $resource);
            $this->assertArrayHasKey('short_name', $resource);
            $this->assertArrayHasKey('long_name', $resource);
        }

        $this->xdmodhelper->logout();
    }

    public function testResourceNoAuth()
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $queryparams = array(
            'realm' => 'Jobs'
        );
        $response = $this->xdmodhelper->get(self::ENDPOINT . 'dimensions/resource', $queryparams);

        $this->assertEquals(401, $response[1]['http_code']);
    }

    private function validateSingleJobSearch($searchparams, $doAuth = true)
    {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        if ($doAuth) {
            $this->xdmodhelper->authenticate('cd');
        }
        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertTrue($result[0]['success']);
        $this->assertArrayHasKey('results', $result[0]);
        $this->assertCount(1, $result[0]['results']);

        $jobdata = $result[0]['results'][0];

        $this->assertArrayHasKey('dtype', $jobdata);
        $this->assertArrayHasKey($jobdata['dtype'], $jobdata);

        if ($doAuth) {
            $this->xdmodhelper->logout();
        }

        return $jobdata;
    }

    public function testBasicJobSearch() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $queryparams = array(
            'realm' => 'Jobs',
            'params' => json_encode(
                array(
                    'resource_id' => 5,
                    'local_job_id' => 6117153
                )
            )
        );
        $this->validateSingleJobSearch($queryparams);
    }

    public function testBasicJobSearchNoAuth() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $searchparams = array(
            'realm' => 'Jobs',
            'params' => json_encode(
                array(
                    'resource_id' => 5,
                    'local_job_id' => 6117153
                )
            )
        );

        foreach (array('usr', 'pi') as $unpriv) {
            $this->xdmodhelper->authenticate($unpriv);
            $response = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);
            $this->assertEquals(403, $response[1]['http_code']);
            $this->assertArrayHasKey('success', $response[0]);
            $this->assertFalse($response[0]['success']);
            $this->xdmodhelper->logout();
        }
    }

    public function testInvalidJobSearch() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $this->xdmodhelper->authenticate('cd');
        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', array() );

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertFalse($result[0]['success']);
        $this->assertEquals(400, $result[1]['http_code']);

        $this->xdmodhelper->logout();
    }

    public function testInvalidJobSearchJson() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $searchparams = array(
            'realm' => 'Jobs',
            'params' => 'this is not json data'
        );

        $this->xdmodhelper->authenticate('cd');
        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], false);
        $this->assertEquals($result[1]['http_code'], 400);

        $this->xdmodhelper->logout();
    }

    public function missingParamsProvider() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::getRealms())) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $xdmodhelper = new \TestHarness\XdmodTestHelper(array('decodetextasjson' => true));
        $xdmodhelper->authenticate('cd');

        $tests = array();

        $tests[] = array(
            $xdmodhelper,
            array(
                'realm' => 'Jobs',
                'params' => json_encode(array('resource_id' => '2801'))
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'end_date' => '2015-01-01',
                'realm' => 'Blobs',
                'params' => json_encode(array()),
                'start' => 0,
                'limit' => 10
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'realm' => 'Jobs',
                'params' => json_encode(array()),
                'start' => 0,
                'limit' => 10
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'end_date' => '2015-01-01',
                'realm' => 'Jobs',
                'params' => json_encode(array())
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'end_date' => '2015-01-01',
                'realm' => 'Jobs',
                'params' => json_encode(array()),
                'start' => 0,
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'end_date' => '2015-01-01',
                'realm' => 'Jobs',
                'params' => json_encode(3),
                'start' => 0,
                'limit' => 20
            ),
            false
        );

        $tests[] = array(
            $xdmodhelper,
            array(
                'start_date' => '2015-01-01',
                'end_date' => '2015-01-01',
                'realm' => 'Jobs',
                'params' => json_encode(array(3)),
                'start' => 0,
                'limit' => 20
            ),
            true
        );

        return $tests;
    }

    /**
     * @dataProvider missingParamsProvider
     */
    public function testInvalidJobSearchMissingParams($xdmodhelper, $searchparams, $isfinal) {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $result = $xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertFalse($result[0]['success']);
        $this->assertEquals(400, $result[1]['http_code']);

        if ($isfinal) {
            $xdmodhelper->logout();
        }
    }

    public function testAdvancedSearchInvalid() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $searchparams = array(
            'start_date' => '2015-01-01',
            'end_date' => '2015-01-01',
            'realm' => 'Jobs',
            'params' => json_encode(
                array( 'non existent dimension 1' => array(0),
                'another invalid dimension' => array(1) )
            ),
            'limit' => 10,
            'start' => 0
        );

        $this->xdmodhelper->authenticate('cd');
        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);
        $this->assertFalse($result[0]['success']);
        $this->assertEquals(400, $result[1]['http_code']);

        $this->xdmodhelper->logout();
    }

    public function testAdvancedSearchNoParams() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $searchparams = array(
            'start_date' => '2016-12-31',
            'end_date' => '2016-12-31',
            'realm' => 'Jobs',
            'params' => '{}',
            'limit' => 5,
            'start' => 0
        );

        $this->xdmodhelper->authenticate('usr');
        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/jobs', $searchparams);
        $this->assertTrue($result[0]['success']);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertEquals(9, $result[0]['totalCount']);
        $this->assertCount(5, $result[0]['results']);

        // Normal user can only see their jobs
        foreach($result[0]['results'] as $jobrecord) {
            $this->assertArrayHasKey('resource', $jobrecord);
            $this->assertArrayHasKey('name', $jobrecord);
            $this->assertArrayHasKey('text', $jobrecord);
            $this->assertArrayHasKey('start_time_ts', $jobrecord);
            $this->assertArrayHasKey('end_time_ts', $jobrecord);
            $this->assertArrayHasKey('cpu_user', $jobrecord);
            $this->assertEquals('Whimbrel', $jobrecord['name']);
        }

        $this->xdmodhelper->logout();
    }

    /**
     * Test that the search history save new search correctly sanitizes the text input.
     * @return void
     */
    public function testSearchSaving() {

        $input = array(
            "text" => "Typing in <script>alert(1)</script>",
            "searchterms" => array(
                "params" => array(
                    "start_date" => "2020-01-09",
                    "end_date" => "2020-01-15",
                    "realm" => "Jobs",
                    "limit" => 24,
                    "start" => 0,
                    "params" => "{\"resource\":[\"1\"]}"
                )
            ),
            "results" => array(
                array(
                    'resource' => 'pozidriv',
                    'name' => 'blah',
                    "jobid" => 42520494,
                    "text" => "pozidriv-5138",
                    "dtype" => "jobid",
                    "local_job_id" => "5138"
                )
            )
        );

        $this->xdmodhelper->authenticate('cd');
        $response = $this->xdmodhelper->post(self::ENDPOINT . 'search/history', array('realm' => 'Jobs'), array('data' => json_encode($input)));

        $this->assertEquals(200, $response[1]['http_code']);
        $result = $response[0];
        $this->assertTrue($result['success']);

        $this->assertEquals($input['searchterms'], $result['results']['searchterms']);
        $this->assertEquals($input['results'], $result['results']['results']);

        $this->assertEquals('Typing in &lt;script&gt;alert(1)&lt;/script&gt;', $result['results']['text']);

        // get the record id
        $recordId = $result['results'][$result['results']['dtype']];

        // remove the dummy saved search.
        $response = $this->xdmodhelper->delete(self::ENDPOINT . 'search/history/' . $recordId, array('realm' => 'Jobs'));

        $this->assertEquals(200, $response[1]['http_code']);
        $result = $response[0];
        $this->assertTrue($result['success']);
    }

    public function testJobMetadata() {
        //TODO: Needs further integration for other realms.
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $queryparams = array(
            'realm' => 'Jobs',
            'params' => json_encode(
                array(
                    'resource_id' => 5,
                    'local_job_id' => 6112282
                )
            )
        );
        $this->xdmodhelper->authenticate('cd');
        $jobparams = $this->validateSingleJobSearch($queryparams, false);
        $searchparams = array(
            'realm' => 'Jobs',
            'recordid' => '-1', // this parameter is not acutally used for anything but needs to be present :-(
            $jobparams['dtype'] => $jobparams[$jobparams['dtype']]
        );

        $result = $this->xdmodhelper->get(self::ENDPOINT . 'search/history', $searchparams);

        $types = array();

        foreach($result[0]['results'] as $datum) {
            $this->assertArrayHasKey('dtype', $datum);
            $this->assertArrayHasKey($datum['dtype'], $datum);
            $this->assertArrayHasKey('text', $datum);
            $types[] = $datum['text'];
        }

        $expectedTypes = array(
            'Accounting data'
        );

        $this->assertEquals($expectedTypes, $types);
    }
}
