<?php

namespace Models\Services;

use Xdmod\Config;

class Parameters
{

    /**
     * Retrieve the parameters ( ultimately where clauses ) for the specified $user & $aclName. An
     * empty array means that there are no where clauses & hence unrestricted.
     *
     * @param \XDUser $user the user for whom the parameters are to be retrieved
     * @param string $aclName the acl to use when retrieving the parameters
     * @return array in the form: array($dimensionName => $dimensionValue)
     * @throws \Exception if roles.json
     */
    public static function getParameters(\XDUser $user, $aclName)
    {
        $parameters = array();

        // We need to retrieve which dimensions this acl filters on. To do that we need to see how
        // it's configured
        $config = Config::factory();

        // retrieve the roles section of the roles.json/.d config files.
        $roles = $config['roles']['roles'];

        try {
            $aclConfig = $dimensions = $roles[$aclName];
            $dimensions = isset($aclConfig['dimensions']) ? $aclConfig['dimensions'] : array();
        } catch (\Exception $e) {
            throw new \Exception("Unable to retrieve dimension information about $aclName");
        }

        foreach ($dimensions as $dimension) {
            switch ($dimension) {
                case 'provider':
                    $parameters['provider'] = (string)$user->getOrganizationID();
                    break;
                case 'person':
                    $parameters['person'] = (string)$user->getPersonID();
                    break;
                case 'pi':
                    $parameters['pi'] = (string)$user->getPersonID();
                    break;
                default:
                    throw new \Exception("Unable to determine parameters for acl [$aclName] dimension[$dimension]");
                    break;
            }
        }

        return $parameters;
    }
}
