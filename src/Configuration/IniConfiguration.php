<?php

namespace CCR\Configuration;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
     * @var string The path to the directory containing the partial
     * ini files.
     */
    protected string $dotDPath;

    /**
     * @var array The ini data from the ini file and all of the partial ini files.
     */
    protected array $iniData = [];

    public function __construct(
        #[Autowire(value: CONFIG_PORTAL_SETTINGS)]
        protected string          $iniFilePath,

        protected LoggerInterface $logger = new NullLogger(),
    )
    {
        $this->dotDPath = sprintf('%s/%s.d', dirname($this->iniFilePath), basename($this->iniFilePath, '.ini'));

        $this->load();
    }

    /**
     * Loads the information from the $iniFilePath file identified by theconstructor argument and if it exists,
     * a directory containing partial configuration files. This directory is derived from the directory that
     * $iniFilePath resides in, combined with the base name of $iniFilePath and '.d'.
     *
     * Example:
     * $iniFilePath = '/etc/xdmod/portal.ini';
     * $dotDPath = '/etc/xdmod/portal.d'
     *
     * @return void
     */
    private function load(): void
    {
        if (!is_readable($this->iniFilePath)) {
            throw new \RuntimeException(sprintf('Could not read settings file: %s', $this->iniFilePath));
        }

        $iniData = parse_ini_file($this->iniFilePath, true);
        if ($iniData === false) {
            throw new \RuntimeException(sprintf('Failed to parse settings file: %s', $this->iniFilePath));
        }

        $partialFilePaths = glob("{$this->dotDPath}/*.ini");

        foreach ($partialFilePaths as $partialFilePath) {
            if (!is_readable($partialFilePath)) {
                throw new \RuntimeException(sprintf('Could not read settings file: %s', $partialFilePath));
            }

            $partialIniData = parse_ini_file($partialFilePath, true);
            if ($partialIniData === false) {
                throw new \RuntimeException(sprintf('Failed to parse settings file: %s', $partialFilePath));
            }

            $iniData = array_merge($iniData, $partialIniData);
        }

        $this->iniData = $iniData;
    }

    /**
     * Retrieve either a section or a key from a section within an XDMoD configuration file / configuration directory.
     *
     * @param string $section Required. The section to retrieve if no key is provided, or the section
     *                        in which the provided key can be found.
     * @param string|null $key The key within the section to retrieve. If null explicitely
     *                         null or not provided, the entire section will be returned.
     * @param mixed|null $default If provided, and the requested section and or key do not exist,
     *                            or if the value of the key is null, then the default will be returned.
     * @return mixed
     *
     * @throws InvalidArgumentException If the section is not provided.
     */
    public function get(string $section, ?string $key = null, mixed $default = null): mixed
    {
        if (empty($section)) {
            throw new InvalidArgumentException('The section must be provided.');
        }

        // If the section doesn't exist then return the default
        if (!array_key_exists($section, $this->iniData)) {
            return $default;
        }

        // if they didn't provide a key then give them the whole section.
        if ($key === null) {
            return $this->iniData[$section];
        }

        // If the key doesn't exist then return the default
        if (!array_key_exists($key, $this->iniData[$section])) {
            return $default;
        }

        // else, return the value for the key unless it's null, then return the default
        return $this->iniData[$section][$key] ?? $default;
    }
}

