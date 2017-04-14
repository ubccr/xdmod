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
    * This function attempts to determine whether or not the provided $user
    * has the provided $requirements. If $blacklist is supplied then success
    * will be whether or not the user *does not* have the provided requirements.
    *
    * @param XDUser $user        the user to authorize
    * @param array $requirements an array of acl names
    * @param bool $blacklist     whether or not to test for the presence of the $requirements or the absence
    * @return array              in the form:
    *                            array(
    *                                0 => <success>,
    *                                1 => <message>
    *                            );
    * @throws \Exception if the user provided is null
    *                    if the requirements is not an array or it is an array but has no contents
    **/
    public static function authorized(XDUser $user, array $requirements = array(), $blacklist = false)
    {
        $result = array(
            self::_SUCCESS => false,
            self::_MESSAGE => self::_DEFAULT_MESSAGE
        );

        if (!isset($user)) {
            throw new \Exception('A valid user must be supplied to complete the requested operation.');
        }

        $notAnArray = isset($requirements) && !is_array($requirements);
        $noContents = isset($requirements) && is_array($requirements) && count($requirements) <= 0;
        $requirementsInvalid = $notAnArray && $noContents;
        if ($requirementsInvalid) {
            throw new \Exception('A valid set of requirements are required to complete the requested operation.');
        }

        $found = $user->hasAcls($requirements);
        $result[self::_SUCCESS] = (!$found && $blacklist) || ($found && !$blacklist);
        $result[self::_MESSAGE] .= (!$found && !$blacklist) || ($found && $blacklist)
            ? ' [ Not Authorized ]'
            : '';
        return $result;
    }

}
