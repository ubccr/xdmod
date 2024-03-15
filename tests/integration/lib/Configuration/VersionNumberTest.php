<?php

namespace IntegrationTests\Configuration;

use Exception;
use \PHPUnit\Framework\TestCase;
use CCR\Json;

/**
 * Test the Open XDMoD version number.
 */
class VersionNumberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Absolute path to portal_settings.ini of installed Open XDMoD.
     * @var string
     */
    private $portalSettingIniPath = '/etc/xdmod/portal_settings.ini';

    /**
     * Absolute path to build.json used to build the installed Open XDMoD.
     * @var string
     */
    private $buildJsonPath;

    public function setup(): void
    {
        $buildJsonPath = __DIR__ . '/../../../../open_xdmod/modules/xdmod/build.json';
        $this->buildJsonPath = realpath($buildJsonPath);
        if ($this->buildJsonPath === false) {
            throw new Exception(
                sprintf('Failed to find build.json at "%s"', $buildJsonPath)
            );
        }
    }

    /**
     * Test the version number in portal_settings.ini.
     */
    public function testPortalSettingsVersionNumber()
    {
        $portalSettingsData = parse_ini_file($this->portalSettingIniPath, true);
        if ($portalSettingsData === false) {
            throw new Exception(
                sprintf('Failed to parse "%s"', $this->portalSettingIniPath)
            );
        }

        $buildData = Json::loadFile($this->buildJsonPath);

        $this->assertEquals(
            $buildData['version'],
            $portalSettingsData['general']['version'],
            sprintf(
                'Version in "%s" matches version in "%s"',
                $this->portalSettingIniPath,
                $this->buildJsonPath
            )
        );
    }
}
