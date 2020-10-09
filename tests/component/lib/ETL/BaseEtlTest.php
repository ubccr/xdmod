<?php
/**
 * @package OpenXdmod\ComponentTests
 * @author Steven M. Gallo <smgallo@buffalo.edu>
 */

namespace ComponentTests\ETL;

use CCR\Logging;
use ETL\EtlOverseerOptions;
use ETL\Configuration\EtlConfiguration;
use ETL\aAction;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Base class for ETL tests providing common functionality.
 */

abstract class BaseEtlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface
     */
    protected static $logger = null;

    /**
     * Create a logger object for use when running ETL tests.
     *
     * @param string $ident Log identifier string
     * @param int $level Maximum log level to display
     */

    protected static function createLogger($ident = 'PHPUnit', $level = Logger::WARNING)
    {
        self::$logger = Logging::factory($ident, array('console' => array('level' => $level)));
    }

    /**
     * Create a temporary directory.
     *
     * @param string $prefix A string prefix to use when creating the temporary directory.
     *
     * @return string The path to the temporary directory
     */

    protected static function createTempDir($prefix)
    {
        $tmpDir = sprintf("%s/%s_%d_%d", sys_get_temp_dir(), $prefix, getmypid(), time());
        if ( false === @mkdir($tmpDir) ) {
            $err = error_get_last();
            error_log(sprintf("Could not create temporary directory %s: %s", $tmpDir, $err['message']));
            return false;
        }
        return $tmpDir;
    }

    /**
     * Directly execute an ETL action.
     *
     * @param string $actionName The name of the action to execute
     * @param EtlConfiguration $etlConfig An object describing the current ETL configuration
     * @param EtlOverseerOptions $overseerOptions Overseer options
     */

    protected function executeEtlAction(
        $actionName,
        EtlConfiguration $etlConfig,
        EtlOverseerOptions $overseerOptions
    ) {
        $action = aAction::factory($etlConfig, $actionName, self::$logger);
        $action->execute($overseerOptions);
    }
}
