<?php

namespace NewRest\Utilities;

use XDUser;

class Authorization
{

    const _DEFAULT_SESSION_KEY = 'xdUser';
    const _SUCCESS = 0;
    const _MESSAGE = 1;

    const _DEFAULT_MESSAGE = 'An error was encountered while attempting to process the requested authorization procedure.';

    /**
     * @param XDUser $user
     * @param array $requirements
     * @param bool $blacklist
     * @return array in the form array( boolean success, string message )
     * @throws \Exception
     */
    public static function isAuthorized(XDUser $user, array $requirements = array(), $blacklist = false)
    {
        $result = array(
            self::_SUCCESS => false,
            self::_MESSAGE => self::_DEFAULT_MESSAGE
        );

        $validUser = isset($user);
        $validRequirements = isset($requirements) && is_array($requirements) && count($requirements) > 0;

        if ($validUser && $validRequirements) {

            $requirements = self::_preProcessRequirements($user, $requirements);

            $roles = $user->getRoles();
            $isManager = $user->isManager();
            $activeRole = $user->getActiveRole()->getIdentifier();

            if (in_array(SAB_MEMBER, $requirements) && !in_array('sab', $roles)) {
                $result[self::_MESSAGE] = self::_DEFAULT_MESSAGE . "\n[ Not a SAB Member ]";
            } else if (in_array(STATUS_MANAGER_ROLE, $requirements) && !$isManager) {
                $result[self::_MESSAGE] = self::_DEFAULT_MESSAGE . "\n[ Not a Manager ]";
            } else if (in_array(STATUS_CENTER_DIRECTOR_ROLE, $requirements) && $activeRole !== ROLE_ID_CENTER_DIRECTOR) {
                $result[self::_MESSAGE] = self::_DEFAULT_MESSAGE . "\n [ Not a Center Director ]";
            } else {
                if (!$blacklist) {
                    $found = 0;
                    foreach ($requirements as $requirement) {
                        if (in_array($requirement, $roles)) $found += 1;
                    }
                    if ($found >= count($requirements)) {
                        $result[self::_SUCCESS] = true;
                        $result[self::_MESSAGE] = '';
                    } else {
                        $result[self::_MESSAGE] .= " [ Not Authorized ]";
                    }
                } else {
                    $found = 0;
                    foreach($requirements as $requirement) {
                        if (in_array($requirement, $roles)) $found += 1;
                    }
                    if ($found === 0) {
                        $result[self::_SUCCESS] = true;
                        $result[self::_MESSAGE] = '';
                    } else {
                        $result[self::_MESSAGE] .= " [ Not Authorized ]";
                    }
                }

            }
        }

        return $result;
    }

    public static function authorized(XDUser $user, array $requirements = array(), $blacklist = false)
    {
        $result = array(
            self::_SUCCESS => false,
            self::_MESSAGE => self::_DEFAULT_MESSAGE
        );

        if (!isset($user)) {
            throw new \Exception('A valid user must be supplied to complete the requested operation.');
        }

        if (
            (
                isset($requirements) &&
                !is_array($requirements)
            ) ||
            (
                isset($requirements) &&
                is_array($requirements) &&
                count($requirements) <= 0
            )
        ) {
            throw new \Exception('A valid set of requirements are required to complete the requested operation.');
        }

        $found = $user->hasAcls($requirements);
        $result[self::_SUCCESS] = (!$found && $blacklist) || ($found && !$blacklist);
        $result[self::_MESSAGE] = (!$found && !$blacklist) || ($found && $blacklist)
            ? ' [ Not Authorized ]'
            : '';
        return $result;
    }

    /**
     * Conduct any processing on the provided requirements prior to the actual
     * authorization process.
     *
     * @param XDUser $user object that represents the currently logged in user.
     * @param array $requirements that the user must fulfill to be considered 'authorized'.
     * @return array of $requirements.
     */
    private static function _preProcessRequirements(XDUser $user, array $requirements)
    {

        if ($user->isManager()) {
            \xd_utilities\remove_element_by_value($requirements, SAB_MEMBER);
        }

        return $requirements;
    }


}
