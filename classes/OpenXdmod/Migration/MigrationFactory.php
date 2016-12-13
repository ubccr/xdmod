<?php
/**
 * Migration factory class.
 */

namespace OpenXdmod\Migration;

class MigrationFactory
{

    /**
     * Create a migration for the specified criteria.
     *
     * @param string $fromVersion The current Open XDMoD version.
     * @param string $toVersion The Open XDMoD version migrating to.
     * @param bool $updateConfigFiles True to migrate config files.
     * @param bool $updateDatabases True to migrate databases.
     * @param \Log $logger Logger to use.
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
            $logger = \Log::singleton('null');
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

        if ($updateDatabases && class_exists($databasesMigrationName)) {
            $msg = "Using databases migration '$databasesMigrationName'";
            $logger->debug($msg);

            $migrations[] = new $databasesMigrationName(
                $fromVersion,
                $toVersion
            );
        }

        $migration = new CompositeMigration(
            $fromVersion,
            $toVersion,
            $migrations
        );

        $migration->setLogger($logger);

        return $migration;
    }
}
