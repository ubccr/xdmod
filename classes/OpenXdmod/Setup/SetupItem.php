<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use xd_utilities;
use CCR\Json;
use Xdmod\Template;

/**
 * Representation of a single setup item.
 */
abstract class SetupItem
{

    /**
     * @var Console
     */
    protected $console;

    /**
     * Constructor.
     *
     * @param Console $console The setup console.
     */
    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    /**
     * Handle the setup.
     */
    abstract public function handle();

    /**
     * Returns the path for a config file.
     *
     * @param string $file The config file name (excluding extension).
     * @param string $pkg The package the config file belongs to.
     *
     * @return string The file path.
     */
    protected function getJsonConfigFilePath($file, $pkg = null)
    {
        return $pkg === null
            ? sprintf('%s/%s.json',      CONFIG_DIR, $file)
            : sprintf('%s/%s.d/%s.json', CONFIG_DIR, $file, $pkg);
    }

    /**
     * Returns the path for a config file.
     *
     * @param string $file The config file name (excluding extension).
     * @param string $pkg The package the config file belongs to.
     *
     * @return string The file path.
     */
    protected function getIniConfigFilePath($file, $pkg = null)
    {
        return $pkg === null
            ? sprintf('%s/%s.ini',      CONFIG_DIR, $file)
            : sprintf('%s/%s.d/%s.ini', CONFIG_DIR, $file, $pkg);
    }

    /**
     * Load data from a JSON config file.
     *
     * @param string $file The config file name (excluding extension).
     * @param string $pkg The package the config file belongs to.
     *
     * @return array
     */
    protected function loadJsonConfig($file, $pkg = null)
    {
        $path = $this->getJsonConfigFilePath($file, $pkg);
        return $this->loadJsonFile($path);
    }

    /**
     * Load data from an INI config file.
     *
     * @param string $file The config file name (excluding extension).
     * @param string $pkg The package the config file belongs to.
     *
     * @return array
     */
    protected function loadIniConfig($file, $pkg = null)
    {
        $path = $this->getIniConfigFilePath($file, $pkg);
        return $this->loadIniFile($path);
    }

    /**
     * Load a JSON file.
     *
     * @param string $path JSON file path.
     *
     * @return array
     */
    protected function loadJsonFile($path)
    {
        try {
            $data = Json::loadFile($path);
        } catch (\Exception $e) {
            return array();
        }

        // Remove meta data, it's not used anymore.
        if (isset($data['meta'])) {
            unset($data['meta']);
        }

        return $data;
    }

    /**
     * Load an INI file.
     *
     * @param string $path INI file path.
     *
     * @return array
     */
    protected function loadIniFile($path)
    {
        $data = parse_ini_file($path, true);

        if ($data === false) {
            return array();
        }

        $settings = array();

        foreach ($data as $sectionName => $sectionData) {
            foreach ($sectionData as $key => $value) {
                $settings[$sectionName . '_' . $key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Save configuration data as JSON.
     *
     * @param array $data The config file data.
     * @param string $file The config file.
     * @param string $pkg The package the config file belongs to.
     */
    protected function saveJsonConfig(array $data, $file, $pkg = null)
    {
        $path = $this->getJsonConfigFilePath($file, $pkg);
        $this->saveJsonConfigFile($path, $data);
    }

    /**
     * Save configuration data in an INI file.
     *
     * NOTE: The INI file must have a corresponding template.
     *
     * @param array $data The config file data.
     * @param string $file The config file.
     * @param string $pkg The package the config file belongs to.
     */
    protected function saveIniConfig(array $data, $file, $pkg = null)
    {
        $template = new Template($file, $pkg);
        $template->apply($data);

        $path = $this->getIniConfigFilePath($file, $pkg);
        $this->saveTemplate($template, $path);

        // Need to clear config cache so that any changes are returned by
        // future calls to xd_utilities\getConfiguration, etc.
        xd_utilities\clearConfigurationCache();
    }

    /**
     * Save a JSON configuration file.
     *
     * @param string $path The config file path.
     * @param array $data The config file data.
     */
    protected function saveJsonConfigFile($path, array $data)
    {
        $this->saveConfigFile(
            $path,
            Json::prettyPrint(json_encode($data))
        );
    }

    /**
     * Save a templated configuration file.
     *
     * @param Template $template The config file template.
     * @param string $path The config file path.
     */
    protected function saveTemplate(Template $template, $path)
    {
        $this->saveConfigFile($path, $template->getContents());
    }

    /**
     * Prompt and save a configuration file.
     *
     * @param string $path The config file path.
     * @param string $contents The config file contents.
     */
    protected function saveConfigFile($path, $contents)
    {
        $this->console->displayBlankLine();

        $confirm = $this->console->prompt(
            "Overwrite config file '$path'?",
            'yes',
            array('yes', 'no')
        );

        if ($confirm !== 'yes') {
            $this->console->displayMessage('Changes NOT saved!');
            return;
        }

        $this->console->displayMessage("Writing configuration to '$path'");
        $this->console->displayBlankLine();

        if (file_put_contents($path, $contents) === false) {
            $this->console->displayBlankLine();
            $this->console->displayMessage('Failed to write config file.');
            $this->console->displayBlankLine();
            $this->console->prompt('Press ENTER to continue.');
            return;
        }

        $this->console->displayMessage('Settings saved.');
        $this->console->displayBlankLine();
        $this->console->prompt('Press ENTER to continue.');
    }

    /**
     * Execute a command.
     *
     * @param string $command The command to execute.  Must already be
     *     escaped.
     *
     * @throws Exception If the command exits with a non-zero return status.
     *
     * @return array The command output.
     */
    protected function executeCommand($command)
    {
        $output    = array();
        $returnVar = 0;

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar != 0) {
            $msg = "Command exited with non-zero return status:\n"
                . "command = $command\noutput =\n" . implode("\n", $output);
            throw new \Exception($msg);
        }

        return $output;
    }
}
