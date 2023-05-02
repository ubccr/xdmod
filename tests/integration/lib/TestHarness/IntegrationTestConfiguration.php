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
 * directory (e.g., tests/artifacts/xdmod/integration), and properly
 * transforming "$ref" pointers whose values are fragments only (i.e., that
 * start with "#", i.e., that refer to a section within the configuration file
 * itself) to prepend the name of the configuration file, since the default
 * JsonReferenceTransformer would otherwise fail to parse such a value because
 * it has an empty URL path.
 *
 * For example, given the two files below:
 *
 * tests/artifacts/xdmod/integration/a.json: {
 *     "b": "c"
 * }
 *
 * d.json: {
 *     "e": {
 *         "$ref": "${INTEGRATION_ROOT}/a.json"
 *     },
 *     "f": {
 *         "$ref": "#/g/h"
 *     },
 *     "g": {
 *         "h": {
 *             "i": "j"
 *         }
 *     }
 * }
 *
 * The JSON object parsed from d.json would be:
 * {
 *     "e": {
 *         "b": "c"
 *     },
 *     "f": {
 *         "i": "j"
 *     },
 *     "g": {
 *         "h": {
 *             "i": "j"
 *         }
 *     }
 * }
 */
class IntegrationTestConfiguration extends Configuration
{
    /**
     * Load a JSON configuration file into an associative array, replacing the
     * string "${INTEGRATION_ROOT}" with the path to the integration test
     * artifacts root directory and properly parsing fragment-only references.
     *
     * @param string $filename the base configuration file to be processed.
     * @param TestFiles $testFiles used to get the path to the integration test
     *                             artifacts root directory.
     */
    public function defaultAssocArrayFactory($filename, $testFiles)
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
     * Add a transformer that will prepend the name of the configuration file
     * to references that contain only a fragment (e.g., "#/foo" becomes
     * "bar#/foo") before the JsonReferenceTransformer gets to them so it does
     * cause an error due to the URL paths being empty.
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
