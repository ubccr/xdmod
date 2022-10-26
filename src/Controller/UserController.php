<?php
declare(strict_types=1);
namespace Access\Controller;

use Models\Services\Organizations;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use XDUser;

/**
 * @Route(path="users")
 */
class UserController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The set of profile details that are allowed to be set by users for themselves.
     *
     * @var array
     */
    private static $userSettableProperties = [
        'first_name',
        'last_name',
        'email_address',
        'password',
    ];

    /**
     * A mapping of user properties that can come in with a request to
     * options for handling them in the profile updating code.
     *
     * @var array
     */
    private static $propertySettingOptions = [
        'first_name' => [
            'setter' => 'setFirstName',
        ],
        'last_name' => [
            'setter' => 'setLastName',
        ],
        'email_address' => [
            'setter' => 'setEmailAddress',
        ],
        'password' => [
            'setter' => 'setPassword',
        ],
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @Route(path="/curent", name="get_current_user")
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function getCurrentUser(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());

        if ((!$user instanceof XDUser)) {
            return $this->json([
                'success' => false,
                'message' => 'Internal Error validating User'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'results' => $this->extractUserData($user)
        ]);
    }

    /**
     * @Route(path="/current", methods={"PATCH"}, name="update_current_user")
     * @param Request $request
     * @return Response
     * @throws \Exception if unable to look up an XDUser by the currently logged in user's id.
     */
    public function updateCurrentUser(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());

        if ((!$user instanceof XDUser)) {
            return $this->json([
                'success' => false,
                'message' => 'Internal Error validating User'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->updateUser(
            $user,
            $this->extractUserSettableProperties($request)
        );

        return $this->json([
            'success' => true,
            'message' => 'User profile updated successfully'
        ]);
    }

    /**
     * Extract information from a user object.
     *
     * Ported from: classes/REST/Portal/Profile.php
     *
     * @param XDUser $user The user object to extract data from.
     * @return array        An associative array of data for the user.
     * @throws \Exception
     */
    private function extractUserData(XDUser $user)
    {
        $emailAddress = $user->getEmailAddress();
        if ($emailAddress == NO_EMAIL_ADDRESS_SET) {
            $emailAddress = '';
        }
        $mostPrivileged = $user->getMostPrivilegedRole();
        $mostPrivilegedFormalName = $mostPrivileged->getDisplay();
        if (count(array_intersect(XDUser::$CENTER_ACLS, $user->getAcls(true))) > 0) {
            $organization = Organizations::getAbbrevById($user->getOrganizationID());
            $mostPrivilegedFormalName = "$mostPrivilegedFormalName - $organization";
        }
        $rawRealmConfig = \DataWarehouse\Access\RawData::getRawDataRealms($user);
        $rawDataRealms = array_map(
            function ($item) {
                return $item['name'];
            },
            $rawRealmConfig
        );

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
            'person_id' => $user->getPersonID(true),
            'raw_data_allowed_realms' => $rawDataRealms
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
            $propertyValue = $request->get($propertyName);

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
