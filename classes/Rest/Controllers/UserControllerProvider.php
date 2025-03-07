<?php

namespace Rest\Controllers;

use CCR\DB;
use Configuration\Configuration;
use Firebase\JWT\JWT;
use Models\Services\Organizations;
use PhpOffice\PhpWord\Exception\Exception;
use Silex\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
        $controller->get("$root/current/api/token", '\Rest\Controllers\UserControllerProvider::getCurrentAPIToken');
        $controller->post("$root/current/api/token", '\Rest\Controllers\UserControllerProvider::createAPIToken');
        $controller->delete("$root/current/api/token", '\Rest\Controllers\UserControllerProvider::revokeAPIToken');
        $controller->get("$root/current/api/jsonwebtoken", '\Rest\Controllers\UserControllerProvider::createJSONWebToken');
    }

    /**
     * Get details for the current user.
     *
     * @param Request $request The request used to make this call.
     * @param Application $app The router application.
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
     * @param Request $request The request used to make this call.
     * @param Application $app The router application.
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
     * This endpoint is meant to return the metadata about a users API Token. The actual API token hash will never be
     * included in the data returned. To receive a successful response from this endpoint a user must fulfill the
     * following conditions:
     *   - They just have authenticated to XDMoD via one of the supported methods.
     *   - THey must have an active API Token.
     *
     * @param Request $request
     * @param Application $app
     * @return mixed
     * @throws \Exception
     */
    public function getCurrentAPIToken(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        if ($this->canCreateToken($user)) {
            throw new NotFoundHttpException('API token not found.');
        }

        $tokenData = $this->getCurrentAPITokenMetaData($user);

        return $app->json(array(
                'success' => true,
                'data' => $tokenData
            )
        );
    }

    /**
     * This endpoint will attempt to create a new API token for the requesting user. To successfully call this endpoint
     * a user must fulfill the following requirements:
     *   - They just have authenticated to XDMoD via one of the supported methods.
     *   - They must not have an existing API Token.
     *
     * @param Request $request
     * @param Application $app
     * @return Response
     * @throws \Exception if there is a problem retrieving a database connection.
     */
    public function createAPIToken(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        if (!$this->canCreateToken($user)) {
            throw new ConflictHttpException('Token already exists.');
        }

        return $app->json(array(
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
     * @param Request $request
     * @param Application $app
     * @return Response
     * @throws \Exception
     */
    public function revokeAPIToken(Request $request, Application $app)
    {
        $user = $this->authorize($request);

        // If we can create a token then we can't really revoke it can we.
        if ($this->canCreateToken($user)) {
            throw new NotFoundHttpException('API token not found.');
        }

        // Attempt to revoke the requesting users token.
        if ($this->revokeToken($user)) {
            return $app->json(array(
                'success' => true,
                'message' => 'Token successfully revoked.'
            ));
        }

        // If the `revokeToken` failed for some reason then we let the user know.
        throw new Exception('Unable to revoke API token.');
    }

    /**
     *
     * @param Request $request
     * @param Application $app
     * @return Response
     * @throws \Exception if there is a problem retrieving a database connection.
     */
    public function createJSONWebToken(Request $request, Application $app)
    {
        try {
            $user = $this->authorize($request);
        } catch (UnauthorizedHttpException | AccessDeniedException $e) {
            return new RedirectResponse("/");
        }

        $secretKey  = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $tokenId    = base64_encode(random_bytes(16));
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+6 minutes')->getTimestamp();

        $data = [
            'iat'  => $issuedAt->getTimestamp(),
            'jti'  => $tokenId,
            'exp'  => $expire,
            'upn'  => $user->getUserName()
        ];

        $jwt = JWT::encode(
            $data,
            $secretKey,
            'HS256'
        );

        $cookie = new Cookie('xdmod_jwt', $jwt);
        $jupyterhub_url = \xd_utilities\getConfiguration('jupyterhub', 'url');
        $response = new RedirectReponse($jupyterhub_url);
        return $response->headers->setCookie($cookie);
    }

    /**
     * Extract information from a user object.
     *
     * Ported from: classes/REST/Portal/Profile.php
     *
     * @param XDUser $user The user object to extract data from.
     * @return array        An associative array of data for the user.
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
     * @param Request $request The request to extract properties from.
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

    /**
     * This function will determine whether or not the provided $user should be allowed to create a new API token. A
     * user will only be allowed to create a new API token if they do not currently have an active API token.
     *
     * @param XDUser $user
     * @return bool true if the user does not already have a valid API token.
     * @throws \Exception if there is a problem retrieving a database connection.
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
     * @throws \Exception if there is a problem retrieving a db connection.
     * @throws \Exception if there is a problem executing the SELECT statement.
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
            throw new \Exception('Invalid token data returned.');
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
     * @throws \Exception if unable to retrieve a database connection or if there is a problem generating a random token.
     * @throws \Exception if the api_token.expiration_interval configuration value ( in portal_settings.ini ) is not set.
     * @throws \Exception if inserting the newly generated token is unsuccessful. i.e. the number of rows inserted is < 1.
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
            throw new \Exception('Expiration Interval not provided.');
        }
        $dateInterval = date_interval_create_from_date_string($expirationInterval);
        $expirationDate = date_add(date_create(), $dateInterval)->format('Y-m-d H:m:s');

        $result = $db->execute(
            $query,
            array(
                ':user_id'    => $user->getUserID(),
                ':token'      => $hash,
                ':created_on' => $createdOn,
                ':expires_on' => $expirationDate
            )
        );

        if ($result != 1) {
            throw new \Exception('Unable to create a new API token.');
        }

        return array(
            'token'           => sprintf('%s.%s', $user->getUserID(), $password),
            'expiration_date' => $expirationDate,
        );
    }

    /**
     * Attempts to revoke the currently active token for the provided $user.
     *
     * @param XDUser $user whose active token will be revoked.
     * @return bool true if 1 row was deleted else false.
     * @throws \Exception if there was a problem retrieving a database connection.
     * @throws \Exception if there was an error while executing the DELETE statement.
     */
    private function revokeToken(XDUser $user)
    {
        $query = 'DELETE FROM moddb.user_tokens WHERE user_id = :user_id';
        $db = DB::factory('database');

        $rows = $db->execute($query, array(':user_id' => $user->getUserID()));

        return $rows === 1;
    }
}
