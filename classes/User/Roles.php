<?php namespace User;

use Configuration\XdmodConfiguration;

/**
 * Made so that we can have access to aRole::getConfig without having to change anything in aRole.
 **/
class Roles
{

    /**
     * @var XdmodConfiguration
     */
    private static $config ;

    /**
     * Attempt to retrieve a list of names for the roles currently installed in the system.
     *
     * @param array $blacklist if specified, any entries in this array will not be included in the
     *                         final result.
     *
     * @return array
     *
     * @throws \Exception If there is a problem reading / processing the underlying `roles.json`
     *                    configuration data.
     */
    public static function getRoleNames(array $blacklist = array())
    {
        return  array_filter(
            array_keys(self::getConfigData()),
            function ($item) use ($blacklist) {
                return !in_array($item, $blacklist);
            }
        );
    }

    /**
     * Retrieve the 'roles' configuration data. This will include any information provided in the
     * `roles.d` local configuration folder in addition to the processing of any `extends`
     * properties found.
     *
     * @return array
     *
     * @throws \Exception if there is a problem reading / processing the underlying `roles.json`
     *                    configuration data
     */
    protected static function getConfigData()
    {
        if (!isset(self::$config)) {
            self::$config = XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR)['roles'];
        }

        return self::$config;
    }
}
