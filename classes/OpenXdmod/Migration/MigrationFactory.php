<?php
/**
 * Migration factory class.
 */

namespace OpenXdmod\Migration;

use CCR\Log;
use Psr\Log\LoggerInterface;

class MigrationFactory
{

    /**
     * Create a migration for the specified criteria.
     *
     * @param string $fromVersion The current Open XDMoD version.
     * @param string $toVersion The Open XDMoD version migrating to.
     * @param bool $updateConfigFiles True to migrate config files.
     * @param bool $updateDatabases True to migrate databases.
     * @param LoggerInterface|null $logger either a Monolog Logger to use or null, which indicates that a Null Logger will be used..
     *
     * @return Migration
     */
    public static function createMigration(
        $fromVersion,
        $toVersion,
        $updateConfigFiles = true,
        $updateDatabases = true,
        $logger = null
    ) {
        if ($logger === null) {
            $logger = Log::singleton('null');
        }

        $from = preg_replace('/[^0-9]/', '', $fromVersion);
        $to   = preg_replace('/[^0-9]/', '', $toVersion);

        $ns = '\\OpenXdmod\\Migration\\Version' . $from . 'To' . $to;

        $logger->debug("Using migration namespace '$ns'");

        $configFilesMigrationName = $ns . '\\' . 'ConfigFilesMigration';
        $databasesMigrationName   = $ns . '\\' . 'DatabasesMigration';

        $migrations = array();

        if ($updateConfigFiles && class_exists($configFilesMigrationName)) {
            $msg = "Using config files migration '$configFilesMigrationName'";
            $logger->debug($msg);

            $migrations[] = new $configFilesMigrationName(
                $fromVersion,
                $toVersion
            );
        }

        if ($updateDatabases) {
            if (class_exists($databasesMigrationName)) {
                $msg = "Using databases migration '$databasesMigrationName'";
                $logger->debug($msg);

                $migrations[] = new $databasesMigrationName(
                    $fromVersion,
                    $toVersion
                );

                // Add each of the other DatabasesMigration classes in the
                // namespace.
                $classes = array_map(
                    function ($file) use ($ns) {
                        return $ns . '\\' . rtrim(basename($file), '.php');
                    },
                    glob(__DIR__ . "/Version{$from}To$to/*.php")
                );
                $databasesMigrationClasses = array_filter(
                    $classes,
                    function ($class) use ($databasesMigrationName) {
                        return (
                            substr($class, -strlen('DatabasesMigration')) === 'DatabasesMigration'
                            && class_exists($class)
                            && $class !== $databasesMigrationName
                        );
                    }
                );
                sort($databasesMigrationClasses);
                foreach ($databasesMigrationClasses as $class) {
                    $logger->debug("Using databases migration '$class'");
                    $migrations[] = new $class($fromVersion, $toVersion);
                }
            }
            $migrations[] = new \OpenXdmod\Migration\Etlv2Migration($fromVersion, $toVersion);
        }

        $migrations[] = new AclConfigMigration($fromVersion, $toVersion);
        $migrations[] = new DotEnvConfigMigration($fromVersion, $toVersion);

        $migration = new CompositeMigration(
            $fromVersion,
            $toVersion,
            $migrations
        );

        $migration->setLogger($logger);

        return $migration;
    }
}
