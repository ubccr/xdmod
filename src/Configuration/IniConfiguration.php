<?php

namespace CCR\Configuration;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * This class is meant to provide a method to utilize our extensible ini file feature. This feature allows us to
 * "add new data" to an ini file ( ex. `file.ini` ) by creating a new ini file in a directory called `file.d`. At
 * runtime this code reads and merges the data from the original ini file, `file.ini` with all of the files in `file.d`.
 *
 * This is one way in which we enable our "modules" to add configuration information to existing files.
 *
 * Note: This class is meant to provide the functionality contained within `libraries/utilities.php::getConfiguration`
 * and `libraries/utilities.php::getConfigurationSection` to the Symfony code. Ultimately the code found in `libraries/`
 * and `classes/` should be fully migrated to the `src/` directory and brought up to date.
 *
 */
class IniConfiguration
{
    /**
     * The data from the ini file and all of the partial ini files, if they exist.
     * @var array
     */
    protected array $iniData = [];

    /**
     * @param string $iniFilePath
     * @param LoggerInterface $logger
     * @throws Exception if unable to read the ini file.
     * @throws Exception if unable to parse the ini file.
     * @throws Exception if unable to read the directory containing partial ini files.
     * @throws Exception if unable to read a partial ini file from the partial ini file directory.
 */
    public function __construct(
        #[Autowire(value: CONFIG_PORTAL_SETTINGS)]
        readonly string $iniFilePath,
        protected readonly LoggerInterface $logger,
    ) {
        $this->load($iniFilePath);
    }

    /**
     * Loads the information from the file identified by the $iniFilePath argument, and if it exists,
     * a directory containing partial configuration files. This directories name is derived from the directory that
     * $iniFilePath resides in, combined with the base name of $iniFilePath and '.d'.
     *
     * Example:
     * $iniFilePath = '/etc/xdmod/portal.ini';
     * $dotDPath = '/etc/xdmod/portal.d'
     *
     * @param string $iniFilePath The path of the ini file to load.
     *
     * @return void
     *
     * @throws Exception if unable to read the ini file.
     * @throws Exception if unable to parse the ini file.
     * @throws Exception if unable to read a partial ini file.
     * @throws Exception if unable to read the directory containing partial ini files.
     */
    private function load(string $iniFilePath): void
    {
        if (!is_readable($iniFilePath)) {
            $msg = "Could not read settings file: " . $this->iniFilePath;
            throw new Exception($msg);
        }

        $iniData = parse_ini_file($iniFilePath, true);
        if ($iniData === false) {
            $msg = "Failed to parse settings file: " . $this->iniFilePath;
            throw new Exception($msg);
        }

        $dotDPath = preg_replace('/\\.ini$/', '.d', $iniFilePath);
        $filePaths = glob("{$dotDPath}/*.ini");
        sort($filePaths);

        foreach ($filePaths as $partialFile) {
            if (!is_readable($partialFile)) {
                $msg = "Could not read settings file: " . $partialFile;
                throw new Exception($msg);
            }

            $partialIniData = parse_ini_file($partialFile, true);
            if ($partialIniData === false) {
                $msg = "Failed to parse settings file: " . $partialFile;
                throw new Exception($msg);
            }

            foreach ($partialIniData as $sectionName => $sectionData) {
                foreach ($sectionData as $key => $value) {
                    $iniData[$sectionName][$key] = $value;
                }
            }
        }

        $this->iniData = $iniData;
    }

    /**
     * Retrieve the provided $option from the provided $section.
     *
     * @param string $section the section that will be used to find and retrieve the specified option.
     * @param string|null $option the option that will be used to retrieve the specified value.
     * @return mixed the value of the specified option.
     * @throws Exception if the provided section is empty or null.
     * @throws Exception if the provided section does not exist.
     * @throws Exception if the provided option is empty or null.
     * @throws Exception if the provided option does not exist.
     */
    public function getConfigurationOption(string $section, ?string $option): mixed
    {
        $sectionData = $this->getConfigurationSection($section);

        if (empty($option)) {
            $msg = "Configuration option not specified";
            throw new Exception($msg);
        }

        if (!array_key_exists($option, $sectionData)) {
            $msg = "Option '$option' does not exist in section '$section'";
            throw new Exception($msg);
        }

        return $sectionData[$option];
    }

    /**
     * Retrieve the data associated with the specified section.
     *
     * @param string $section the section to retrieve.
     * @return mixed the data associated with this section.
     * @throws Exception if the section is empty.
     * @throws Exception if the section does not exist.
     */
    public function getConfigurationSection(string $section): mixed
    {
        if (empty($section)) {
            $msg = "Configuration section not specified";
            throw new Exception($msg);
        }

        // Verifying that the section exist.
        if (!array_key_exists($section, $this->iniData)) {
            $msg = "Undefined configuration section: '$section'";
            throw new Exception($msg);
        }

        return $this->iniData[$section];
    }
}
