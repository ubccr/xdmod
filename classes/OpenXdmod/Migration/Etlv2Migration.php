<?php
/**
 * Runs all etlv2 migration sections. The sections are chosen based
 * on the section name. The name must end in the string 'migration-X_Y_Z-A_B_C'
 * for migrations from XDMoD version X.Y.Z to A.B.C
 *
 * The migrations are processed in alphabetical order so the Aaaron Aaardvark migration
 * will run before the Zysel Zywicki one.
 *
 */

namespace OpenXdmod\Migration;

use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;
use ETL\EtlOverseer;

class Etlv2Migration extends Migration
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $etlConfig = new EtlConfiguration(
            CONFIG_DIR . '/etl/etl.json',
            null,
            $this->logger,
            array('default_module_name' => 'xdmod')
        );
        $etlConfig->initialize();
        Utilities::setEtlConfig($etlConfig);

        $sectionFilter = 'migration-' . str_replace('.', '_', $this->currentVersion) . '-' . str_replace('.', '_', $this->newVersion);

        $scriptOptions = array(
            'process-sections' => array()
        );

        foreach($etlConfig->getSectionNames() as $sectionName) {
            if (strpos($sectionName, $sectionFilter) === (strlen($sectionName) - strlen($sectionFilter))) {
                $scriptOptions['process-sections'][] = $sectionName;
            }
        }

        if (empty($scriptOptions['process-sections'])) {
            return;
        }

        sort($scriptOptions['process-sections']);

        $overseerOptions = new EtlOverseerOptions($scriptOptions, $this->logger);
        $overseer = new EtlOverseer($overseerOptions, $this->logger);
        $overseer->execute($etlConfig);
    }
}
