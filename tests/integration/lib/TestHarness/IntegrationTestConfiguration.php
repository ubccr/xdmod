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
 * @see JsonSchemaAnchorReferenceTransformer.
 */
class IntegrationTestConfiguration extends Configuration
{
    private $numSchemasSeen = 0;

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
     * @return bool true if we are currently inside a schema while running
     * @see processKeyTransformers().
     */
    public function inSchema()
    {
        return $this->numSchemasSeen > 0;
    }

    /**
     * Add the @see JsonSchemaAnchorReferenceTransformer.
     */
    protected function preTransformTasks()
    {
        $this->addKeyTransformer(
            new JsonSchemaAnchorReferenceTransformer($this->logger)
        );
        parent::preTransformTasks();
        return $this;
    }

    /**
     * If the given object has a '$schema' property, increment the number of
     * schemas seen before transforming it, so that the
     * @see JsonSchemaAnchorReferenceTransformer can do its transformations,
     * and then decrement the number of schemas seen after transforming it.
     *
     * @param stdClass $obj the object to transform.
     * @return stdClass the transformed object.
     */
    protected function processKeyTransformers(stdClass $obj)
    {
        if (property_exists($obj, '$schema')) {
            $this->numSchemasSeen++;
        }
        $obj = parent::processKeyTransformers($obj);
        if (property_exists($obj, '$schema')) {
            $this->numSchemasSeen--;
        }
        return $obj;
    }
}
