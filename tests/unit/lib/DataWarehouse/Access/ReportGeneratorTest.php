<?php

namespace UnitTests\DataWarehouse\Access;

use \DataWarehouse\Access\ReportGenerator;

class ReportGeneratorTest extends \PHPUnit_Framework_TestCase
{

    public function reportTmpProvider() {
        $data = array();
        $data[]  = array('1-1579636800-yk3aoO', true);
        $data[]  = array('1-1579636800-../etc/shadow', false);
        $data[]  = array('../../root/.ssh/id_rsa', false);

        return $data;
    }

    public function reportIdProvider() {
        $data = array();
        $data[]  = array('1-1579636800', true);
        $data[]  = array('1021-1479636800.234', true);
        $data[]  = array('cat /etc/passwd', false);

        return $data;
    }

    public function chartRefProvider() {
        $data = array();
        $data[] = array('1-1579636800;2', true);
        $data[] = array('1;161', true);
        $data[] = array('/var/log/messages;161', false);
        $data[] = array('; SELECT * FROM Users;161', false);

        return $data;
    }

    public function cacheVarProvider() {
        $data = array();
        $data[] = array('2019-12-01;2019-12-31;1-1579636800;2', true);
        $data[] = array('2019-12-01;2019-12-31;160-1579636800.234;2', true);
        $data[] = array('2019-12-01;2019-12-31;xd_report_volatile_1;153', true);
        $data[] = array('2010-01-22;2020-01-22;xd_report_volatile_1;161_d1579741925', true);
        $data[] = array('2-1-22;2020-01-22;xd_report_volatile_1;161_d1579741925', false);
        $data[] = array('2019-12-01;2019-12-31;../etc/passwd;2', false);

        return $data;
    }

    /*
     * run a regular expression via the php filter_var api and check
     * whether the input is valid.
     */
    private function runFilter($regexp, $input, $isValid)
    {
        $r = filter_var(
            $input,
            FILTER_VALIDATE_REGEXP,
            array('options'=> array('regexp'=> $regexp))
        );

        if ($isValid) {
            $this->assertEquals($input, $r);
        } else {
            $this->assertFalse($r);
        }
    }

    /**
     * @dataProvider cacheVarProvider
     */
    public function testCacheVar($input, $isValid) {
        $this->runFilter(ReportGenerator::CHART_CACHEREF_REGEX, $input, $isValid);
    }

    /**
     * @dataProvider reportIdProvider
     */
    public function testReportIdVar($input, $isValid) {
        $this->runFilter(ReportGenerator::REPORT_ID_REGEX, $input, $isValid);
    }

    /**
     * @dataProvider chartRefProvider
     */
    public function testChartRefVar($input, $isValid) {
        $this->runFilter(ReportGenerator::REPORT_CHART_REF_REGEX, $input, $isValid);
    }

    /**
     * @dataProvider reportTmpProvider
     */
    public function testReportTmpVar($input, $isValid) {
        $this->runFilter(ReportGenerator::REPORT_TMPDIR_REGEX, $input, $isValid);
    }
}
