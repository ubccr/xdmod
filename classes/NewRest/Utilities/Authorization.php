<?php

namespace NewRest\Utilities;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
    * @throws \Exception                if the user provided is null
    *                                   if the requirements is not an array or it is an array but has no contents
    * @throws UnauthorizedHttpException if the user was not able to satisfy the provided requirements
    * and is a public user.
    * @throws AccessDeniedHttpException if the user was not able to satisfy the provided requirements
    * and is not a public user.
    **/
    public static function authorized(XDUser $user, array $requirements = array(), $blacklist = false)
    {
        if (count($requirements) === 0) {
            throw new \Exception('A valid set of requirements are required to complete the requested operation.');
        }

        $found = $user->hasAcls($requirements);
        $success = (!$found && $blacklist) || ($found && !$blacklist);
        $message = self::_DEFAULT_MESSAGE .= (!$found && !$blacklist) || ($found && $blacklist)
            ? ' [ Not Authorized ]'
            : '';
        if ($success === false) {
            if ($user->isPublicUser() === true) {
                throw new UnauthorizedHttpException('xdmod', $message);
            } else {
                throw new AccessDeniedHttpException($message);
            }
        }
    }
}
