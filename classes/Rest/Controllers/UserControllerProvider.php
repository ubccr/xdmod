<?php

namespace Rest\Controllers;

use Models\Services\Organizations;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

use XDUser;

/**
 * Class UserControllerProvider
 *
 * This class is responsible for maintaining routes for the REST stack that
 * handle user-related functionality.
 */
class UserControllerProvider extends BaseControllerProvider
{

    /**
     * The set of profile details that are allowed to be set by users for themselves.
     *
     * @var array
     */
    private static $userSettableProperties = array(
        'first_name',
        'last_name',
        'email_address',
        'password',
    );

    /**
     * A mapping of user properties that can come in with a request to
     * options for handling them in the profile updating code.
     *
     * @var array
     */
    private static $propertySettingOptions = array(
        'first_name' => array(
            'setter' => 'setFirstName',
        ),
        'last_name' => array(
            'setter' => 'setLastName',
        ),
        'email_address' => array(
            'setter' => 'setEmailAddress',
        ),
        'password' => array(
            'setter' => 'setPassword',
        ),
    );

    /**
     * @see BaseControllerProvider::setupRoutes
     */
    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;

        $controller->get("$root/current", '\Rest\Controllers\UserControllerProvider::getCurrentUser');
        $controller->patch("$root/current", '\Rest\Controllers\UserControllerProvider::updateCurrentUser');
    }

    /**
     * Get details for the current user.
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return array                Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              results: An object containing data about
     *                                       the current user.
     */
    public function getCurrentUser(Request $request, Application $app)
    {
        // Ensure that the user is logged in.
        $this->authorize($request);

        // Extract and return the information for the user.
        return $app->json(array(
            'success' => true,
            'results' => $this->extractUserData($this->getUserFromRequest($request)),
        ));
    }

    /**
     * Update details about the current user.
     *
     * @param  Request     $request The request used to make this call.
     * @param  Application $app     The router application.
     * @return array                Response data containing the following info:
     *                              success: A boolean indicating if the call was successful.
     *                              message
     */
    public function updateCurrentUser(Request $request, Application $app)
    {
        // Ensure that the user is logged in.
        $this->authorize($request);

        // Attempt to update the user's profile with the given information.
        $this->updateUser(
            $this->getUserFromRequest($request),
            $this->extractUserSettableProperties($request)
        );

        // If the last step completed successfully, hide the welcome message
        // for first-time XSEDE users and return a success message.
        $_SESSION['suppress_profile_autoload'] = true;

        return $app->json(array(
            'success' => true,
            'message' => 'User profile updated successfully',
        ));
    }

    /**
     * Extract information from a user object.
     *
     * Ported from: classes/REST/Portal/Profile.php
     *
     * @param  XDUser $user The user object to extract data from.
     * @return array        An associative array of data for the user.
     */
    private function extractUserData(XDUser $user)
    {
        $emailAddress = $user->getEmailAddress();
        if ($emailAddress == NO_EMAIL_ADDRESS_SET) {
            $emailAddress = '';
        }
        $mostPrivileged = $user->getMostPrivilegedRole();
        $mostPrivilegedFormalName = $mostPrivileged->getFormalName();
        if (count(array_intersect(XDUser::$CENTER_ACLS, $user->getAcls(true))) > 0) {
            $organization = Organizations::getAbbrevById($user->getOrganizationID());
            $mostPrivilegedFormalName = "$mostPrivilegedFormalName - $organization";
        }
        return array(
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email_address' => $emailAddress,
            'is_sso_user' => $user->isSSOUser(),
            'first_time_login' => $user->getCreationTimestamp() == $user->getLastLoginTimestamp(),
            'autoload_suppression' => isset($_SESSION['suppress_profile_autoload']),
            'field_of_science' => $user->getFieldOfScience(),
            'active_role' => $mostPrivilegedFormalName,
            'most_privileged_role' => $mostPrivilegedFormalName,
        );
    }

    /**
     * Extract user profile properties from a request that are allowed to be
     * set by the user.
     *
     * @param  Request $request The request to extract properties from.
     * @return array            An array containing properties
     */
    private function extractUserSettableProperties(Request $request)
    {
        $requestProperties = array();
        foreach (self::$userSettableProperties as $propertyName) {
            $propertyValue = $this->getStringParam($request, $propertyName);

            if ($propertyValue === null) {
                continue;
            }
            $requestProperties[$propertyName] = $propertyValue;
        }
        return $requestProperties;
    }

    /**
     * Update a user with new details.
     *
     * @param  XDUser $user              The user to update.
     * @param  array  $updatedProperties A mapping of properties to update
     *                                   to their new values.
     *
     * @throws Exception The new property values failed to save.
     */
    private function updateUser(XDUser $user, array $updatedProperties)
    {
        // For each property that can be set, check if it is included in the
        // given set of properties. If so, invoke that property's setter on the
        // given user with the given property value.
        $userType = $user->getUserType();
        foreach ($updatedProperties as $propertyName => $propertyValue) {
            if (!array_key_exists($propertyName, self::$propertySettingOptions)) {
                continue;
            }
            $propertyOptions = self::$propertySettingOptions[$propertyName];

            $user->{$propertyOptions['setter']}($propertyValue);
        }

        // Attempt to save the user's new details. This will throw an exception
        // if an error occurs.
        $user->saveUser();
    }
}
