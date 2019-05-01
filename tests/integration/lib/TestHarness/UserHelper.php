<?php

namespace TestHarness;

use XDUser;

class UserHelper
{
    const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@test.com";

    /**
     * A helper function that takes care of some of the default values for instantiating an XDUser.
     *
     * @param string      $username
     * @param string      $password
     * @param string      $firstName
     * @param string      $middleName
     * @param string      $lastName
     * @param array|null  $acls
     * @param string|null $primaryRole
     * @param string|null $email
     * @param string|null $organizationId
     * @param string|null $personId
     * @return XDUser
     * @throws \Exception
     */
    public static function getUser(
        $username,
        $password,
        $firstName,
        $middleName,
        $lastName,
        array $acls = null,
        $primaryRole = null,
        $email = null,
        $organizationId = null,
        $personId = null
    ) {
        $acls = isset($acls) ? $acls : array(ROLE_ID_USER);
        $primaryRole = isset($primaryRole) ? $primaryRole : ROLE_ID_USER;
        $emailAddress = isset($email) ? $email : "$username" . self::DEFAULT_EMAIL_ADDRESS_SUFFIX;

        return new XDUser($username, $password, $emailAddress, $firstName, $middleName, $lastName, $acls, $primaryRole, $organizationId, $personId);
    }
}
