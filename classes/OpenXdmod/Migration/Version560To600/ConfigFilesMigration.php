<?php
/**
 * @author Ryan Rathsam<ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Migration\Version560To600;

use CCR\Json;
use CCR\Log;
use Exception;
use xd_utilities;
use OpenXdmod\Setup\Console;

/**
 * Update config files from version 5.6.0 to 6.0.0.
 */
class ConfigFilesMigration extends \OpenXdmod\Migration\ConfigFilesMigration
{

    private $verbose;

    /**
     * Added an optional 'verbose' construction parameter. If set to true
     * this migration will log information at various steps in its execution.
     *
     * NOTE: the static access warnings pertain to the use of:
     *   - parent::construct
     *   - \Log::singleton
     *   - \CCR\Log::INFO
     * all of which are perfectly reasonable in this case.
     * @SuppressWarnings(@PHPMD.StaticAccess)
     *
     * @param string $currentVersion
     * @param string $newVersion
     * @param bool   $verbose
     */
    public function __construct(
        $currentVersion,
        $newVersion,
        $verbose = false
    ) {
        parent::__construct($currentVersion, $newVersion);

        $this->verbose = $verbose;

        // We override the null logger in the interest of having visible logging.
        $this->logger = \Log::singleton('console', array('consoleLogLevel' => Log::INFO));
    }
    /**
     * Execute the migration.
     **/
    public function execute()
    {

        // Make sure all the config files that will be changed are
        // writable.
        $this->assertPortalSettingsIsWritable();
        $this->assertModulePortalSettingsAreWritable();

        // Set new options in portal_settings.ini.
        $this->writePortalSettingsFile(array(
            'general_maintainer_email_signature' => '',
        ));

        $this->writeModulePortalSettingsFiles(array(
            'supremm' => array(
                'supremm-general_schema_file' => 'etl.schema.js',
            ),
        ));

        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $json = $this->modifyFile($file);
            $this->writeFile($file, $json);
        }
    }

    /**
     * Modify the provided config 'file' to bring it in line with what is
     * required for version 6.0.0.
     *
     * @param string $file filepath that will be modified so that it will
     *                     contain the correct information for v6.0.0
     *
     * @return array|object the newly modified file contents.
     *
     * @throws Exception
     */
    public function &modifyFile($file)
    {
        $this->assertFileIsWritable($file);
        $json = Json::loadFile($file);

        $roleKeys = $this->getFiltersFor("roles");
        $permittedModuleKeys = $this->getFiltersFor("permitted_modules");

        $foundRoleKeys = array_intersect(array_keys($json), $roleKeys);
        if (count($foundRoleKeys) < 1) {
            if ($this->verbose === true) {
                $this->logger->warning("Malformed roles file: $file. No roles property.");
            }
        }

        $roleKey = array_shift($foundRoleKeys);

        $this->searchRoles($json[$roleKey], $permittedModuleKeys, basename($file));

        return $json;
    }

    /**
     * Helper function that attempts to find a key in the provided 'roles'
     * array that can also be found in the provided 'keys' array. If found,
     * it passes the value associated with the found key as well as the
     * modifications to be made to 'searchPermittedModules' function.
     *
     * NOTE: There is no return value as changes are made inline and further
     * the line.
     *
     * @param array $roles the roles to be searched for permitted modules.
     * @param array $keys  the keys that will be used in the search.
     * @param string $file the name of the file that is currently being searched.
     * @throws Exception
     */
    private function searchRoles(array &$roles, array $keys, $file)
    {
        $modifications = $this->getModifications();
        foreach ($roles as $role => &$roleDef) {
            $foundKeys = array_intersect(array_keys($roleDef), $keys);
            if (count($foundKeys) < 1) {
                if ($this->verbose === true) {
                    $this->logger->info("No 'permitted_modules' found for [$role] in [$file], continuing...");
                }
                continue;
            }
            $permittedModules = array_shift($foundKeys);
            $this->searchPermittedModules($roleDef[$permittedModules], $modifications);
        }
    }

    /**
     * Helper function that performs the actual modifications required for this
     * migration. It expects that '$permittedModules' will be an array of
     * arrays. Each sub-array is required to contain a 'name' parameter. This
     * name will then be used to look up the appropriate modifications to be
     * made. If found, the modifications then be merged into module and
     * passed through a validation function, which at a minimum just checks that
     * the modifications were in fact applied. If the sub-array was successfully
     * validated then the modified sub-array is saved back into
     * permittedModules.
     *
     *
     * @param array $permittedModules the array of modules that should be
     *                                modified.
     * @param array $modifications    the modifications that are to be made.
     *
     * @throws Exception if the sub-array is missing the 'name' parameter.
     */
    private function searchPermittedModules(array &$permittedModules, array $modifications)
    {
        foreach ($permittedModules as $i => $module) {
            if (!isset($module['name'])) {
                throw new \Exception("'name' property missing -> permitted_modules -> $i");
            }

            $name = preg_replace("/^\+.*$/", "", $module['name']);
            if (!array_key_exists($name, $modifications)) {
                if ($this->verbose === true) {
                    $this->logger->warning("Unsupported module found: $name, manual upgrade required.");
                }
                continue;
            }
            $moduleMods = isset($modifications[$name]) ? $modifications[$name] : array();
            $modified = array_merge($module, $moduleMods);
            if ($this->validateModification($modified, $moduleMods)) {
                $permittedModules[$i] = $modified;
            };
        }

    }

    /**
     * Default implementation of the validation function. Just checks that the
     * modification merge was successful, if not it throws an exception.
     *
     * @param array $modified      the merged dataset to be validated.
     * @param array $modifications the modifications that were merged.
     * @return bool                true if merge was successful.
     * @throws Exception if the merge was not successful.
     */
    public function validateModification(array $modified, array $modifications)
    {
        $intersection = array_intersect_assoc($modified, $modifications);
        ksort($intersection);
        ksort($modifications);
        $valid = $intersection === $modifications;
        if ($valid !== true) {
            $msg = implode(
                "\n",
                array(
                    "Did not successfully merge the modifications.",
                    "Expected: ",
                    Json::prettyPrint(json_encode($modifications)),
                    "Found: ",
                    Json::prettyPrint(json_encode($intersection)),
                )
            );
            throw new Exception($msg);
        }
        return true;
    }

    /**
     * Helper function that retrieves a list of the file paths this migration
     * operates on ('roles').
     *
     * @return array
     * @throws Exception
     */
    public function getSourceFiles()
    {
        $localConfigDir = implode(
            DIRECTORY_SEPARATOR,
            array(
                $this->config->getBaseDir(),
                'roles.d'
            )
        );
        $localConfigFiles = array();
        if (is_dir($localConfigDir)) {
            $localConfigFiles = glob("$localConfigDir/*.json");
            sort($localConfigFiles);
        }

        return array_merge(
            array(
                implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        $this->config->getBaseDir(),
                        'roles.json'
                    )
                )
            ),
            $localConfigFiles
        );
    }

    /**
     * Helper function that just helps with defining filters for a given term.
     *
     * @param string $term
     * @return array
     */
    public function getFiltersFor($term)
    {
        return array(
            $term,
            "+$term"
        );
    }

    /**
     * Getter for the modifications that are to be applied during this
     * migration.
     *
     * @return array
     */
    public function getModifications()
    {
        return $this->modifications;
    }

    private $modifications = array(
        'tg_summary' => array(
            'javascriptClass'       => 'XDMoD.Module.Summary',
            'javascriptReference'   => 'CCR.xdmod.ui.tgSummaryViewer',
            'tooltip'               => 'Displays summary information',
            'userManualSectionName' => 'Summary Tab'
        ),
        'tg_usage' => array(
            'javascriptClass'       => 'XDMoD.Module.Usage',
            'javascriptReference'   => 'CCR.xdmod.ui.chartViewerTGUsage',
            'tooltip'               => 'Displays usage',
            'userManualSectionName' => 'Usage Tab'
        ),
        'metric_explorer' => array(
            'javascriptClass'       => 'XDMoD.Module.MetricExplorer',
            'javascriptReference'   => 'CCR.xdmod.ui.metricExplorer',
            'tooltip'               => '',
            'userManualSectionName' => 'Metric Explorer'
        ),
        'about_xdmod' => array(
            'javascriptClass'       => 'XDMoD.Module.About',
            'javascriptReference'   => 'CCR.xdmod.ui.aboutXD',
            'tooltip'               => '',
            'userManualSectionName' => 'About'
        ),
        'app_kernels' => array(
            'javascriptClass'       => 'XDMoD.Module.AppKernels',
            'javascriptReference'   => 'CCR.xdmod.ui.appKernels',
            'tooltip'               => 'Displays data referencing the reliability and performance of grid resources',
            'userManualSectionName' => 'App Kernels'
        ),
        'report_generator' => array(
            'javascriptClass'       => 'XDMoD.Module.ReportGenerator',
            'javascriptReference'   => 'CCR.xdmod.ui.reportGenerator',
            'tooltip'               => '',
            'userManualSectionName' => 'Report Generator'
        ),
        'job_viewer' => array(
            'javascriptClass'       => 'XDMoD.Module.JobViewer',
            'javascriptReference'   => 'CCR.xdmod.ui.jobViewer',
            'tooltip'               => '',
            'userManualSectionName' => 'Job Viewer'
        )
    );
}
