<?php

declare(strict_types=1);

namespace Access\Controller\InternalDashboard;

use Access\Controller\BaseController;
use CCR\DB;
use CCR\MailWrapper;
use Exception;
use Models\Acl;
use Models\Services\Acls;
use Models\Services\Users;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use XDAdmin;
use XDUser;
use XDWarehouse;
use function xd_response\buildError;


/**
 *
 */
class UserAdminController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/user_admin.php')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $operation = $this->getStringParam($request, 'operation', true);
        switch ($operation) {
            case 'create_user':
                return $this->createUser($request);
            case 'delete_user':
                return $this->deleteUser($request);
            case 'empty_report_image_cache':
                return $this->emptyReportImageCache($request);
            case 'enum_institutions':
                return $this->enumInstitutions($request);
            case 'enum_exception_email_addresses':
                return $this->enumExceptionEmailAddresses($request);
            case 'enum_resource_providers':
                return $this->enumResourceProviders($request);
            case 'enum_user_types':
                return $this->enumUserTypes($request);
            case 'enum_roles':
                return $this->enumRoles($request);
            case 'get_user_details':
                $userId = $this->getStringParam($request, 'uid', true, null, RESTRICTION_UID);
                return $this->getUserDetails($request, $userId);
            case 'list_users':
                return $this->listUsers($request);
            case 'pass_reset':
                return $this->passwordReset($request);
            case 'search_users':
                return $this->searchForUsers($request);
            case 'update_user':
                return $this->updateUser($request);
        }
        throw new BadRequestHttpException('invalid operation specified');
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function listUsers(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);
        $xda = new XDAdmin();

        $group = $this->getIntParam($request, 'group');
        $userListing = $xda->getUserListing($group);

        $users = [];
        foreach ($userListing as $currentUser) {

            $userData = explode(';', $currentUser['username']);
            if ($userData[0] !== 'Public User') {
                $userEntry = [
                    'id' => $currentUser['id'],
                    'username' => $userData[0],
                    'first_name' => $currentUser['first_name'],
                    'last_name' => $currentUser['last_name'],
                    'account_is_active' => $currentUser['account_is_active'],
                    'last_logged_in' => $this->parseMicrotime($currentUser['last_logged_in'])
                ];

                $users[] = $userEntry;
            }
        }

        return $this->json([
            'success' => true,
            'status' => 'success',
            'users' => $users
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/metadata', requirements: ['prefix' => '.*'], methods: ['GET'])]
    public function getUserMetadata(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $pdo = DB::factory('database');

        $userTypes = $pdo->query('SELECT id, type, color FROM moddb.UserTypes');
        $acls = $pdo->query("SELECT display AS description, acl_id AS role_id FROM moddb.acls WHERE name != 'pub' ORDER BY description");

        return $this->json([
            'success' => true,
            'user_types' => $userTypes,
            'user_roles' => $acls
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/create', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        $this->logger->warning('[start] Creating User');

        try {
            $userName = $this->getStringParam($request, 'username', true, null, RESTRICTION_USERNAME);
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'username' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'username'."), 400);
            }
        }

        try {
            $firstName = $this->getStringParam($request, 'first_name', true, null, RESTRICTION_FIRST_NAME);
        }catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'first_name' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'first_name'."), 400);
            }
        }

        try {
            $lastName = $this->getStringParam($request, 'last_name', true, null, RESTRICTION_LAST_NAME);
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'last_name' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'last_name'."), 400);
            }
        }

        try {
            $userType = intval($this->getStringParam($request, 'user_type', true, null, RESTRICTION_GROUP));
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'user_type' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'user_type'."), 400);
            }
        }

        try {
            $institution = intval($this->getStringParam($request, 'institution', true, null, RESTRICTION_INSTITUTION));
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'institution' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'institution'."), 400);
            }
        }


        try {
            $personAssignment = intval($this->getStringParam($request, 'assignment', true, null, RESTRICTION_ASSIGNMENT));
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'assignment' not specified."), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'assignment'."), 400);
            }
        }

        try {
            $emailAddress = $this->getEmailParam($request, 'email_address', true);
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("'email_address' not specified."), 400);
            } else {
                return $this->json(buildError("Failed to assert 'email_address'."), 400);
            }
        }

        try {
            $acls = json_decode($this->getStringParam($request, 'acls', true), true);
        } catch (BadRequestHttpException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'is a required parameter')) {
                return $this->json(buildError("Acl information is required"), 400);
            } else {
                return $this->json(buildError("Invalid value specified for 'acls'."), 400);
            }
        }

        $sticky = $this->getBooleanParam($request, 'sticky', false, false);

        // Ensure that we have at least on acl for the new user.
        if (empty($acls)) {
            return $this->json(buildError('Acl information is required'), 400);
        }
        // Checking for an acl set that only contains feature acls.
        // Feature acls are acls that only provide access to an XDMoD feature and
        // are not used for data access.
        if (!$this->hasDataAcls($acls)) {
            return $this->json(buildError('Please include a non-feature acl ( i.e. User, PI etc. )'), 400);
        }

        $tempPassword = $this->generateTempPassword();

        $newUser = new \XDUser(
            $userName,
            $tempPassword,
            $emailAddress,
            $firstName,
            '',
            $lastName,
            array_keys($acls),
            ROLE_ID_USER,
            $institution,
            $personAssignment,
            [],
            $sticky
        );
        $newUser->setUserType($userType);
        $newUser->saveUser();

        foreach ($acls as $acl => $centers) {
            // Now that the user has been updated, We need to check if they have been assigned any
            // 'center' acls. If they have and if an 'institution' has been provided ( it should have
            // been ) then we need to call `setOrganizations` so that the user_acl_group_by_parameters
            // table is updated accordingly.
            if (in_array($acl, ['cd', 'cs'])) {
                $newUser->setOrganizations(
                    [
                        $institution => [
                            'primary' => 1,
                            'active' => 1
                        ]
                    ],
                    $acl
                );
            }
        }

        // 'institution' now corresponds to a Users organization and will always be present, not only
        // when a user has been assigned the campus champion acl. This means we need to update the logic
        // that gates  the `setInstitution` function call to include a check if the user has been
        // assigned the Campus Champion acl.
        if (in_array(ROLE_ID_CAMPUS_CHAMPION, array_keys($acls))) {
            $newUser->setInstitution($institution);
        }

        list($subject, $emailBody) = $this->generateNewUserEmail($newUser);
        MailWrapper::sendMail([
            'body' => $emailBody,
            'subject' => $subject,
            'toAddress' => $emailAddress
        ]);
        $this->logger->warning('[done] Creating User');
        return $this->json([
            'success' => true,
            'user_type' => $userType,
            'message' => sprintf('User <b>%s</b> created successfully', $userName)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/update', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function updateUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $currentUser = $this->authorize($request, ['mgr']);

        $userId = intval($this->getStringParam($request, 'uid', true, null, RESTRICTION_UID));
        $userToUpdate = \XDUser::getUserByID($userId);
        if (!isset($userToUpdate)) {
            return $this->json([
                'success' => false,
                'status' => 'user_does_not_exist'
            ]);
        }


        $potentialParameters = [
            'first_name' => $this->getStringParam($request, 'first_name', false, null, RESTRICTION_FIRST_NAME),
            'last_name' => $this->getStringParam($request, 'last_name', false, null, RESTRICTION_LAST_NAME),
            'user_type' => $this->getStringParam($request, 'user_type', false, null, RESTRICTION_GROUP),
            'institution' => $this->getStringParam($request, 'institution', false, null, RESTRICTION_INSTITUTION),
            'person' => $this->getStringParam($request, 'assignment', false, null, RESTRICTION_ASSIGNMENT),
            'is_active' => $this->getBooleanParam($request, 'is_active')
        ];

        $qualifyingParameters = array_filter(
            $potentialParameters,
            function ($value) {
                return isset($value);
            }
        );

        $acls = null;
        $aclsRaw = $this->getStringParam($request, 'acls');
        if (isset($aclsRaw)) {
            $acls = json_decode($aclsRaw, true);
            if (count($acls) < 1) {
                return $this->json(buildError('Acl information is required'));
            }
        }

        // If we're updating ourselves we need to ensure a few things...
        if ($currentUser->getUserID() === $userToUpdate->getUserID()) {

            // Make sure that we're not trying to disable ourselves.
            if (isset($qualifyingParameters['is_active']) && !$qualifyingParameters['is_active']) {
                return $this->json([
                    'success' => false,
                    'status' => 'You are not allowed to disable your own account.'
                ]);
            }

            // Check to make sure that we're not trying to revoke our own manager access.
            if (isset($acls)) {
                if (!array_key_exists(ROLE_ID_MANAGER, $acls)) {
                    return $this->json([
                        'success' => false,
                        'status' => 'You are not allowed to revoke manager access from yourself.'
                    ]);
                }
            }
        }

        if (isset($qualifyingParameters['first_name'])) {
            $userToUpdate->setFirstName($qualifyingParameters['first_name']);
        }

        if (isset($qualifyingParameters['last_name'])) {
            $userToUpdate->setLastName($qualifyingParameters['last_name']);
        }

        $emailAddress = $this->getEmailParam($request, 'email_address', true);

        // Make sure that if we're anything other than an SSO User that we cannot remove our email address.
        if ($userToUpdate->getUserType() !== SSO_USER_TYPE && strlen($emailAddress) < 1) {
            return $this->json([
                'success' => true,
                'status' => 'This XDMoD user must have an e-mail address set.'
            ]);
        }
        $userToUpdate->setEmailAddress($emailAddress);

        if (isset($qualifyingParameters['person'])) {
            $userToUpdate->setPersonID($qualifyingParameters['person']);
        }

        if (isset($qualifyingParameters['is_active'])) {
            $userToUpdate->setAccountStatus($qualifyingParameters['is_active']);
        }

        // If we're trying to update the user's type, only non-SSO users can do so.
        if (isset($qualifyingParameters['user_type'])) {
            if ($userToUpdate->getUserType() !== SSO_USER_TYPE) {
                $userToUpdate->setUserType($qualifyingParameters['user_type']);
            }
        }

        $sticky = $this->getBooleanParam($request, 'sticky');
        if (isset($sticky)) {
            $userToUpdate->setSticky($sticky);
        }

        $originalAcls = $userToUpdate->getAcls(true);
        if (isset($acls)) {
            if (!$this->hasDataAcls($acls)) {
                return $this->json(buildError('Please include a non-feature acl ( i.e. User, PI etc. )'));
            }
            // first clear the updated user's acls
            $userToUpdate->setAcls([]);
            foreach ($acls as $aclName => $centers) {
                $acl = Acls::getAclByName($aclName);
                $userToUpdate->addAcl($acl);
            }
        } else {
            return $this->json(buildError('Acl information is required.'));
        }

        if (isset($qualifyingParameters['institution'])) {
            $userToUpdate->setOrganizationID($qualifyingParameters['institution']);
            $oldCampusChampion = in_array(ROLE_ID_CAMPUS_CHAMPION, $originalAcls);
            $newCampusChampion = in_array(ROLE_ID_CAMPUS_CHAMPION, array_keys($acls));

            if ($newCampusChampion && !$oldCampusChampion) {
                $userToUpdate->setInstitution($qualifyingParameters['institution']);
            } elseif (!$newCampusChampion && $oldCampusChampion) {
                $userToUpdate->disassociateWithInstitution();
            }
        }

        // We've updated everything that we need to, now we can save.
        try {
            $userToUpdate->saveUser();

            // Now that the user has been saved, clear their organizations
            $userToUpdate->setOrganizations([], ROLE_ID_CENTER_DIRECTOR);
            $userToUpdate->setOrganizations([], ROLE_ID_CENTER_STAFF);

            // and add the new ones.
            foreach ($acls as $aclName => $centers) {
                if (in_array($aclName, ['cd', 'cs']) && isset($qualifyingParameters['institution'])) {
                    $userToUpdate->setOrganizations(
                        [
                            $qualifyingParameters['institution'] => [
                                'primary' => 1,
                                'active' => 1
                            ]
                        ],
                        $aclName
                    );
                }
            }
        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'status' => $exception->getMessage()
            ]);
        }

        $userName = $userToUpdate->getUsername();
        return $this->json([
            'success' => true,
            'status' => sprintf(
                '%sUser <b>%s</b> updated successfully',
                $userToUpdate->isSSOUser() ? 'Single Sine On' : '',
                $userName
            ),
            'username' => $userName,
            'user_type' => $userToUpdate->getUserType()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/search', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function searchForUsers(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $searchCriteria = json_decode($this->getStringParam($request, 'search_crit', true), true);

        $datawarehouse = new \XDWarehouse();
        $users = $datawarehouse->searchUsers($searchCriteria);

        return $this->json([
            'success' => true,
            'data' => $users,
            'total' => count($users)
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/password', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function passwordReset(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $userId = $this->getStringParam($request, 'uid', true, null, RESTRICTION_UID);

        $userToContact = XDUser::getUserByID($userId);
        if ($userToContact === null) {
            return $this->json([
                'success' => false,
                'status' => 'user_does_not_exist'
            ]);
        }

        $this->sendPasswordResetEmail($userToContact);

        $message = sprintf('Password reset e-mail sent to user %s', $userToContact->getUsername());
        return $this->json([
            'success' => true,
            'message' => $message,
            'status' => $message
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/institutions', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumInstitutions(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $query = $this->getStringParam($request, 'query');
        $xdAdmin = new \XDAdmin();

        $institutions = $xdAdmin->enumerateInstitutions($query);

        // If there are no organizations for the provided query, then by default retrieve / return the full list of
        // organizations.
        $institutionCount = count($institutions);
        if (count($institutions) === 0) {
            $institutions = $xdAdmin->enumerateInstitutions();
        }

        return $this->json([
            'success' => true,
            'status' => 'success',
            'total_institution_count' => $institutionCount,
            'institutions' => $institutions
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/roles', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumRoles(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $xdAdmin = new \XDAdmin();
        $roles = $xdAdmin->enumerateAcls();

        $roleEntries = [];
        foreach ($roles as $currentRole) {
            // requiresCenter can only be true iff the current install supports
            // multiple service providers.
            if ($currentRole['name'] !== 'pub') {
                $roleEntries[] = [
                    'acl' => $currentRole['display'],
                    'acl_id' => $currentRole['name'],
                    'include' => false,
                    'primary' => false,
                    'displays_center' => false,
                    'requires_center' => false
                ];
            }
        }
        return $this->json([
            'success' => true,
            'status' => 'success',
            'acls' => $roleEntries
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/types', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumUserTypes(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $xdAdmin = new \XDAdmin();
        $userTypes = $xdAdmin->enumerateUserTypes();

        $userTypeEntries = [];
        foreach ($userTypes as $type) {
            $userTypeEntries[] = [
                'id' => $type['id'],
                'type' => $type['type'],
            ];
        }
        $data = [
            'success' => true,
            'status' => 'success',
            'user_types' => $userTypeEntries
        ];
        return $this->json($data);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/providers', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumResourceProviders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $xdAdmin = new \XDAdmin();
        $resourceProviders = $xdAdmin->enumerateResourceProviders();

        $providers = [];
        foreach ($resourceProviders as $provider) {
            $providers[] = [
                'id' => $provider['id'],
                'organization' => $provider['organization'] . ' (' . $provider['name'] . ')',
                'include' => false
            ];
        }

        return $this->json([
            'status' => 'success',
            'success' => true,
            'providers' => $providers
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/emails/exceptions', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumExceptionEmailAddresses(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $xdAdmin = new \XDAdmin();
        $emailAddresses = $xdAdmin->enumerateExceptionEmailAddresses();

        return $this->json([
            'success' => true,
            'status' => 'success',
            'email_addresses' => $emailAddresses
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/reports/images/cache', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function emptyReportImageCache(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $userId = $this->getStringParam($request, 'uid', true, null, RESTRICTION_UID);
        $targetUser = XDUser::getUserByID($userId);
        if (!isset($targetUser)) {
            return $this->json(buildError('user_does_not_exist'));
        }

        $chart_pool = new \XDChartPool($targetUser);
        $chart_pool->emptyCache();

        $report_manager = new \XDReportManager($targetUser);
        $report_manager->emptyCache();
        $report_manager->flushReportImageCache();

        return $this->json([
            'success' => true,
            'message' => sprintf(
                'The report image cache for user <b>%s</b> has been emptied',
                $targetUser->getUsername()
            )
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/delete', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function deleteUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $requestingUser = $this->authorize($request, ['mgr']);

        $userId = $this->getStringParam($request, 'uid', true, null, RESTRICTION_UID);
        $targetUser = XDUser::getUserByID($userId);
        if (!isset($targetUser)) {
            return $this->json(buildError('user_does_not_exist'));
        }

        if ($requestingUser->getUsername() === $targetUser->getUsername()) {
            return $this->json(buildError('You are not allowed to delete your own account.'));
        }

        // Remove all entries in this user's profile
        $profile = $targetUser->getProfile();
        $profile->clear();

        $statusPrefix = $targetUser->isSSOUser() ? 'Single Sign On ' : '';
        $displayUsername = $targetUser->getUsername();

        $targetUser->removeUser();

        return $this->json([
            'success' => true,
            'message' => sprintf(
                '%sUser <b>%s</b> deleted from the portal',
                $statusPrefix,
                $displayUsername
            )
        ]);
    }

    /**
     * @param Request $request
     * @param int|string $userId
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/{userId}', requirements: ['userId' => '\d+', 'prefix' => '.*'], methods: ['POST'])]
    public function getUserDetails(Request $request, $userId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $selected_user = XDUser::getUserByID($userId);

        if ($selected_user === NULL) {
            return $this->json(buildError('user_does_not_exist'));
        }

        // -----------------------------

        $userDetails = [];

        $userDetails['username'] = $selected_user->getUsername();
        $userDetails['formal_name'] = $selected_user->getFormalName();

        $userDetails['time_created'] = $selected_user->getCreationTimestamp();
        $userDetails['time_updated'] = $selected_user->getUpdateTimestamp();
        $userDetails['time_last_logged_in'] = $selected_user->getLastLoginTimestamp();

        $userDetails['email_address'] = $selected_user->getEmailAddress();

        if ($userDetails['email_address'] == NO_EMAIL_ADDRESS_SET) {
            $userDetails['email_address'] = '';
        }

        $userDetails['assigned_user_id'] = $selected_user->getPersonID(TRUE);

        //$userDetails['provider'] = $selected_user->getOrganization();
        $userDetails['institution'] = $selected_user->getOrganizationID();

        $userDetails['user_type'] = $selected_user->getUserType();

        $obj_warehouse = new XDWarehouse();

        $userDetails['institution_name'] = $obj_warehouse->resolveInstitutionName($userDetails['institution']);

        $userDetails['assigned_user_name'] = $obj_warehouse->resolveName($userDetails['assigned_user_id']);

        if ($userDetails['assigned_user_name'] == NO_MAPPING) {
            $userDetails['assigned_user_name'] = '';
        }

        $userDetails['is_active'] = $selected_user->getAccountStatus() ? 'active' : 'disabled';
        $userDetails['sticky'] = $selected_user->isSticky();

        $acls = Acls::listUserAcls($selected_user);
        $populatedAcls = array_reduce(
            $acls,
            function ($carry, $item) use ($selected_user) {
                $aclName = $item['name'];
                $aclCenters = [];
                if ($item['requires_center'] === true) {
                    $aclCenters = Acls::getDescriptorParamValues(
                        $selected_user,
                        $aclName,
                        'provider'
                    );
                }

                $carry[$aclName] = $aclCenters;

                return $carry;
            },
            []
        );

        $userDetails['acls'] = $populatedAcls;

        return $this->json([
            'success' => true,
            'status' => 'success',
            'user_information' => $userDetails
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/internal_dashboard/users/existing', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function enumExistingUsers(Request $request): Response
    {
        $group_filter = $this->getStringParam($request, 'group_filter');
        $role_filter = $this->getStringParam($request, 'role_filter');
        $context_filter = $this->getStringParam($request, 'context_filter', false, '');

        $results = Users::getUsers($group_filter, $role_filter, $context_filter);
        $filtered = [];
        foreach ($results as $user) {
            if ($user['username'] !== 'Public User') {
                $filtered[] = $user;
            }
        }

        return $this->json([
            'success' => true,
            'count' => count($filtered),
            'response' => $filtered
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    private function sendPasswordResetEmail(XDUser $user): void
    {
        $rid = $user->generateRID();

        $subject = sprintf('%s: Password Reset', \xd_utilities\getConfiguration('general', 'title'));
        $body = $this->twig->render(
            'emails/password_reset.html.twig',
            [
                'first_name' => $user->getFirstName(),
                'username' => $user->getUsername(),
                'reset_link' => sprintf(
                    '%spassword_reset.php?rid=%s',
                    \xd_utilities\getConfigurationUrlBase('general', 'site_address'),
                    $rid
                ),
                'expiration' => strftime('%c %Z', explode('|', $rid)[1]),
                'maintainer_signature' => MailWrapper::getMaintainerSignature(),
            ]
        );

        MailWrapper::sendMail([
            'toAddress' => $user->getEmailAddress(),
            'subject' => $subject,
            'body' => $body
        ]);
    }

    /**
     * @return string
     */
    private function generateTempPassword(): string
    {
        $password_chars = 'abcdefghijklmnopqrstuvwxyz!@#$%-_=+ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $max_password_chars_index = strlen($password_chars) - 1;
        $password = '';
        for ($i = 0; $i < CHARLIM_PASSWORD; $i++) {
            $password .= $password_chars[mt_rand(0, $max_password_chars_index)];
        }
        return $password;
    }

    /**
     * @param array $acls
     * @return bool
     */
    private function hasDataAcls(array $acls): bool
    {
        $aclNames = [];
        $featureAcls = Acls::getAclsByTypeName('feature');
        $tabAcls = Acls::getAclsByTypeName('tab');
        $uiOnlyAcls = array_merge($featureAcls, $tabAcls);
        if (count($uiOnlyAcls) > 0) {
            $aclNames = array_reduce(
                $uiOnlyAcls,
                function ($carry, Acl $item) {
                    $carry [] = $item->getName();
                    return $carry;
                },
                []
            );
        }
        $diff = array_diff(array_keys($acls), $aclNames);
        return !empty($diff);
    }

    /**
     * @return array in the form [$subject, $emailBody]
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws Exception
     */
    private function generateNewUserEmail(\XDUser $newUser): array
    {
        $pageTitle = \xd_utilities\getConfiguration('general', 'title');
        $siteAddress = \xd_utilities\getConfigurationUrlBase('general', 'site_address');
        $userName = $newUser->getUsername();
        $rid = $newUser->generateRID();

        return [
            sprintf('%s: Account Created', $pageTitle),
            $this->twig->render(
                'emails/new_user.html.twig',
                [
                    'page_title' => $pageTitle,
                    'site_address' => $siteAddress,
                    'username' => $userName,
                    'rid' => $rid
                ]
            )
        ];
    }

    private function parseMicrotime($mtime)
    {

        $time_frags = explode('.', $mtime);
        return $time_frags[0] * 1000;

    }
}
