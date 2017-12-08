<?php

namespace UnitTesting\Xdmod;

require_once __DIR__ . '/../../bootstrap.php';

use CCR\Json;
use Xdmod\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_OUTPUT_PATH = "/../../artifacts/xdmod-test-artifacts/xdmod/acls/output";

    /**
     *
     * @dataProvider moduleSectionProvider
     *
     * @param string $section
     * @param array $testCases
     */
    public function testGetModuleSection($section, array $testCases)
    {
        $this->assertNotNull($section);
        $this->assertNotEmpty($testCases);

        $config = Config::factory();
        $this->assertNotNull($config);

        foreach ($testCases as $testCase) {
            if (!isset($testCase['expected'])) {
                continue;
            }

            $metaData = isset($testCase['meta_data'])
                ? $testCase['meta_data']
                : null;
            $expected = $testCase['expected'];

            $actual = $metaData !== null
                ? $config->filterByMetaData(
                    $config->getModuleSection($section),
                    $metaData
                )
                : $config->getModuleSection($section);

            $this->assertEquals($expected, $actual);
        }

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Configuration file 'supa.json' not found
     */
    public function testInvalidSection()
    {
        $invalidSection = 'supa';

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($invalidSection);
    }

    public function testValidSectionWithNoDFolder()
    {
        $validSection = 'linker';

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($validSection);
    }

    public function testMissingParentPlus()
    {
        $section = 'baz';
        $sectionFilePath = CONFIG_DIR . "/$section.json";
        $sectionDir = CONFIG_DIR . "/$section.d";
        $sectionChildFile = "$sectionDir/xdmod.json";


        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, "{}");
        fflush($fh);
        fclose($fh);

        // If the section directory doesn't exist then create it.
        if (!is_dir($sectionDir)) {
            $this->assertTrue(mkdir($sectionDir), "Unable to create: $sectionDir");
        }

        // Create the child file w/ a '+' property that the parent doesn't have.
        $fh = fopen($sectionChildFile, "w+");
        fwrite($fh, '{ "+roles": { "+default": { "+permitted_modules": [ { "name": "job_viewer", "title": "Job Viewer", "position": 5000, "javascriptClass": "XDMoD.Module.JobViewer", "javascriptReference": "CCR.xdmod.ui.jobViewer", "tooltip": "View detailed job-level metrics", "userManualSectionName": "Job Viewer" } ] } } }');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $config->getModuleSection($section);

        // Clean up the child file.
        $this->assertTrue(unlink($sectionChildFile), "Unable to remove $sectionChildFile");

        // Clean up the section directory.
        $this->assertTrue(rmdir($sectionDir), "Unable to remove $sectionDir");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");

    }

    public function testMalformedParentSection()
    {
        $section = 'baz';
        $sectionFilePath = CONFIG_DIR . "/$section.json";
        $sectionDir = CONFIG_DIR . "/$section.d";
        $sectionChildFile = "$sectionDir/xdmod.json";


        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": "totally not going to work."}');
        fflush($fh);
        fclose($fh);

        // If the section directory doesn't exist then create it.
        if (!is_dir($sectionDir)) {
            $this->assertTrue(mkdir($sectionDir), "Unable to create: $sectionDir");
        }

        // Create the child file w/ a '+' property that the parent doesn't have.
        $fh = fopen($sectionChildFile, "w+");
        fwrite($fh, '{ "+roles": { "+default": { "+permitted_modules": [ { "name": "job_viewer", "title": "Job Viewer", "position": 5000, "javascriptClass": "XDMoD.Module.JobViewer", "javascriptReference": "CCR.xdmod.ui.jobViewer", "tooltip": "View detailed job-level metrics", "userManualSectionName": "Job Viewer" } ] } } }');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);
        try {
            $config->getModuleSection($section);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $condition = strpos($msg, "Cannot merge non-array/object values") >= 0;
            $this->assertTrue(
                $condition,
                "Unable to correctly identify the expected exception message."
            );
        }


        // Clean up the child file.
        $this->assertTrue(unlink($sectionChildFile), "Unable to remove $sectionChildFile");

        // Clean up the section directory.
        $this->assertTrue(rmdir($sectionDir), "Unable to remove $sectionDir");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testAddMetaDataToASectionThatAlreadyHasSome()
    {
        $section = 'abc';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "enabled": true }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $metaData = $data['meta-data'];
        $this->assertArrayHasKey('enabled', $metaData);

        $this->assertTrue($metaData['enabled'] === true);

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testFilteringWithMismatchedValueTypes()
    {
        $invalidMetaData = array(
            'modules' => 'bar'
        );

        $config = Config::factory();
        $this->assertNotNull($config);

        $roles = $config->getModuleSection('roles');
        $filtered = $config->filterByMetaData($roles, $invalidMetaData);
        $this->assertEmpty($filtered);

    }

    public function testFilteringWithNonArrays()
    {
        $section = 'bac';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "enabled": true }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $filtered = $config->filterByMetaData(
            $data,
            array(
                'enabled' => true
            )
        );
        $this->assertNotEmpty($filtered, "Expected data to be returned but found none.");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function testFilteringWithNonAssociativeArrays()
    {
        $section = 'cba';
        $sectionFilePath = CONFIG_DIR . "/$section.json";

        // Create the parent file w/ an empty json object.
        $fh = fopen($sectionFilePath, "w+");
        fwrite($fh, '{"roles": {}, "meta-data": { "testing": { "modules": ["xdmod"] } }}');
        fflush($fh);
        fclose($fh);

        $config = Config::factory();
        $this->assertNotNull($config);

        $data = $config->getModuleSection($section);
        $this->assertArrayHasKey("meta-data", $data, "Section $section does not have any meta-data.");

        $filtered = $config->filterByMetaData(
            $data,
            array(
                'testing' => array(
                    "modules" => array(
                        "xdmod"
                    )
                )
            )
        );
        $this->assertNotEmpty($filtered, "Expected data to be returned but found none.");

        // Clean up the parent file.
        $this->assertTrue(unlink($sectionFilePath), "Unable to remove $sectionFilePath");
    }

    public function moduleSectionProvider()
    {
        $rolesExpected = Json::loadFile(__DIR__ . self::TEST_ARTIFACT_OUTPUT_PATH . DIRECTORY_SEPARATOR . 'roles-update_enumAllAvailableRoles.json');
        $datawarehouseExpected = Json::loadFile(__DIR__ . self::TEST_ARTIFACT_OUTPUT_PATH . DIRECTORY_SEPARATOR . 'datawarehouse.json');
        return array(
            array(
                'roles',
                array(
                    array(
                        'meta_data' => array(
                            'modules' => array(
                                'xdmod'
                            )
                        ),
                        'expected' => $rolesExpected
                    )
                )
            ),
            array(
                'datawarehouse',
                array(
                    array(
                        'meta_data' => array(
                            'modules' => array(
                                'xdmod'
                            )
                        ),
                        'expected' => $datawarehouseExpected
                    )
                )
            )
        );
    }
}
