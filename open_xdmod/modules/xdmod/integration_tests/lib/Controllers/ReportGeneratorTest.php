<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
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
     * @param string $outputFile the test file that contains the expected output.
     *
     * @dataProvider enumReportTemplateDataProvider
     */
    public function testEnumReportTemplates($userType, $outputFile) {
        if ($userType !== 'pub') {
            $this->helper->authenticate($userType);
        }

        $response = $this->helper->post(
            '/controllers/report_builder.php',
            null,
            array('operation' => 'enum_templates')
        );

        list($content, $curlinfo) = $response;

        $expected = Json::loadFile(
            TestFiles::getFile('controllers', $outputFile)
        );

        $this->assertArrayHasKey('http_code', $expected);
        $this->assertArrayHasKey('response', $expected);

        $httpCode = $expected['http_code'];
        $expectedContent = $expected['response'];

        $this->assertEquals('application/json', $curlinfo['content_type']);
        $this->assertEquals($httpCode, $curlinfo['http_code']);
        $this->assertEquals($expectedContent, $content);

        $this->helper->logout();
    }

    public function enumReportTemplateDataProvider()
    {
        return Json::loadFile(
            TestFiles::getFile('controllers', 'enum_report_templates', 'input')
        );
    }
}
