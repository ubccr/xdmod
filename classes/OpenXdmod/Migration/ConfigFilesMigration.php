<?php
/**
 * Abstract base class for migrating config files.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Migration;

use Exception;
use CCR\Json;
use Xdmod\Template;

abstract class ConfigFilesMigration extends Migration
{

    /**
     * The full path of the portal_settings.ini file.
     *
     * @var string
     */
    protected $portalSettingsPath = CONFIG_PORTAL_SETTINGS;

    /**
     * The full path of each module's portal_settings.d file keyed by
     * the module name.
     *
     * This excludes the primary Open XDMoD module, typically referred
     * to as "xdmod".
     *
     * @var array
     */
    protected $modulePortalSettingsPaths = array();

    /**
     * @inheritdoc
     */
    public function __construct($currentVersion, $newVersion)
    {
        parent::__construct($currentVersion, $newVersion);

        // Determine what modules are installed.  There is no
        // standardized way of doing so at this time, but since this is
        // a configuration file migration, the presence of configuration
        // files is used to indicate a module has been installed.

        $portalSettingsDir = preg_replace(
            '/\\.ini$/',
            '.d',
            $this->portalSettingsPath
        );
        $filePaths = glob("$portalSettingsDir/*.ini");
        sort($filePaths);

        foreach ($filePaths as $file) {
            $moduleName = pathinfo($file, PATHINFO_FILENAME);

            // Make sure this file belongs to an actual module by
            // checking for the existence of the corresponding template.
            try {
                $template = new Template('portal_settings', $moduleName);
            } catch (Exception $e) {
                continue;
            }

            $this->modulePortalSettingsPaths[$moduleName] = $file;
        }
    }

    /**
     * Write to the portal_settings.ini file.
     *
     * Uses the portal_settings.template file and applies any new data.
     * If no data is specified, only updates the version number.
     *
     * @param array $changes Values that should be changed in
     *     portal_settings.ini.
     */
    protected function writePortalSettingsFile(array $changes = array())
    {
        $this->writeModulePortalSettingsFile('xdmod', $changes);
    }

    /**
     * Write to the each module portal_settings.d file.
     *
     * @param array $changes Each key is a module name and corresponding
     *     value is an array of values that should be changed in the
     *     portal_settings.d file for that module.
     */
    protected function writeModulePortalSettingsFiles(array $changes = array())
    {
        foreach ($this->modulePortalSettingsPaths as $moduleName => $file) {
            $moduleChanges
                = isset($changes[$moduleName])
                ? $changes[$moduleName]
                : array();
            $this->writeModulePortalSettingsFile($moduleName, $moduleChanges);
        }
    }

    /**
     * Write a single portal_settings file.
     *
     * Automatically updates the version number in the file using the
     * version number specified by the migration.
     *
     * The module named "xdmod" is handled differently since it uses the
     * portal_settings.ini file and not a file in portal_settings.d.
     *
     * Uses the portal_settings.template file and applies any new data.
     * If no data is specified, only updates the version number.
     *
     * @param string $moduleName The name of the module (i.e. "xdmod",
     *     "supremm" or "appkernels").
     * @param array $changes Values that should be changed in the
     *     portal_settings file.
     */
    protected function writeModulePortalSettingsFile(
        $moduleName,
        array $changes = array()
    ) {
        $portalSettingsPath
            = $moduleName === 'xdmod'
            ? $this->portalSettingsPath
            : $this->modulePortalSettingsPaths[$moduleName];

        $data = parse_ini_file($portalSettingsPath, true);

        if ($data === false) {
            $msg = "Failed to parse '$portalSettingsPath'";
            throw new Exception($msg);
        }

        $settings = array();

        // Store all the current key/value pairs from the current
        // portal_settings file.
        foreach ($data as $sectionName => $sectionData) {
            foreach ($sectionData as $key => $value) {
                $settings[$sectionName . '_' . $key] = $value;
            }
        }

        // Overwrite or add any new values.
        foreach ($changes as $key => $value) {
            $settings[$key] = $value;
        }

        // Every module should have a version in its general section.
        $versionKey
            = ($moduleName === 'xdmod' ? '' : $moduleName . '-')
            . 'general_version';
        $settings[$versionKey] = $this->newVersion;

        $settingsTemplate = new Template(
            'portal_settings',
            $moduleName === 'xdmod' ? null : $moduleName
        );

        $settingsTemplate->apply($settings);
        $settingsTemplate->saveTo($portalSettingsPath);
    }

    protected function writeFile($filePath, array $data)
    {
        $json = Json::prettyPrint(json_encode($data));
        if (file_put_contents($filePath, $json) === false) {
            throw new Exception("Failed to write to file '$filePath'");
        }
    }

    /**
     * Write the contents of a config file.
     *
     * @param string $name The config file name (without ".json").
     * @param array $data The data to store in the config file.
     */
    protected function writeJsonConfigFile($name, array $data)
    {
        $json = Json::prettyPrint(json_encode($data));

        $file = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->config->getBaseDir(),
                "$name.json"
            )
        );

        if (file_put_contents($file, $json) === false) {
            throw new Exception("Failed write to file '$file'");
        }
    }

    /**
     * Check if portal_settings.ini is writable.
     *
     * @throws Exception if portal_settings.ini is not writable.
     */
    protected function assertPortalSettingsIsWritable()
    {
        $file = $this->portalSettingsPath;

        if (!is_writable($file)) {
            throw new Exception("Cannot write to file '$file'");
        }
    }

    /**
     * Check if portal_settings.d files are writable.
     *
     * @throws Exception if any of the files are not writable.
     */
    protected function assertModulePortalSettingsAreWritable()
    {
        foreach ($this->modulePortalSettingsPaths as $file) {
            if (!is_writable($file)) {
                throw new Exception("Cannot write to file '$file'");
            }
        }
    }


    /**
     * Check if the file referenced by the provided '$filePath' is writable.
     *
     * @param string $filePath the path to the file to be checked.
     *
     * @throws Exception if the file is not writable
     */
    protected function assertFileIsWritable($filePath) {
        if (!is_writable($filePath)){
            throw new Exception("Cannot write to file '$filePath'");
        }
    }

    /**
     * Check if a JSON config file is writable.
     *
     * @throws Exception if portal_settings.ini is not writable.
     */
    protected function assertJsonConfigIsWritable($name)
    {
        $file = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->config->getBaseDir(),
                "$name.json"
            )
        );

        if (!is_writable($file)) {
            throw new Exception("Cannot write to file '$file'");
        }
    }
}
