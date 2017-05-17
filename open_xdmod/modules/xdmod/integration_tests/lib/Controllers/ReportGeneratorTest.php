<?php

namespace IntegrationTests\Controllers;

use TestHarness\XdmodTestHelper;

class ReportGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
    }

    /**
     * Test enumeration of report templates for user types.
     *
     * @param string $userType User type abbreviation.
     * @param int $httpCode Expected HTTP status code.
     * @param array $expectedContent Expected content decoded from returned JSON.
     *
     * @dataProvider enumReportTemplateDataProvider
     */
    public function testEnumReportTemplates(
        $userType,
        $httpCode,
        array $expectedContent
    ) {
        if ($userType !== 'pub') {
            $this->helper->authenticate($userType);
        }

        $response = $this->helper->post(
            '/controllers/report_builder.php',
            null,
            array('operation' => 'enum_templates')
        );

        list($content, $curlinfo) = $response;

        $this->assertEquals('application/json', $curlinfo['content_type']);
        $this->assertEquals($httpCode, $curlinfo['http_code']);
        $this->assertEquals($expectedContent, $content);

        $this->helper->logout();
    }

    public function enumReportTemplateDataProvider()
    {
        $notAuthenticatedReportTemplates = array(
            'success' => false,
            'count' => 0,
            'total' => 0,
            'totalCount' => 0,
            'results' => array(),
            'data' => array(),
            'message' => 'Session Expired',
            'code' => 2,
        );

        $noReportTemplates = array(
            'status' => 'success',
            'success' => true,
            'templates' => array(),
            'count' => 0,
        );

        $centerDirectorReportTemplates = array(
            'status' => 'success',
            'success' => true,
            'templates' => array(
                array(
                    'id' => '1',
                    'name' => 'Quarterly Report - Center Director',
                    'description' => 'Quarterly Report - Center Director',
                    'use_submenu' => '0'
                ),
            ),
            'count' => 1
        );

        return array(
            array(
                'pub',
                401,
                $notAuthenticatedReportTemplates,
            ),
            array(
                'cd',
                200,
                $centerDirectorReportTemplates,
            ),
            array(
                'cs',
                200,
                $noReportTemplates,
            ),
            array(
                'pi',
                200,
                $noReportTemplates,
            ),
            array(
                'usr',
                200,
                $noReportTemplates,
            ),
        );
    }
}
