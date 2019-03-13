<?php

namespace Models\Services;

use Configuration\XdmodConfiguration;

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
        $roles = XdmodConfiguration::assocArrayFactory('roles.json', CONFIG_DIR)['roles'];

        if (!isset($roles[$aclName])) {
            throw new \Exception("Unable to find configuration information for $aclName.");
        }

        $dimensions = isset($roles[$aclName]['dimensions']) ? $roles[$aclName]['dimensions'] : array();

        foreach ($dimensions as $dimension) {
            switch ($dimension) {
                case 'provider':
                    $parameters['provider'] = (string)$user->getOrganizationID();
                    break;
                case 'institution':
                    $parameters['institution'] = (string)$user->getOrganizationID();
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
