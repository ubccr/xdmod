<?php
/**
 * XDMoD versioning related functions.
 *
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace Xdmod;

use Exception;
use CCR\DB;
use CCR\DB\MySQLHelper;
use CCR\Json;
use xd_utilities;

/**
 * XDMoD versioning related functions.
 */
class Version
{

    /**
     * Stores latest version string to avoid repeated queries of remote
     * server.
     *
     * @var string
     */
    protected static $latestVersion;

    /**
     * Array of MySQLHelper objects.
     *
     * @var MySQLHelper[]
     */
    protected static $databaseHelpers = array();

    /**
     * Name of the schema version history table.
     *
     * @var string
     */
    protected static $versionTableName = 'schema_version_history';

    /**
     * HTTP query timeout length in seconds.
     *
     * @var int
     */
    protected static $timeout = 3;

    /**
     * Logger instance.
     *
     * @var \Log
     */
    protected static $logger = null;

    /**
     * Get a MySQL helper for the specified database.
     *
     * @param string $configSection Config section name.
     *
     * @return \CCR\DB\MySQLHelper
     */
    protected static function getDatabaseHelper($configSection)
    {
        if (!isset(static::$databaseHelpers[$configSection])) {
            $db = DB::factory($configSection);
            $helper = MySQLHelper::factory($db);
            $helper->setLogger(static::$logger);
            static::$databaseHelpers[$configSection] = $helper;
        }

        return static::$databaseHelpers[$configSection];
    }

    /**
     * Set the HTTP query timeout.
     *
     * @param int $timeout Timeout length in seconds.
     */
    public static function setTimeout($timeout)
    {
        static::$timeout = $timeout;
    }

    /**
     * Set the logger.
     *
     * @param \Log Logger instance.
     */
    public static function setLogger(\Log $logger)
    {
        static::$logger = $logger;

        foreach (static::$databaseHelpers as $helper) {
            $helper->setLogger($logger);
        }
    }

    /**
     * Get the current version number.
     *
     * @param bool $useConst Use the OPEN_XDMOD_VERSION constant to
     *     determine the version number if possible.  During the upgrade
     *     process portal_settings.ini will still have the old version
     *     number, but the constant will be the new version number.
     *     Therefore, the constant should be used when checking if a
     *     newer version is available, but the value from
     *     portal_settings.ini should be used to determine what needs
     *     to be done during the upgrade process.
     *
     * @return string The current version number of Open XDMoD.
     */
    public static function getCurrentVersionNumber($useConst = true)
    {
        if ($useConst && defined('OPEN_XDMOD_VERSION')) {
            return OPEN_XDMOD_VERSION;
        }

        return xd_utilities\getConfiguration('general', 'version');
    }

    /**
     * Query remote server for the latest version number.
     *
     * @param array $args Additional query arguments (optional).
     *
     * @return string The current version string.
     */
    public static function getLatestVersionNumber(array $args = array())
    {
        if (isset(static::$latestVersion)) {
            return static::$latestVersion;
        }

        $scheme = 'https';
        $host   = 'xdmod.ccr.buffalo.edu';
        $port   = null;
        $path   = '/rest/v0.1/versions/current';

        $url = $scheme . '://' . $host;

        if (isset($port) && is_numeric($port)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        if (!isset($args['open-xdmod'])) {
            $args['open-xdmod'] = true;
        }

        if (count($args) > 0) {
            $url .= '?' . http_build_query($args);
        }

        $contextOpts = array(
            'http' => array(
                'timeout'    => static::$timeout,
                'user_agent' => 'XDMoD',
            ),
        );

        $context = stream_context_create($contextOpts);

        // Perform the query.
        $json = @file_get_contents($url, false, $context);

        if ($json === false) {
            throw new Exception('Failed to query server');
        } elseif ($json === '') {
            throw new Exception('No data returned from server');
        }

        $data = json_decode($json, true);

        if ($data === null) {
            $msg = "Failed to decode JSON '$json': "
                . Json::getLastErrorMessage();
            throw new Exception($msg);
        }

        if (!isset($data['success'])) {
            throw new Exception("No success value found in JSON '$json'");
        }

        if (isset($data['success']) && !$data['success']) {
            $msg = 'Failed to retrieve version data';

            if (isset($data['message'])) {
                $msg .= ': ' . $data['message'];
            }

            throw new Exception($msg);
        }

        if (!isset($data['results'])) {
            throw new Exception("No results value found in JSON '$json'");
        }

        static::$latestVersion = $data['results'];

        return static::$latestVersion;
    }

    /**
     * Check if the version is supported.
     *
     * A version is considered "supported" if it is not an alpha or beta
     * release and the version number is formatted as "x.y.z" where x, y
     * and z are all integers.
     *
     * @param string $version
     *
     * @return bool
     */
    public static function isSupportedVersion($version)
    {
        $versionParts = explode('.', $version);

        // All version numbers should be in "x.y.z" format.
        if (count($versionParts) != 3) {
            return false;
        }

        // Exclude alpha and beta versions.
        foreach ($versionParts as $part) {
            if (!preg_match('/^\d+$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare the current version with the latest.
     *
     * If either the current or latest version is not supported
     *
     * @param array $args Additional arguments to use when querying for
     *     the latest version number (optional).
     *
     * @return bool True if a newer version is available.
     */
    public static function isNewerVersionAvailable(array $args = array())
    {
        $currentVersion = static::getCurrentVersionNumber();

        $args['current_version'] = $currentVersion;
        $latestVersion = static::getLatestVersionNumber($args);

        if (!static::isSupportedVersion($currentVersion)) {
            return false;
        }

        if (!static::isSupportedVersion($latestVersion)) {
            return false;
        }

        // Compare version numbers.  Assumes a longer version is newer.
        return
            strlen($latestVersion) > strlen($currentVersion)
            || strcmp($latestVersion, $currentVersion) > 0;
    }

    /**
     * Get the current version of a database.
     *
     * @param string $configSection Config section name.
     *
     * @return string
     */
    public static function getDatabaseVersion($configSection)
    {
        $helper = static::getDatabaseHelper($configSection);

        $dbName = $helper->getDatabaseName();

        // If the schema version history table doesn't exist assume that
        // we're using the first public release.
        if (!$helper->tableExists(static::$versionTableName)) {
            return '3.5.0';
        }

        $sql = "
            SELECT schema_version
            FROM " . static::$versionTableName . "
            WHERE database_name = :db_name
            ORDER BY action_datetime DESC
            LIMIT 1
        ";

        $db = $helper->getDatabase();

        $results = $db->query($sql, array('db_name' => $dbName));

        // If no version is found, assume that we're using the first
        // public release.
        if (count($results) === 0) {
            return '3.5.0';
        }

        return $results[0]['schema_version'];
    }

    /**
     * Update the version of a database.
     *
     * @param string $configSection Config section name.
     * @param string $version The new version.
     */
    public static function updateDatabaseVersion($configSection, $version)
    {
        $helper = static::getDatabaseHelper($configSection);

        $dbName = $helper->getDatabaseName();

        if (!$helper->tableExists(static::$versionTableName)) {
            static::createDatabaseVersionTable($dbName);
        }

        $sql = "
            INSERT INTO " . static::$versionTableName . " SET
                database_name = :db_name,
                schema_version = :version,
                action_datetime = NOW(),
                action_type = 'upgraded',
                script_name = :script_name
        ";

        $db = $helper->getDatabase();

        $db->execute(
            $sql,
            array(
                'db_name'     => $dbName,
                'version'     => $version,
                'script_name' => $_SERVER['PHP_SELF'],
            )
        );
    }

    /**
     * Create the table that stores database version history if it
     * doesn't already exist.  Optionally, add a record for the current
     * database version.
     *
     * @param string $configSection Config section name.
     * @param string $version The current database version (optional).
     *
     * @throws Exception If version table already exists.
     */
    public static function createDatabaseVersionTable(
        $configSection,
        $version = null
    ) {
        $helper = static::getDatabaseHelper($configSection);

        $dbName = $helper->getDatabaseName();

        if ($helper->tableExists(static::$versionTableName)) {
            $msg = '"Database version history table ('
                . static::$versionTableName . ') already exists';
            throw new Exception($msg);
        }

        $helper->createTable(
            static::$versionTableName,
            array(
                array(
                    'name' => 'database_name',
                    'type' => 'char(64)',
                ),
                array(
                    'name' => 'schema_version',
                    'type' => 'char(64)',
                ),
                array(
                    'name' => 'action_datetime',
                    'type' => 'datetime',
                ),
                array(
                    'name' => 'action_type',
                    'type' => "enum('created','upgraded')",
                ),
                array(
                    'name' => 'script_name',
                    'type' => 'varchar(255)',
                ),
            ),
            array(
                array(
                    'type'    => 'primary',
                    'columns' => array(
                        'database_name',
                        'schema_version',
                        'action_datetime',
                    ),
                ),
            )
        );

        if ($version === null) {
            return;
        }

        $sql = "
            INSERT INTO " . static::$versionTableName . " SET
                database_name = :db_name,
                schema_version = :version,
                action_datetime = NOW(),
                action_type = 'created',
                script_name = :script_name
        ";

        $db = $helper->getDatabase();

        $db->execute(
            $sql,
            array(
                'db_name'     => $dbName,
                'version'     => $version,
                'script_name' => $_SERVER['PHP_SELF'],
            )
        );
    }
}
