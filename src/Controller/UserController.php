<?php
declare(strict_types=1);

namespace Access\Controller;

use CCR\DB;
use Exception;
use Models\Services\Acls;
use Models\Services\Organizations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use xd_security\SessionSingleton;
use XDUser;
use XDWarehouse;

/**
 *
 */
#[Route("{prefix}/users", requirements: ['prefix' => '.*'])]
class UserController extends BaseController
{

    /**
     * The set of profile details that are allowed to be set by users for themselves.
     *
     * @var array
     */
    private static $userSettableProperties = [
        'first_name' => 'string',
        'last_name' => 'string',
        'email_address' => 'string',
        'password' => 'string',
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

    /**
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('', methods: ['POST'])]
    #[Route('/controllers/sab_user.php', name: 'list_users_legacy', methods: ['GET'])]
    public function listUsers(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $start = $this->getIntParam($request, 'start', true);
        $limit = $this->getIntParam($request, 'limit', true);
        $searchMode = $this->getStringParam($request, 'search_mode', true);
        $piOnly = $this->getBooleanParam($request, 'pi_only', true);
        $nameFilter = $this->getStringParam($request, 'query');
        $userManagement = $this->getBooleanParam($request, 'userManagement');

        $universityId = null;
        $searchMethod = null;
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        if ($user->hasAcl(ROLE_ID_CAMPUS_CHAMPION) && !isset($userManagement)) {
            $universityId = Acls::getDescriptorParamValue($user, ROLE_ID_CAMPUS_CHAMPION, 'provider');
        }

        switch ($searchMode) {
            case 'formal_name':
                $searchMethod = FORMAL_NAME_SEARCH;
                break;
            case 'username':
                $searchMethod = USERNAME_SEARCH;
        }

        $dataWarehouse = new XDWarehouse();

        list($userCount, $users) = $dataWarehouse->enumerateGridUsers(
            $searchMethod,
            $start,
            $limit,
            $nameFilter,
            $piOnly,
            $universityId
        );

        $entryId = 0;
        $userEntries = [];
        foreach ($users as $currentUser) {
            $entryId++;

            $personName = 'Invalid';
            $personId = -666;
            switch ($searchMode) {
                case 'formal_name':
                    $personName = $currentUser['long_name'];
                    $personId = $currentUser['id'];
                    break;
                case 'username':
                    $personName = $currentUser['abusername'];
                    $personId = sprintf('%s;%s', $currentUser['id'], $currentUser['abusername']);
                    break;
            }
            $userEntries[] = [
                'id' => $entryId,
                'person_id' => $personId,
                'person_name' => $personName
            ];
        }

        return $this->json([
            'success' => true,
            'status' => 'success',
            'message' => 'success',
            'total_user_count' => $userCount,
            'users' => $userEntries
        ]);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route("/current", name: "get_current_user", methods: ["GET"])]
    public function getCurrentUser(Request $request)
    {
        $this->authorize($request);

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
     *
     * @param Request $request
     * @return Response
     * @throws Exception if unable to look up an XDUser by the currently logged in user's id.
     */
    #[Route("/current", name: "update_current_user", methods: ["PATCH"])]
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
     * This endpoint is meant to return the metadata about a users API Token. The actual API token hash will never be
     * included in the data returned. To receive a successful response from this endpoint a user must fulfill the
     * following conditions:
     *   - They just have authenticated to XDMoD via one of the supported methods.
     *   - They must have an active API Token.
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/current/api/token', methods: ['GET'])]
    public function getCurrentAPIToken(Request $request): Response
    {
        $user = $this->authorize($request);

        if ($this->canCreateToken($user)) {
            throw new NotFoundHttpException('API token not found.');
        }

        $tokenData = $this->getCurrentAPITokenMetaData($user);

        return $this->json([
            'success' => true,
            'data' => $tokenData
        ]);
    }

    /**
     * This endpoint will attempt to create a new API token for the requesting user. To successfully call this endpoint
     * a user must fulfill the following requirements:
     *   - They just have authenticated to XDMoD via one of the supported methods.
     *   - They must not have an existing API Token.
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception if there is a problem retrieving a database connection.
     */
    #[Route('/current/api/token', methods: ['POST'])]
    public function createAPIToken(Request $request): Response
    {
        $user = $this->authorize($request);

        if (!$this->canCreateToken($user)) {
            throw new ConflictHttpException('Token already exists.');
        }

        return $this->json(array(
            'success' => true,
            'data' => $this->createToken($user)
        ));
    }


    /**
     * This endpoint will attempt to revoke the currently active api token for the requesting user. To successfully call
     * this endpoint a user must fulfill the following requirements:
     *   - They must have authenticated to XDMoD via one of the supported methods.
     *   - They must have an active API Token
     *
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/current/api/token', methods: ['DELETE'])]
    public function revokeAPIToken(Request $request): Response
    {
        $user = $this->authorize($request);

        // If we can create a token then we can't really revoke it can we.
        if ($this->canCreateToken($user)) {
            throw new NotFoundHttpException('API token not found.');
        }

        // Attempt to revoke the requesting users token.
        if ($this->revokeToken($user)) {
            return $this->json(array(
                'success' => true,
                'message' => 'Token successfully revoked.'
            ));
        }

        // If the `revokeToken` failed for some reason then we let the user know.
        throw new Exception('Unable to revoke API token.');
    }


    /**
     * Extract information from a user object.
     *
     * Ported from: classes/REST/Portal/Profile.php
     *
     * @param XDUser $user The user object to extract data from.
     * @return array        An associative array of data for the user.
     * @throws Exception
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

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email_address' => $emailAddress,
            'is_sso_user' => $user->isSSOUser(),
            'first_time_login' => $user->getCreationTimestamp() == $user->getLastLoginTimestamp(),
            'autoload_suppression' => SessionSingleton::getSession()->get('suppress_profile_autoload', false),
            'field_of_science' => $user->getFieldOfScience(),
            'active_role' => $mostPrivilegedFormalName,
            'most_privileged_role' => $mostPrivilegedFormalName,
            'person_id' => $user->getPersonID(true),
            'raw_data_allowed_realms' => $rawDataRealms
        ];
    }

    /**
     * Extract user profile properties from a request that are allowed to be
     * set by the user.
     *
     * @param Request $request The request to extract properties from.
     * @return array            An array containing properties
     */
    private function extractUserSettableProperties(Request $request)
    {
        $requestProperties = array();
        $this->logger->debug('Getting User Settable Properties');
        foreach (self::$userSettableProperties as $propertyName => $propertyType) {
            $propertyValue = $request->get($propertyName);
            $this->logger->debug('Checking Property', [$propertyName, $propertyValue, $propertyType]);
            if ($propertyValue === null) {
                continue;
            }

            // Check to make sure that the property value type is what we expect.
            if (get_debug_type($propertyValue) !== $propertyType) {
                throw new BadRequestHttpException(
                    sprintf(
                        "Invalid value for $propertyName. Must be a(n) %s.",
                        $propertyType
                    )
                );
            }
            $requestProperties[$propertyName] = $propertyValue;
        }
        $this->logger->debug('Returning user settable properties', [var_export($requestProperties, true)]);
        return $requestProperties;
    }

    /**
     * Update a user with new details.
     *
     * @param XDUser $user The user to update.
     * @param array $updatedProperties A mapping of properties to update
     *                                   to their new values.
     *
     * @throws Exception The new property values failed to save.
     */
    private function updateUser(XDUser $user, array $updatedProperties)
    {
        // For each property that can be set, check if it is included in the
        // given set of properties. If so, invoke that property's setter on the
        // given user with the given property value.
        foreach ($updatedProperties as $propertyName => $propertyValue) {
            $this->logger->debug('Checking Update Property', [$propertyName, !array_key_exists($propertyName, self::$propertySettingOptions)]);
            if (!array_key_exists($propertyName, self::$propertySettingOptions)) {
                continue;
            }
            $propertyOptions = self::$propertySettingOptions[$propertyName];
            $this->logger->debug(sprintf('Calling %s w/ %s', $propertyOptions['setter'], $propertyValue));
            $user->{$propertyOptions['setter']}($propertyValue);
        }
        $this->logger->debug('Saving User!');
        // Attempt to save the user's new details. This will throw an exception
        // if an error occurs.
        $this->logger->debug('Updating User', [$user->getUserId(), $user->getUsername(), var_export($updatedProperties, true)]);
        $user->saveUser();
    }

    /**
     * This function will determine whether or not the provided $user should be allowed to create a new API token. A
     * user will only be allowed to create a new API token if they do not currently have an active API token.
     *
     * @param XDUser $user
     * @return bool true if the user does not already have a valid API token.
     * @throws Exception if there is a problem retrieving a database connection.
     */
    private function canCreateToken(XDUser $user)
    {
        $db = DB::factory('database');

        $query = <<<SQL
SELECT 1
FROM moddb.Users u
    LEFT JOIN moddb.user_tokens AS ut
        ON ut.user_id = u.id
WHERE    u.id = :user_id
     AND ut.user_token_id IS NOT NULL
     AND u.account_is_active = 1
SQL;

        $rows = $db->query($query, array(':user_id' => $user->getUserID()));

        return empty($rows);
    }

    /**
     * A helper function that will retrieve the created_on and expires_on information for the provided $user's currently
     * active token.
     *
     * @param XDUser $user whose token data should be retrieved.
     * @return array in the format array('created_on' => createdOn, 'expiration_date' => expirationDate)
     * @throws Exception if there is a problem retrieving a db connection.
     * @throws Exception if there is a problem executing the SELECT statement.
     */
    private function getCurrentAPITokenMetaData(XDUser $user)
    {
        $db = DB::factory('database');
        $query = <<<SQL
SELECT created_on,
       expires_on
FROM moddb.user_tokens as at
WHERE at.user_id = :user_id;
SQL;
        $rows = $db->query($query, array(':user_id' => $user->getUserID()));

        if (count($rows) !== 1) {
            throw new Exception('Invalid token data returned.');
        }

        return array(
            'created_on' => $rows[0]['created_on'],
            'expiration_date' => $rows[0]['expires_on']
        );
    }

    /**
     * Creates a new API token for the provided $user. Note, the results of a successful creation is the only time that
     * the token will be visible to the user. No other function will return this value.
     *
     * @param XDUser $user
     *
     * @return array in the format ('token' => newToken, 'expiration_date' => tokenExpirationDate)
     *
     * @throws Exception if unable to retrieve a database connection or if there is a problem generating a random token.
     * @throws Exception if the api_token.expiration_interval configuration value ( in portal_settings.ini ) is not set.
     * @throws Exception if inserting the newly generated token is unsuccessful. i.e. the number of rows inserted is < 1.
     */
    private function createToken(XDUser $user)
    {
        $query = <<<SQL
INSERT INTO moddb.user_tokens (user_id, token, created_on, expires_on)
VALUES(:user_id, :token, :created_on, :expires_on);
SQL;
        $db = DB::factory('database');

        // We need to, when presented with a token know which user it is for. To allow for this the tokens stored in the
        // db will encode the Users.id value along with the hashed token value. This will mean that some pre-processing
        // will need to occur when attempting to validate the token, but it alleviates the problem of having to attempt
        // to match every token in the db to find which user it's meant to authenticate.
        $password = bin2hex(random_bytes(32));
        $hash = password_hash($password, PASSWORD_DEFAULT, array('cost' => 12));

        $createdOn = date_create()->format('Y-m-d H:m:s');
        $expirationInterval = \xd_utilities\getConfiguration('api_token', 'expiration_interval');
        if (empty($expirationInterval)) {
            throw new Exception('Expiration Interval not provided.');
        }
        $dateInterval = date_interval_create_from_date_string($expirationInterval);
        $expirationDate = date_add(date_create(), $dateInterval)->format('Y-m-d H:m:s');

        $result = $db->execute(
            $query,
            array(
                ':user_id' => $user->getUserID(),
                ':token' => $hash,
                ':created_on' => $createdOn,
                ':expires_on' => $expirationDate
            )
        );

        if ($result !== 1) {
            throw new Exception('Unable to create a new API token.');
        }

        return array(
            'token' => sprintf('%s.%s', $user->getUserID(), $password),
            'expiration_date' => $expirationDate,
        );
    }

    /**
     * Attempts to revoke the currently active token for the provided $user.
     *
     * @param XDUser $user whose active token will be revoked.
     * @return bool true if 1 row was deleted else false.
     * @throws Exception if there was a problem retrieving a database connection.
     * @throws Exception if there was an error while executing the DELETE statement.
     */
    private function revokeToken(XDUser $user)
    {
        $query = 'DELETE FROM moddb.user_tokens WHERE user_id = :user_id';
        $db = DB::factory('database');

        $rows = $db->execute($query, array(':user_id' => $user->getUserID()));

        return $rows === 1;
    }

}
