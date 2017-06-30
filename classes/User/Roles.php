<?php namespace User;

use Xdmod\Config;

/**
 * Made so that we can have access to aRole::getConfig without having to change anything in aRole.
 **/
class Roles extends aRole
{
    /**
     * Attempt to retrieve a list of names for the roles currently installed in the system.
     *
     * @param array $blacklist if specified, any entries in this array will not be included in the final result.
     *
     * @return array
     **/
    public static function getRoleNames(array $blacklist = array())
    {
        return  array_filter(
            array_keys(parent::getConfigData()),
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
     * @return null|mixed
     **/
    public static function getConfig($identifier, $section = null)
    {
        return parent::getConfig($identifier, $section);
    }

    /**
     * Attempt to retrieve the module specific values located in roles.json or
     * roles.d/<modules>.json that correspond to the provided identifier and
     * optional section.
     *
     * @param string $module     the module under which the identifier / section
     *                           should have been added.
     * @param string $identifier the first level of information to retrieve
     * @param string $section    an optional second level of information
     *
     * @return null|mixed
     **/
    public static function getModuleConfig($module, $identifier, $section = null)
    {
        $config = Config::factory();

        $data = $config->getModuleSection('roles');
        if (!array_key_exists($module, $data)) {
            return array();
        }

        $moduleData = $data[$module]['roles'];
        if (is_array($moduleData) && !array_key_exists($identifier, $moduleData) || !is_array($moduleData)) {
            return array();
        }

        $identifierData = $moduleData[$identifier];
        if (array_key_exists('extends', $identifierData)) {
            return self::getModuleConfig($module, $identifierData['extends'], $section);
        } elseif ( null !== $section && array_key_exists($section, $identifierData)) {
            return $identifierData[$section];
        } elseif (null !== $section) {
            return $identifierData;
        } else {
            return array();
        }
    }
}
