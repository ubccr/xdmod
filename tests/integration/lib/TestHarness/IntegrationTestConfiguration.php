<?php

namespace TestHarness;

use Configuration\Configuration;
use ETL\VariableStore;
use Psr\Log\LoggerInterface;
use stdClass;
use TestHarness\TestFiles;

/**
 * Read and parse a JSON configuration file, replacing the string
 * "${INTEGRATION_ROOT}" with the path to the integration test artifacts root
 * directory (e.g., tests/artifacts/xdmod/integration), and using
 * JsonFragmentOnlyReferenceTransformer.
 */
class IntegrationTestConfiguration extends Configuration
{
    /**
     * Load a JSON configuration file into an associative array, replacing the
     * string "${INTEGRATION_ROOT}" with the path to the integration test
     * artifacts root directory.
     *
     * @param string $filename the base configuration file to be processed.
     * @param TestFiles $testFiles used to get the path to the integration test
     *                             artifacts root directory.
     */
    public static function defaultAssocArrayFactory($filename, $testFiles)
    {
        return parent::assocArrayFactory(
            $filename,
            null, // Use the file's dirname as its directory.
            null, // No logger is needed.
            [
                'variable_store' => new VariableStore([
                    'INTEGRATION_ROOT' =>
                    $testFiles->getFile(
                        'integration',
                        '',
                        '',
                        ''
                    )
                ])
            ]
        );
    }

    /**
     * @see Configuration::preTransformTasks()
     */
    protected function preTransformTasks()
    {
        $this->addKeyTransformer(
            new JsonFragmentOnlyReferenceTransformer($this->logger)
        );
        parent::preTransformTasks();
        return $this;
    }
}
