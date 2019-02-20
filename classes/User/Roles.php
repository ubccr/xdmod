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
     * Attempt to retrieve the values located in roles.json
     * or roles.d/<module>.json that correspond to the provided identifier
     * and optional section.
     *
     * @param string $identifier the first level of information to retrieve
     * @param string $section    an optional second level of information
     *
     * @return mixed
     *
     * @throws \Exception if unable to find data for $identifier.
     * @throws \Exception if there is a problem reading / processing the underlying `roles.json`
     *                    configuration data
     */
    public static function getConfig($identifier, $section = null)
    {
        foreach(self::getConfigData() as $key => $data) {
            if ($key === $identifier) {
                if ($section === null) {
                    return $data;
                } elseif (array_key_exists($section, $data)) {
                    return $data[$section];
                } else {
                    // If the section is not found then we fall back to the `default` section,
                    // regardless of whether or not the $identifier actually extends default.
                    return self::getConfig('default', $section);
                }
            }
        }

        throw new \Exception("Unknown role '$identifier'");
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
            $configFile = new XdmodConfiguration(
                'roles.json',
                CONFIG_DIR,
                null,
                array(
                    'local_config_dir' => implode(
                        DIRECTORY_SEPARATOR,
                        array(
                            CONFIG_DIR,
                            'roles.d'
                        )
                    )
                )
            );
            $configFile->initialize();
            $data = json_decode($configFile->toJson(), true);

            self::$config = $data['roles'];
        }

        return self::$config;
    }
}
