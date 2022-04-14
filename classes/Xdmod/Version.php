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
use Psr\Log\LoggerInterface;
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
     * HTTP query timeout length in seconds.
     *
     * @var int
     */
    protected static $timeout = 3;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    protected static $logger = null;


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
     * @param LoggerInterface $logger A Monolog Logger instance.
     */
    public static function setLogger(LoggerInterface $logger)
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

        return version_compare($latestVersion, $currentVersion, '>');
    }
}
