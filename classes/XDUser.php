<?php

use CCR\DB;
use CCR\Log;
use CCR\MailWrapper;
use Models\Acl;
use Models\Services\Acls;
use Models\Services\Organizations;
use DataWarehouse\Query\Exceptions\AccessDeniedException;

/**
 * XDMoD Portal User
 *
 * @Class XDUser
 */
class XDUser extends CCR\Loggable implements JsonSerializable
{

    private $_pdo;                       // PDO Handle (set in __construct)

    private $_id;
    private $_account_is_active;

    private $_username;
    private $_password;
    private $_email;

    private $_firstName;
    private $_middleName;
    private $_lastName;

    private $_timeCreated;
    private $_timeUpdated;
    private $_timePasswordUpdated;

    private $_roles;

    private $_field_of_science = 0;

    private $_organizationID;
    private $_personID;

    private $_user_type = 0;

    private $_update_token = false;
    private $_token;

    /**
     * An array that is assumed to be stored in the following manner:
     *   _acls[$acl->name] = $acl;
     * @var Acl[]
     */
    private $_acls;

    /**
     * @var Acl
     */
    private $_mostPrivilegedAcl;

    /**
     * A static reference to the public user. That is used as a singleton so
     * that the public user need only be retrieved from the db once. Note that
     * this is different from how the public user was previously used /
     * utilized. Previously the public user only existed as an ephemeral object
     * with no backing in the database. This was causing problems with the new
     * Acl system ( having user -> acl relations explicitly defined ) so we
     * now have a real (i.e. has a record in a table) public user w/ a real
     * public acl.
     *
     * @var XDUser
     */
    private static $_publicUser;

    const PUBLIC_USER = 1;
    const INTERNAL_USER = 2;

    const ADMIN_NOTIFICATION_EMAIL = <<<EML

User Organization Update --------------------------------
Name:             %s
Username:         %s
E-Mail:           %s
Old Organization: %s
New Organization: %s
Old Acls:         %s
New Acls:         %s
EML;

    const USER_NOTIFICATION_EMAIL = <<<EML
Dear %s,

The organization associated with your XDMoD user account has been automatically
updated from %s to %s. You will no longer be able to view non-public data
from %s.

If you were not expecting this change or the new organization affiliation is
incorrect then please contact support at %s.

%s
EML;

    const UAGBP_INSERT_QUERY = <<<SQL
INSERT INTO user_acl_group_by_parameters(user_id, acl_id, group_by_id, value)
SELECT inc.*
FROM (
    SELECT u.id           AS user_id,
           a.acl_id       AS acl_id,
           gb.group_by_id AS group_by_id,
           CASE
               WHEN gb.name IN ('provider', 'organization', 'institution') THEN u.organization_id
               WHEN gb.name IN ('person', 'pi') THEN u.person_id
               END        AS value
    FROM acl_dimensions ad
        JOIN group_bys  gb ON ad.group_by_id = gb.group_by_id
        JOIN user_acls  ua ON ad.acl_id = ua.acl_id
        JOIN Users      u ON ua.user_id = u.id
        JOIN acls       a ON ad.acl_id = a.acl_id
    WHERE u.id =     :user_id AND
          a.acl_id = :acl_id
)                                          inc
    LEFT JOIN user_acl_group_by_parameters cur
                  ON cur.user_id = inc.user_id AND
                     cur.acl_id = inc.acl_id AND
                     cur.group_by_id = inc.group_by_id AND
                     cur.value = inc.value
WHERE cur.user_acl_parameter_id IS NULL;
SQL;


    /**
     * The acls in OpenXDMoD that have a dependency on centers / organizations.
     * NOTE: This should be pulled from a configuration file not hard coded. Explore in future
     * commit.
     *
     * @var array
     */
    public static $CENTER_ACLS = array('cd', 'cs', 'cc');

    /**
     * These are the only SSO attribtutes that should be included when setting `$this->ssoAttrs;`
     *
     * @var array
     */
    private static $INCLUDE_SSO_ATTRS = array('username', 'organization', 'system_username', 'email_address');

    /**
     * The attributes present when a user logs in via SSO. Defaults to an empty array otherwise.
     *
     * @var array
     */
    private $ssoAttrs;

    /**
     * The current session token for this user. Populated via XDSessionManager::recordLogin when
     * XDUser::postLogin() is called.
     *
     * @var string
     */
    private $currentToken;

    /**
     * The state of this user's `sticky` bit. Corresponds to the moddb.Users.sticky column.
     * Indicates that the organization_id and or the person_id has been manually overridden and
     * should not be automatically updated.
     *
     * @var boolean
     */
    private $sticky;
    // ---------------------------

    /*
     *
     * @constructor
     *
     * @param string $username
     * @param string $password
     * @param string $email_address
     * @param string $first_name
     * @param string $last_name
     * @param array  $role_set
     * @param string $primary_role
     *
     */

    function __construct(
        $username = null,
        $password = null,
        $email_address = NO_EMAIL_ADDRESS_SET,
        $first_name = null,
        $middle_name = null,
        $last_name = null,
        $role_set = array(ROLE_ID_USER),
        $primary_role = ROLE_ID_USER,
        $organization_id = null,
        $person_id = null,
        array $ssoAttrs = array(),
        $sticky = false
    ) {

        $this->_pdo = DB::factory('database');

        $userCheck = $this->_pdo->query("SELECT id FROM Users WHERE username=:username", array(
            ':username' => $username,
        ));

        if (count($userCheck) > 0 && $username != NULL) {

            $username = preg_replace('/^(.+);(.+)$/', '$1 ($2)', $username);

            throw new Exception("User $username already exists");

        }

        $this->_id = NULL;

        $this->_account_is_active = true;

        $this->_username = $username;
        $this->_password = $password;

        if (self::userExistsWithEmailAddress($email_address) != INVALID) {
            throw new Exception("An XDMoD user with e-mail address $email_address exists");
        }

        $this->_email = $email_address;

        $this->_firstName = $first_name;
        $this->_middleName = $middle_name;
        $this->_lastName = $last_name;

        $this->setRoles($role_set);

        // Role Checking ====================

        if (count($this->_roles) == 0) {
            throw new Exception("At least one role must be associated with this user");
        }

        foreach ($this->_roles as $role) {

            if (self::_getFormalRoleName($role) == NULL) {
                throw new Exception("Unrecognized role $role");
            }

        }

        // =================================

        $this->_timeCreated = date('Y-m-d H:i:s');
        $this->_timeUpdated = NULL;
        $this->_timePasswordUpdated = NULL;

        $this->_organizationID = $organization_id;

        // A person id of 0 is not allowed
        $this->_personID = $person_id == 0 ? NULL : $person_id;    //This user MUST have a person_id mapping

        $this->_update_token = true;
        $this->_token = NULL;

        $this->sticky = $sticky;

        parent::__construct(
            Log::factory(
                'xduser.sql',
                array(
                    'db' => false,
                    'mail' => false,
                    'console' => false,
                    'file'=> LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'exceptions_logfile')
                )
            )
        );

        $this->setSSOAttrs($ssoAttrs);
    }//construct

    // ---------------------------

    /*
     *
     * @function reloadUser  (Retrieves updated information for the user from the database)
     *
     */

    public function reloadUser()
    {

        $this->getUserById($this->_id, $this);

    }//reloadUser

    // ---------------------------

    /*
     *
     * @function getProfile
     *
     * @return XDUserProfile
     * @throws Exception if this user does not have an ID (due to the user data never being saved in the first place)
     *         e.g.  A new user is created, yet not saved prior to calling getProfile()
     *
     */

    public function getProfile()
    {

        if (!isset($this->_id)) {
            throw new Exception('This user must be saved first.');
        }

        return new XDUserProfile($this->_id);

    }//getProfile

    // ---------------------------

    /*
     *
     * @function userExistsWithUsername
     *
     * @param string $username
     *
     * @return int (if not INVALID, then the function returns the id of the respective account)
     *
     */

    public static function userExistsWithUsername($username)
    {

        $pdo = DB::factory('database');

        $userCheck = $pdo->query("SELECT id FROM Users WHERE username=:username", array(
            ':username' => $username,
        ));

        if (count($userCheck) > 0) {
            return $userCheck[0]['id'];
        } else {
            return INVALID;
        }

    }//userExistsWithUsername

    // ---------------------------

    /*
     *
     * @function userExistsWithEmailAddress
     *
     * @param string $email_address
     * @param boolean $pass_reset_mode (optional)
     *
     * $include_exception_addresses      possible return values
     * -------------------------------------------------------------------
     * FALSE                             positive value*, INVALID
     * TRUE                              positive value*, INVALID, AMBIGUOUS
     *
     * (*) if a positive value is returned, then it is the id of the user holding that unique e-mail address
     *
     * @return int (if not INVALID or AMBIGUOUS, then the function returns the id of the respective account)
     *             (returns INVALID if no account is mapped to the e-mail address)
     *             (returns AMBIGUOUS if more than one account is mapped to the e-mail address)
     *
     */

    public static function userExistsWithEmailAddress($email_address, $include_exception_addresses = FALSE)
    {

        if ($email_address == NO_EMAIL_ADDRESS_SET) {

            //Empty values for e-mail address are allowed
            return INVALID;

        }

        $pdo = DB::factory('database');

        $user_check_query = "SELECT id FROM Users WHERE email_address=:email_address AND user_type != :user_type";

        if ($include_exception_addresses == FALSE) {

            // If a user is attempting to reset their password based on their e-mail address, it is important that
            // the e-mail address does NOT map to more than one account (we cannot deal with multiple users mapped
            // to a common e-mail address).  $include_exception_addresses is set to TRUE only in the pass_reset
            // controller of user_auth -- which would not append the following to the SELECT query:

            $user_check_query .= " AND email_address NOT IN (SELECT email_address FROM ExceptionEmailAddresses)";

        }

        // We don't want to acknowledge Single Sign On-derived accounts...

        $userCheck = $pdo->query(
            $user_check_query,
            array(
                'email_address' => $email_address,
                'user_type' => SSO_USER_TYPE
            )
        );

        if (count($userCheck) == 1) {
            return $userCheck[0]['id'];
        } elseif (count($userCheck) > 1) {

            // E-mail address maps to more than one account (present in ExceptionEmailAddresses table)
            return AMBIGUOUS;

        } else {

            // No user maps to $email_address
            return INVALID;

        }

    }//userExistsWithEmailAddress

    // ---------------------------

    /*
     *
     * @function getFieldOfScience
     *
     * @return int
     *
     */

    public function getFieldOfScience()
    {

        return $this->_field_of_science;

    }//getFieldOfScience

    // ---------------------------

    /*
     *
     * @function setFieldOfScience
     *
     * @param int $field_of_science
     *
     */

    public function setFieldOfScience($field_of_science)
    {

        $this->_field_of_science = $field_of_science;

    }//setFieldOfScience

    // ---------------------------

    /*
     *
     * @function getUserByToken
     *
     * @param string $token
     *
     * @return XDUser if $token maps to a user
     * @return NULL if $token does not map to a user
     *
     */

    public static function getUserByToken($token)
    {

        $pdo = DB::factory('database');

        $userCheck = $pdo->query("SELECT id FROM Users WHERE token LIKE BINARY :token", array(
            ':token' => $token,
        ));

        if (count($userCheck) == 0) {
            return NULL;
        }

        return self::getUserByID($userCheck[0]['id']);

    }//getUserByToken

    // ---------------------------


    /**
     * Attempt to retrieve an XDUser instance representation of the Public User.
     * If, for some reason the Public User has not been created then this
     * function will return null.
     * cl
     * @return null|XDUser
     */
    public static function getPublicUser()
    {
        if (null === self::$_publicUser) {
            self::$_publicUser = self::getUserByUserName('Public User');
        }
        return self::$_publicUser;
    }//getPublicUser

    // ---------------------------

    /**
     * Check if this user is a public user.
     *
     * @return boolean If this user is a public user, true. Otherwise, false.
     */
    public function isPublicUser()
    {
        return array_key_exists(ROLE_ID_PUBLIC, $this->_acls);
    }

    // ---------------------------

    /*
     *
     * @function getUserByID
     *
     * @param int $uid
     * @param XDUser REF $targetInstance (If NOT NULL, then re-populate $targetInstance with values)
     *
     * @return XDUser (If &$targetInstance IS NOT NULL, then this function can be treated as void)
     *                (If &$targetInstance IS NULL, then this function should be used as follows: $user = getUserByID(...))
     *
     */

    public static function getUserByID($uid, &$targetInstance = NULL)
    {

        $pdo = DB::factory('database');

        $userCheck = $pdo->query("
         SELECT id, username, password, email_address, first_name, middle_name, last_name,
         time_created, time_last_updated, password_last_updated, account_is_active, organization_id, person_id, field_of_science, token, user_type, sticky
         FROM Users
         WHERE id=:id
      ", array(
            ':id' => $uid,
        ));

        if (count($userCheck) == 0) {
            return NULL;
        }

        $user = ($targetInstance == NULL) ? new self : $targetInstance;

        if ($targetInstance == NULL) {
            // If requesting another user object, make sure to not update the user's token unless
            // issueNewToken() is explicitly invoked (we don't want to update the user's token by default upon calling saveUser())
            $user->_update_token = false;
        }

        $user->_id = $userCheck[0]['id'];

        $user->_account_is_active = ($userCheck[0]['account_is_active'] == '1');

        $user->_username = $userCheck[0]['username'];
        $user->_password = $userCheck[0]['password'];

        $user->_email = $userCheck[0]['email_address'];

        $user->_firstName = $userCheck[0]['first_name'];
        $user->_middleName = $userCheck[0]['middle_name'];
        $user->_lastName = $userCheck[0]['last_name'];

        $user->_timeCreated = $userCheck[0]['time_created'];
        $user->_timeUpdated = $userCheck[0]['time_last_updated'];
        $user->_timePasswordUpdated = $userCheck[0]['password_last_updated'];

        $user->_organizationID = $userCheck[0]['organization_id'];
        $user->_personID = $userCheck[0]['person_id'];

        $user->_field_of_science = $userCheck[0]['field_of_science'];
        $user->_token = $userCheck[0]['token'];

        // datatypes are not passed back unless using prepared statements so force to int
        // See: http://stackoverflow.com/questions/1197005/how-to-get-numeric-types-from-mysql-using-pdo
        $user->_user_type = (int)$userCheck[0]['user_type'];

        $user->sticky = (bool)$userCheck[0]['sticky'];

        $user->_mostPrivilegedAcl = Acls::getMostPrivilegedAcl($user);

        // BEGIN: ACL population
        $query = <<<SQL
SELECT a.*, ua.user_id
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
  LEFT JOIN (
    SELECT
      ah.acl_id,
      ah.level
    FROM acl_hierarchies ah
      JOIN hierarchies h
        ON ah.hierarchy_id = h.hierarchy_id
    WHERE h.name = :acl_hierarchy_name
  ) aclh
    ON aclh.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
      AND a.enabled = TRUE
ORDER BY COALESCE(aclh.level, 0) DESC;
SQL;
        $results = $pdo->query(
            $query,
            array(
                'user_id' => $uid,
                ':acl_hierarchy_name' => 'acl_hierarchy'
            )
        );

        $acls = array_reduce($results, function ($carry, $item) {
            $acl = new Acl($item);
            $carry [$acl->getName()] = $acl;
            return $carry;
        }, array());

        $user->setAcls($acls);
        // END: ACL population

        // we do this instead of calling `setRoles` as `setRoles` will end up
        // making a db call per role to keep the acls in sync. And in the end
        // the results will be the same.
        $user->_roles = $user->getAcls(true);


        return $user;

    }//getUserByID

    // ---------------------------

    /*
     *
     * @function setPassword
     *
     * @param string $raw_password
     *
     */

    public function setPassword($raw_password)
    {
        if ($this->getUserType() === SSO_USER_TYPE) {
            throw new AccessDeniedException("Permission Denied. Only local accounts may have their passwords modified.");
        }

        return $this->_password = $raw_password;
    }//setPassword

    // ---------------------------

    /*
     *
     * @function getUsername
     *
     * @return string
     *
     */

    public function getUsername()
    {

        return $this->_username;

    }//getUsername

    // ---------------------------

    /*
     *
     * @function isDeveloper
     *
     * @return boolean
     *
     */

    public function isDeveloper()
    {

        return (array_key_exists(ROLE_ID_DEVELOPER, $this->_acls));

    }//isDeveloper

    // ---------------------------

    /*
     *
     * @function isManager
     *
     * @return boolean
     *
     */

    public function isManager()
    {

        return array_key_exists(ROLE_ID_MANAGER, $this->_acls);

    }//isManager

    // ---------------------------

    /*
     *
     * @function isPrincipalInvestigator
     *
     * @param int $person_id
     *
     * @return boolean
     *
     */

    public static function isPrincipalInvestigator($person_id)
    {

        $pdo = DB::factory('database');

        $piCheck = $pdo->query(
            "SELECT person_id FROM modw.piperson WHERE person_id=:person_id",
            array('person_id' => $person_id)
        );

        return (count($piCheck) == 1);

    }//isPrincipalInvestigator

    // ---------------------------

    /*
     *
     * @function isCampusChampion
     *
     * @param int $person_id
     *
     * @returns false (boolean) if the person referenced by person_id is NOT a campus champion
     * @returns the numerical organization id (int) if the person IS a campus champion
     *
     */

    public static function isCampusChampion($person_id)
    {

        $pdo = DB::factory('database');

        $ccCheck = $pdo->query(

            "SELECT DISTINCT acct.id, acct.granttype_id
                          FROM modw.account AS acct, modw.peopleonaccount AS poa
                          WHERE poa.person_id=:person_id AND acct.id = poa.account_id AND acct.granttype_id = 2",

            array('person_id' => $person_id)

        );

        if (count($ccCheck) > 0) {

            $orgCheck = $pdo->query("SELECT organization_id FROM modw.person WHERE id=:person_id", array('person_id' => $person_id));

            return $orgCheck[0]['organization_id'];

        } else {

            return false;

        }

    }//isCampusChampion

    // ---------------------------

    /*
     *
     * @function authenticate
     *
     * This function may only be used to authenticate users that have local user
     * account credentials. Users must have non-empty usernames and passwords.
     *
     * @param string $uname
     * @param string $pass    <-- MD5 hash
     *
     * @return XDUser
     *
     */

    public static function authenticate($uname, $pass)
    {

        if (strlen($uname) == 0 || strlen($pass) == 0) {
            return null;
        }

        $pdo = DB::factory('database');

        $userCheck = $pdo->query(
            "SELECT
                id, password
            FROM
                Users
            WHERE
                username = :username
                AND user_type != :user_type",
            array(
                'username' => $uname,
                'user_type' => SSO_USER_TYPE
            )
        );

        if (count($userCheck) !== 1) {
            return null;
        }

        if (password_verify($pass, $userCheck[0]['password'])) {
            return self::getUserByID($userCheck[0]['id']);
        }

        // Fallback case for older MD5 passwords
        if (md5($pass) == $userCheck[0]['password']) {
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);
            if ($new_hash !== false) {
                $updatestmt = $pdo->prepare("UPDATE Users SET password = :password_hash WHERE id = :id");
                $updatestmt->execute(array('password_hash' => $new_hash, 'id' => $userCheck[0]['id']));
            }
            return self::getUserByID($userCheck[0]['id']);
        }

        return null;

    }//authenticate

    // ---------------------------

    /*
     *
     * @function isAuthenticated
     *
     * @param XDUser $user
     *
     * @return boolean
     *
     */

    public static function isAuthenticated($user)
    {
        return ($user != NULL);
    }

    // ---------------------------

    /*
     *
     * @function issueNewToken
     *
     */

    public function issueNewToken()
    {
        $this->_update_token = true;
    }

    // ---------------------------

    /**
     * Returns a parameterized Update query for the 'User' table. If the
     * $updateToken parameter is set then it includes the 'token'
     * and the 'token_expiration' fields.
     *
     * @param bool $updateToken signifies whether or not to include the 'token'
     *             related columns in the return value.
     * @param bool $includePassword signifies whether or not to include the
     *             'password' related columns in the return value.
     *
     * @return string a parameterized query for the 'User' table
     */
    public function getUpdateQuery($updateToken = false, $includePassword = false)
    {
        $result = 'UPDATE moddb.Users SET username = :username,  email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, user_type = :user_type, sticky = :sticky WHERE id = :id';
        if ($updateToken && $includePassword) {
            $result = 'UPDATE moddb.Users SET username = :username, password = :password, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, token = :token, user_type = :user_type, password_last_updated = :password_last_updated, sticky = :sticky WHERE id = :id';
        } else if (!$updateToken && $includePassword) {
            $result = 'UPDATE moddb.Users SET username = :username, password = :password, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, user_type = :user_type, password_last_updated = :password_last_updated, sticky = :sticky WHERE id = :id';
        } else if ($updateToken && !$includePassword) {
            $result = 'UPDATE moddb.Users SET username = :usernam, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, token = :token, user_type = :user_type, sticky = :sticky WHERE id = :id';
        }
        return $result;
    }

    /**
     * Returns a parameterized Insert query for the 'User' table. If the
     * $updateToken parameter is set then it includes the 'token'
     * and the 'token_expiration' fields.
     *
     * @param bool $updateToken signifies whether or not to include the 'token'
     *             related columns in the return value.
     * @param bool $includePassword signifies whether or not to include the
     *             'password' related columns in the return value.
     *
     * @return string a parameterized query for the 'User' table
     */
    public function getInsertQuery($updateToken = false, $includePassword = false)
    {
        $result = 'INSERT INTO moddb.Users (username, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, user_type, sticky) VALUES (:username, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :user_type, :sticky)';
        if ($updateToken && $includePassword) {
            $result = 'INSERT INTO moddb.Users (username, password, password_last_updated, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, token, user_type, sticky) VALUES (:username, :password, :password_last_updated, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :token, :user_type, :sticky)';
        } else if (!$updateToken && $includePassword) {
            $result = 'INSERT INTO moddb.Users (username, password, password_last_updated,  email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, user_type, sticky) VALUES (:username, :password, :password_last_updated, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :user_type, :sticky)';
        } else if ($updateToken && !$includePassword) {
            $result = 'INSERT INTO moddb.Users (username, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, token, user_type, sticky) VALUES (:username, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :token, :user_type, :sticky)';
        }
        return $result;
    }

    /**
     * Accepts an array and outputs a meaningful string representation of said
     * array.
     *
     * @param array $array the array that is to be converted into a string.
     *
     * @return string representation of the array parameter passed in.
     */
    public function arrayToString($array = array())
    {
        $result = 'Keys [ ';
        $result .= implode(', ', array_keys($array)) . ']';
        $result .= 'Values [ ';
        $result .= implode(', ', array_values($array)) . ']';
        return $result;
    }

    // ---------------------------

    /**
     * Saves the data currently encapsulated by this 'XDUser' object to the
     * correct database tables.
     *
     * @return void does not return anything.
     */
    public function saveUser()
    {
        /* BEGIN: VALIDATION  */
        if ($this->isPublicUser()) {
            throw new Exception('The public role user cannot be saved.');
        }

        if ($this->_user_type == 0) {
            throw new Exception('The user must have a valid user type.');
        }

        if (count($this->_roles) === 0) {
            throw new Exception('A user must have at least one role.');
        }

        if (count($this->_acls) === 0) {
            throw new Exception('A user must have at least one acl.');
        }

        $match = preg_match(RESTRICTION_USERNAME, $this->_username);
        if ($match === false || $match === 0) {
            throw new Exception('A user must have a valid username.');
        }

        // Retrieve the userId (if any) for the email associated with this User
        // object.
        $id_of_user_holding_email_address = self::userExistsWithEmailAddress($this->_email);

        // A common e-mail address CAN be shared among multiple XSEDE accounts...
        // Each XDMoD (local) account must have a distinct e-mail address (unless that e-mail address is present in moddb.ExceptionEmailAddresses)

        // The second condition is in place to account for the case where a new Single Sign On user is being created (and is not currently in the XDMoD DB)
        if (($id_of_user_holding_email_address != INVALID) && ($this->getUserType() != SSO_USER_TYPE)) {

            if (!isset($this->_id)) {
                // This user has no record in the database (never saved).  If $id_of_user_holding_email_address
                // holds a valid id, then an already saved user has the e-mail address.

                throw new Exception("An XDMoD user with e-mail address {$this->_email} exists");
            } else {

                // This user has been saved, so we make sure the $id_of_user_holding_email_address is in fact this user's
                // id... otherwise throw the exception

                if ($id_of_user_holding_email_address != $this->_id) {
                    throw new Exception("An XDMoD user with e-mail address {$this->_email} exists");
                }
            }
        }//if ($id_of_user_holding_email_address != INVALID)

        /* END: VALIDATION  */

        $handle = $this->_pdo->handle();
        $update_data = array();

        // Determine whether or not we're inserting or updating.
        $forUpdate = isset($this->_id);

        /* BEGIN: Query Data Population */
        if ($forUpdate) {
            $update_data['id'] = $this->_id;
        }

        $update_data['username'] = $this->_username;
        $includePassword = strlen($this->_password) <= CHARLIM_PASSWORD;
        if ($includePassword) {
            if ($this->_password == "" || is_null($this->_password)) {
                $update_data['password'] = NULL;
            } else {
                $this->_password = password_hash($this->_password, PASSWORD_DEFAULT);
                $update_data['password'] = $this->_password;
            }
            $update_data['password_last_updated'] = 'NOW()';
        }
        $update_data['email_address'] = ($this->_email);
        $update_data['first_name'] = ($this->_firstName);
        $update_data['middle_name'] = ($this->_middleName);
        $update_data['last_name'] = ($this->_lastName);
        $update_data['account_is_active'] = ($this->_account_is_active) ? '1' : '0';
        $update_data['person_id'] = $this->_personID == null
            ? 'NULL'
            : ($this->_personID);
        $update_data['organization_id'] = $this->_organizationID == null
            ? 'NULL'
            : ($this->_organizationID);
        $update_data['field_of_science'] = ($this->_field_of_science);
        if ($this->_update_token) {
            $update_data['token'] = ($this->_generateToken());
            $this->_token = $update_data['token'];
        }
        $update_data['user_type'] = $this->_user_type;
        $update_data['sticky'] = $this->sticky ? 1 : 0;

        /* END: Query Data Population */
        try {
            /* BEGIN: Construct the parameterized query */
            $query = $forUpdate
                ? $this->getUpdateQuery($this->_update_token, $includePassword)
                : $this->getInsertQuery($this->_update_token, $includePassword);
            /* END: Construct the parameterized query */

            /* BEGIN: Execute the query */
            if ($forUpdate) {
                /* $rowCount = */
                $this->_pdo->execute($query, $update_data);
            } else {
                // NOTE: There may be a better way to do this (atomicity issue) ?
                $new_user_id = $this->_pdo->insert($query, $update_data);
                // New User Creation -- assign the new user id to the associated roles
                $this->_id = $new_user_id;
            }
        } catch (Exception $e) {
            throw new Exception("Exception occured while inserting / updating. UpdateToken: [{$this->_update_token}] Query: [$query] data: [{$this->arrayToString($update_data)}]", null, $e);
        }
        /* END: Execute the query */

        /* BEGIN: Update Token Information */
        if ($this->_update_token) {
            // Set token to expire in 30 days from now...
            $this->_pdo->execute(
                'UPDATE Users SET token_expiration=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id=:id',
                array('id' => $this->_id)
            );
            $this->_update_token = false;
        }
        /* END: Update Token Information */

        /* BEGIN: ACL data processing */

        // REMOVE: existing user -> acl relations
        $this->_pdo->execute(
            'DELETE FROM user_acls WHERE user_id = :user_id',
            array('user_id' => $this->_id)
        );

        // REMOVE: existing user_acl_group_by_parameter records for this user.
        $this->_pdo->execute(
            'DELETE FROM moddb.user_acl_group_by_parameters WHERE user_id = :user_id',
            array('user_id' => $this->_id)
        );

        // ADD: current user -> acl relations
        foreach ($this->_acls as $acl) {
            if (null !== $acl->getAclId()) {
                $params = array(
                    ':user_id' => $this->_id,
                    ':acl_id' => $acl->getAclId()
                );

                // Add the appropriate user_acl record.
                $this->_pdo->execute(
                    'INSERT INTO user_acls(user_id, acl_id) VALUES(:user_id, :acl_id)',
                    $params
                );

                // Add the appropriate user_acl_group_by_parameters record.
                $this->_pdo->execute(
                    self::UAGBP_INSERT_QUERY,
                    $params
                );

            }
        }
        /* END:   ACL data processing */

        // Retrieve this users most privileged acl as it will be used to set the
        // the _active_role property.
        $mostPrivilegedAcl = Acls::getMostPrivilegedAcl($this);

        if (!isset($mostPrivilegedAcl)) {
            throw new Exception('Unable to determine this users most privileged acl. There may be a problem with the state of the database.');
        }

        $timestampData = $this->_pdo->query(
            "SELECT time_created, time_last_updated, password_last_updated
             FROM Users
             WHERE id=:id",
            array('id' => $this->_id)
        );

        $this->_timeCreated = $timestampData[0]['time_created'];
        $this->_timeUpdated = $timestampData[0]['time_last_updated'];
        $this->_timePasswordUpdated = $timestampData[0]['password_last_updated'];

    }//saveUser

    // ---------------------------

    /*
     *
     * @function getLastLoginTimestamp
     *
     * @return string
     *
     */

    public function getLastLoginTimestamp()
    {

        $results = $this->_pdo->query(
            "SELECT
                FROM_UNIXTIME(FLOOR(init_time)) AS lastLogin
            FROM
                SessionManager
            WHERE
                user_id = :user_id
            ORDER BY
                init_time DESC
            LIMIT 1",
            array(
                ':user_id' => $this->_id,
            )
        );

        if (count($results) == 0) {
            return "Never logged in";
        }

        return $results[0]['lastLogin'];
    }//getLastLoginTimestamp

    // ---------------------------

    /*
     *
     * @function getToken
     *
     * @return string
     *
     */

    public function getToken()
    {

        if ($this->isPublicUser()) {
            return '';
        }

        $tokenResults = $this->_pdo->query("SELECT token FROM Users WHERE id=:id", array(
            ':id' => $this->_id,
        ));

        return $tokenResults[0]['token'];

    }//getToken

    // ---------------------------

    /*
     *
     * @function getTokenExpiration
     *
     * @return string
     *
     */

    public function getTokenExpiration()
    {

        if ($this->isPublicUser()) {
            return '';
        }

        $tokenResults = $this->_pdo->query("SELECT token_expiration FROM Users WHERE id=:id", array(
            ':id' => $this->_id,
        ));

        return $tokenResults[0]['token_expiration'];

    }//getTokenExpiration

    /*
     *
     * @function removeUser
     *
     */

    public function removeUser()
    {

        if ($this->isPublicUser()) {
            throw new Exception('Cannot remove public user');
        }

        // Clean up any report-based data generated by the user
        $this->_pdo->execute("DELETE FROM ChartPool WHERE user_id=:user_id", array(
            ':user_id' => $this->_id,
        ));
        $this->_pdo->execute("DELETE FROM Reports WHERE user_id=:user_id", array(
            ':user_id' => $this->_id,
        ));
        $this->_pdo->execute("DELETE FROM ReportCharts WHERE user_id=:user_id", array(
            ':user_id' => $this->_id,
        ));

        $this->_pdo->execute("DELETE FROM user_acl_group_by_parameters WHERE user_id=:user_id", array(
            ':user_id' => $this->_id
        ));

        // Make sure to remove the acl relations
        $this->_pdo->execute("DELETE FROM user_acls WHERE user_id = :user_id", array(
            ':user_id' => $this->_id
        ));

        $this->_pdo->execute("DELETE FROM Users WHERE id=:id", array(
            ':id' => $this->_id,
        ));

    }//removeUser

    // ---------------------------

    /*
     *
     * @function getUserType;
     *
     * @return int (maps to one of the TYPE_* class constants at the top of this file)
     *
     */

    public function getUserType()
    {
        return $this->_user_type;
    }

    // ---------------------------

    /*
     *
     * @function setUserType;
     *
     * @param int $userType
     *
     */

    public function setUserType($userType)
    {
        $this->_user_type = $userType;
    }

    // ---------------------------

    /*
     *
     * @function getAccountStatus;
     *
     * @return boolean
     *
     */

    public function getAccountStatus()
    {
        return $this->_account_is_active;
    }

    // ---------------------------

    /*
     *
     * @function setAccountStatus;
     *
     * @param boolean $status
     *
     */

    public function setAccountStatus($status)
    {
        $this->_account_is_active = $status;
    }

    // ---------------------------

    /*
     *
     * @function getEmailAddress
     *
     * @return string
     *
     */

    public function getEmailAddress()
    {
        return $this->_email;
    }

    // ---------------------------

    /*
     *
     * @function setEmailAddress
     *
     * @param string $email_address
     *
     */

    public function setEmailAddress($email_address)
    {
        $this->_email = $email_address;
    }

    // ---------------------------

    /*
     *
     * @function getFormalName
     *
     * @return string
     *
     */

    public function getFormalName($includeMiddleName = false)
    {
        $formalName = $this->_firstName;
        if ($includeMiddleName) {
            $formalName .= ' ' . $this->_middleName;
        }
        $formalName .= ' ' . $this->_lastName;
        return $formalName;
    }

    // ---------------------------

    /*
     *
     * @function getFirstName
     *
     * @return string
     *
     */

    public function getFirstName()
    {
        return $this->_firstName;
    }

    // ---------------------------

    /*
     *
     * @function setFirstName
     *
     * @param string $firstName
     *
     */

    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
    }

    // ---------------------------

    /*
     *
     * @function getLastName
     *
     * @return string
     *
     */

    public function getLastName()
    {
        return $this->_lastName;
    }

    // ---------------------------

    /*
     *
     * @function setLastName
     *
     * @param string $lastName
     *
     */

    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
    }

    // ---------------------------

    /*
     *
     * @function enumAllAvailableRoles
     *
     * @param int $organization_id
     *
     *
     */

    public function enumAllAvailableRoles()
    {

        if (empty($this->_id)) {

            // It is likely that the public user ended up here
            return array();

        }
        // NOTE: DO NOT PUT ['] in your comments, you will break the sql.
        $query = <<<SQL
SELECT DISTINCT
  CASE WHEN uagbp.user_acl_parameter_id IS NOT NULL
    THEN CONCAT(a.display, ' - ', o.abbrev)
  ELSE a.display
  END                   AS 'description',
  CASE WHEN uagbp.user_acl_parameter_id IS NOT NULL
    THEN CONCAT(a.name, ':', uagbp.value)
  ELSE a.name
  END                   AS 'param_value',
  mp.acl_id IS NOT NULL AS is_primary,
  mp.acl_id IS NOT NULL AS is_active
-- we select from user_acls as this table holds the primary relationship between
-- users and the acls they have relationships with.
FROM user_acls ua
-- acls is joined in strictly to retrieve more detailed display information
  JOIN acls a
    ON a.acl_id = ua.acl_id
-- we left join in user_acl_group_by_parameters as this is where 'center' related
-- information about a user / acl relation is stored. Left join is specifically
-- used so that we will always get all records from user_acls regardless of
-- whether or not there is a corresponding record in
-- user_acl_group_by_parameters
  JOIN acl_types at ON a.acl_type_id = at.acl_type_id
  LEFT JOIN user_acl_group_by_parameters uagbp
    ON uagbp.user_id = ua.user_id AND
       uagbp.acl_id = ua.acl_id AND
       uagbp.group_by_id IN (
         SELECT gb.group_by_id
         FROM group_bys gb
         WHERE gb.name = :group_by_name
       )
-- we left join in modw.organization to retrieve more detailed display
-- information. Its a left join as it will be joined to
-- user_acl_group_by_parameters which is also a left join.
  LEFT JOIN modw.organization AS o
    ON o.id = uagbp.value
-- This left join retrieves what will be our primary sorting value
-- acl_hierarchies.level. It is a left join because it is not expected that all
-- acls will participate in a hierarchy.
  LEFT JOIN (
              SELECT
                ah.acl_id,
                ah.level
              FROM acl_hierarchies ah
                JOIN hierarchies h
                  ON ah.hierarchy_id = h.hierarchy_id
              WHERE h.name = :acl_hierarchy_name
            ) aclh
    ON aclh.acl_id = ua.acl_id
-- This big long left join retrieves the most privileged acl for to determine the
-- is_primary and is_active values. You can reference Acls.php getMostPrivilegedAcl.
  LEFT JOIN (
              SELECT DISTINCT
                a.*,
                aclp.abbrev organization,
                aclp.id     organization_id
              FROM acls a
                JOIN user_acls ua
                  ON a.acl_id = ua.acl_id
                LEFT JOIN (
                            SELECT
                              ah.acl_id,
                              ah.level
                            FROM acl_hierarchies ah
                              JOIN hierarchies h
                                ON ah.hierarchy_id = h.hierarchy_id
                            WHERE h.name = :acl_hierarchy_name
                          ) aclh
                  ON aclh.acl_id = ua.acl_id
                LEFT JOIN (
                            SELECT
                              uagbp.acl_id,
                              o.abbrev,
                              o.id
                            FROM modw.organization o
                              JOIN user_acl_group_by_parameters uagbp
                                ON o.id = uagbp.value
                          ) aclp
                  ON aclp.acl_id = ua.acl_id
              WHERE ua.user_id = :user_id
              ORDER BY COALESCE(aclh.level, 0) DESC
              LIMIT 1
            ) mp
    ON mp.acl_id = ua.acl_id
-- we only want records that are related to a specific user
-- the original sql implicitly left out the flag or feature acls
-- so we need to filter these out here
WHERE ua.user_id = :user_id AND at.name = 'data'
-- In this ordering we use coalesce so that any acl that does not participate
-- in a hierarchy will be sent to the bottom of the list
ORDER BY COALESCE(aclh.level, 0) DESC, a.name
SQL;
        $params = array(
            ':acl_hierarchy_name' => 'acl_hierarchy',
            ':user_id' => $this->_id,
            ':group_by_name' => 'provider'
        );

        try {
            // NOTE: previously we had no DB concept of modules / realms
            // the values that are provided for :module_name, :realm_name, and
            // :group_by_name simulate the behavior of the old system.
            $available_roles = $this->_pdo->query(
                $query,
                $params
            );

            return $available_roles;
        } catch (PDOException $e) {
            $this->logAndThrowException(
                "A PDOException was thrown in 'XDUser::enumAllAvailableRoles'",
                array(
                    'exception' => $e,
                    'sql'=> $query
                )
            );

        }
    }//enumAllAvailableRoles

    // ---------------------------

    /*
     *
     * @function setInstitution
     *
     * @param int $organization_id
     *
     *
     */

    public function setInstitution($institution_id, $is_primary = false)
    {

        // This feature currently applies to campus champions...

        if (empty($this->_id)) {
            throw new Exception("This user must be saved prior to calling setInstitution()");
        }

        $primary_flag = $is_primary ? 1 : 0;

        $aclName = 'cd';

        $aclCleanup = <<<SQL
DELETE FROM user_acl_group_by_parameters
WHERE user_id = :user_id
    AND acl_id IN (
        SELECT
            a.acl_id
        FROM acls a
        WHERE a.name = :acl_name
    )
    AND group_by_id IN (
        SELECT
            gb.group_by_id
        FROM group_bys gb
        WHERE gb.name = 'institution'
    )
SQL;

        $aclInsert = <<<SQL
INSERT INTO user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
SELECT inc.*
FROM (
    SELECT
        :user_id AS user_id,
        a.acl_id AS acl_id,
        gb.group_by_id AS group_by_id,
        :value AS value
    FROM acls a, group_bys gb
    WHERE a.name = :acl_name
    AND gb.name = 'institution'
) inc
LEFT JOIN user_acl_group_by_parameters cur
ON cur.user_id = inc.user_id
AND cur.acl_id = inc.acl_id
AND cur.group_by_id = inc.group_by_id
AND cur.value = inc.value
WHERE cur.user_acl_parameter_id IS NULL;
SQL;

        $this->_pdo->execute($aclCleanup, array(
            ':user_id' => $this->_id,
            ':acl_name' => $aclName
        ));

        $this->_pdo->execute($aclInsert, array(
            ':user_id' => $this->_id,
            ':acl_name' => $aclName,
            ':value' => $institution_id
        ));

    }//setInstitution

    // ---------------------------

    /*
     *
     * @function disassociateWithInstitution
     *
     * @param int $organization_id
     *
     *
     */

    public function disassociateWithInstitution()
    {

        // This feature currently applies to campus champions...

        if (empty($this->_id)) {
            throw new Exception("This user must be saved prior to calling disassociateWithInstitution()");
        }

        $this->_pdo->execute(<<<SQL
        DELETE FROM user_acl_group_by_parameters
WHERE user_id = :user_id
AND group_by_id IN (
SELECT gb.group_by_id
FROM group_bys gb
WHERE gb.name = 'institution');
SQL
            , array(
                ':user_id' => $this->_id
            ));

    }//disassociateWithInstitution

    /*
     *
     * @function setOrganizations
     *
     * @param array $organization_ids  (an array of elements of the form 'organization_id' => '0 or 1')
     *                                 (If the value is 0, then the organization is not primary.  1 if otherwise.)
     * @param string $active_role_id (The active role pending a save to the database)
     * @param string $role (use either ROLE_ID_CENTER_DIRECTOR or ROLE_ID_CENTER_STAFF)
     *
     *
     */

    public function setOrganizations($organization_ids = array(), $role = ROLE_ID_CENTER_DIRECTOR, $reassignActiveToPrimary = false)
    {

        // This feature currently applies to center directors and center staff members...

        if (empty($this->_id)) {
            throw new Exception("This user must be saved prior to calling setOrganization()");
        }

        if ($role != ROLE_ID_CENTER_DIRECTOR && $role != ROLE_ID_CENTER_STAFF) {
            throw new Exception("This user must be saved prior to calling setOrganization()");
        }

        $acl = Acls::getAclByName($role);
        if (null === $acl) {
            throw new Exception("Unable to retrieve acl for: $role");
        }

        $this->_pdo->execute(
            "DELETE FROM user_acl_group_by_parameters WHERE user_id = :user_id AND acl_id = :acl_id AND group_by_id IN (SELECT gb.group_by_id FROM group_bys gb WHERE gb.name = 'provider')",
            array(
                ':user_id' => $this->_id,
                ':acl_id' => $acl->getAclId()
            )
        );

        foreach ($organization_ids as $organization_id => $config) {
            $this->_pdo->execute(
                <<<SQL
INSERT INTO user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
SELECT inc.*
FROM (
   SELECT
      :user_id AS user_id,
      :acl_id AS acl_id,
      gb.group_by_id AS group_by_id,
      :value AS value
   FROM group_bys gb
   WHERE gb.name = 'provider'
) inc
LEFT JOIN user_acl_group_by_parameters cur
  ON cur.user_id = inc.user_id
  AND cur.acl_id = inc.acl_id
  AND cur.group_by_id = inc.group_by_id
  AND cur.value = inc.value
WHERE cur.user_acl_parameter_id IS NULL;
SQL
                ,
                array(
                    ':user_id' => $this->_id,
                    ':acl_id' => $acl->getAclId(),
                    ':value' => $organization_id
                )
            );
        }//foreach
    }//setOrganizations

    public function isCenterDirectorOfOrganization($organization_id)
    {
        $query = <<<SQL
SELECT COUNT(*) AS num_matches
FROM user_acl_group_by_parameters uagbp
JOIN group_bys gb ON uagbp.group_by_id = gb.group_by_id
JOIN acls a ON uagbp.acl_id = a.acl_id
WHERE
  a.name        = :acl_name      AND
  gb.name       = :group_by_name AND
  uagbp.user_id = :user_id       AND
  uagbp.value   = :organization_id;
SQL;

        $results = $this->_pdo->query(
            $query,
            array(
                ':user_id' => $this->_id,
                ':acl_name' => ROLE_ID_CENTER_DIRECTOR,
                ':group_by_name' => 'provider',
                ':organization_id' => $organization_id
            )
        );

        $matches = $results[0]['num_matches'];

        return ($matches != 0);

    }//isCenterDirectorOfOrganization

    /*
     *
     * @function getActiveOrganization
     *
     * @return int (corresponding to the active organization)
     *
     *
     */

    public function getActiveOrganization()
    {

        // This feature currently applies to center directors...

        if (empty($this->_id)) {
            throw new Exception("This user must be saved prior to calling getActiveOrganization()");
        }
        $query = <<<SQL
SELECT
  COALESCE(uagbp.value, o.id, po.id) as param_value
FROM Users u
  LEFT JOIN user_acl_group_by_parameters uagbp
    ON uagbp.user_id = u.id AND
    uagbp.group_by_id IN (
      SELECT gb.group_by_id FROM group_bys gb
      WHERE gb.name = 'provider'
    ) AND
    uagbp.value IN (
      SELECT o.id FROM modw.organization o
      JOIN modw.resourcefact rf ON o.id = rf.organization_id
    )
  LEFT JOIN modw.organization o
    ON o.id = u.organization_id AND
       o.id IN
       (SELECT DISTINCT rf.organization_id
        FROM modw.resourcefact rf)
  LEFT JOIN modw.person p ON p.id = u.person_id
  LEFT JOIN modw.organization po ON po.id = p.organization_id
WHERE u.id = :user_id;
SQL;

        $results = $this->_pdo->query($query, array(
            ':user_id' => $this->_id,
        ));

        return (count($results) > 0) ? $results[0]['param_value'] : '-1';

    }//getActiveOrganization


    /*
     *
     * @function getRoles
     *
     * @param string $flag ('formal' or 'informal')
     *
     * @return array
     *
     */

    public function getRoles($flag = 'informal')
    {

        if ($flag == 'informal') {
            $roles = array_reduce($this->_acls, function ($carry, Acl $item) {
                $carry[] = $item->getName();
                return $carry;
            }, array());
            return $roles;
        }

        if ($flag == 'formal') {
            $query = <<<SQL
SELECT
a.display,
a.name
FROM user_acls ua
JOIN acls a
ON a.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
SQL;

            $results = $this->_pdo->query($query, array(
                ':user_id' => $this->_id,
            ));

            $roles = array();

            foreach ($results as $roleSet) {

                $roles[$roleSet['display']] = $roleSet['name'];

            }

            return $roles;
        }

    }//getRoles

    // ---------------------------

    /*
     *
     * @function setRoles
     *
     * @param array $role_set
     *
     */

    public function setRoles($role_set)
    {
        $this->_roles = $role_set;
        // Make sure to also set the Acls
        $acls = array_reduce($role_set, function ($carry, $roleName) {
            $acl = Acls::getAclByName($roleName);
            if ($acl !== null) {
                $carry [] = $acl;
            }
            return $carry;
        }, array());
        $this->setAcls($acls);
    }


    /*
     *
     * @function getMostPrivilegedRole
     *
     * @return Acl
     *
     */

    public function getMostPrivilegedRole()
    {
        if (!isset($this->_mostPrivilegedAcl)) {
            $this->_mostPrivilegedAcl = Acls::getMostPrivilegedAcl($this);
        }

        return $this->_mostPrivilegedAcl;
    }//getMostPrivilegedRole

    /* @function getAllRoles
     *
     * Returns an array containing all roles that a user is assigned
     *
     * @param boolean $includePublicRole (Optional) If true, the roles returned
     *                                   will include the public role.
     *                                   (Defaults to false.)
     */
    function getAllRoles($includePublicRole = false)
    {
        $allroles = array();

        foreach ($this->enumAllAvailableRoles() as $availableRole) {
            $roleData = array_pad(explode(':', $availableRole['param_value']), 2, NULL);
            $allroles[] = $roleData[0];
        }

        if ($includePublicRole) {
            $allroles[] = ROLE_ID_PUBLIC;
        }

        return $allroles;
    }

    /*
     *
     * @function getUserID
     *
     * @return int
     *
     */

    public function getUserID()
    {
        return (empty($this->_id)) ? '0' : $this->_id;
    }

    /*
     *
     * @function getPersonID
     *
     * @param bool $default   (If FALSE *and* the user object being looked at corresponds to the logged in user, consider the 'assumed_person_id' and return it if it exists)
     *                        (If TRUE *and* the user object being looked at corresponds to the logged in user, return the stored person id)
     *
     * @return string
     *
     */

    public function getPersonID($default = FALSE)
    {

        // NOTE: RESTful services do not operate on the concept of a session, so we need to check for $_SESSION[..] entities using isset

        if (isset($_SESSION['xdUser']) && ($_SESSION['xdUser'] == $this->_id) && ($default == FALSE)) {

            // The user object pertains to the user logged in..

            if (isset($_SESSION['assumed_person_id'])) {
                return $_SESSION['assumed_person_id'];
            }

        }

        return (empty($this->_personID)) ? '0' : $this->_personID;

    }//getPersonID

    // ---------------------------

    /*
     *
     * @function setPersonID
     *
     * @param string $person_id
     *
     */

    public function setPersonID($person_id)
    {
        $this->_personID = $person_id;
    }

    // ---------------------------

    /*
     *
     * @function getCreationTimestamp
     *
     * @return string
     *
     */

    public function getCreationTimestamp()
    {
        return $this->_timeCreated;
    }

    // ---------------------------

    /*
     *
     * @function getPasswordLastUpdatedTimestamp
     *
     * @return string
     *
     */

    public function getPasswordLastUpdatedTimestamp()
    {
        return $this->_timePasswordUpdated;
    }

    // ---------------------------

    /*
     *
     * @function getUpdateTimestamp
     *
     * @return string
     *
     */

    public function getUpdateTimestamp()
    {
        return $this->_timeUpdated;
    }

    /**
     * @function _getFormalRoleName
     * (determines the formal description of a role based on its abbreviation)
     *
     * @param string $role_abbrev the role abbreviation to use when looking up the formal name.
     * @param bool   $pubDisplay  Determines whether or not to return the public roles `display`
     * property or it's `name` property. We default to true ( i.e. `display` ) as that is the
     * behavior that currently exists.
     *
     * @return string representing the formal role name if the abbreviation is recognized
     * @throws Exception
     */
    public static function _getFormalRoleName($role_abbrev, $pubDisplay = true)
    {
        $pdo = DB::factory('database');
        $pubColumn = $pubDisplay ? 'display' : 'name';

        $query = <<<SQL
SELECT CASE WHEN a.acl_id IS NULL
  THEN pub.description
       ELSE a.display END description
FROM (
       SELECT
         acl_id,
         $pubColumn AS description
       FROM acls
       WHERE name = :pub_abbrev
     ) pub
  LEFT JOIN acls a
    ON a.acl_id != pub.acl_id
       AND a.name = :abbrev;
SQL;

        $roleData = $pdo->query(
            $query,
            array(
                ':abbrev' => $role_abbrev,
                ':pub_abbrev' => ROLE_ID_PUBLIC
            )
        );

        return $roleData[0]['description'];

    }//_getFormalRoleName

    // --------------------------------

    /*
     *
     * @function _generateToken
     * (returns a unique random string used for authentication against the REST service)
     *
     * @return string
     *
     */

    private function _generateToken()
    {

        $characters = '0123456789aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ';

        $string = '';

        for ($p = 0; $p < 32; $p++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        $results = $this->_pdo->query("SELECT * FROM Users WHERE token=:token", array(
            ':token' => $string,
        ));

        if (count($results) == 0)
            return $string;
        else
            return $this->_generateToken();

    }//_generateToken

    /*
     * @function isSSOUser
     *
     * Determines whether the user is an XSEDE-oriented user
     *
     * @returns boolean
     *
     */

    public function isSSOUser()
    {

        return ($this->getUserType() == SSO_USER_TYPE);

    }

    /**
     * Retrieve the Acls for this user.
     *
     * @param bool $names defaults to `false`. If true, then the names of the
     *                    acls will be returned instead of the Acl objects themselves.
     * @return Acl[]|string[]
     */
    public function getAcls($names = false)
    {
        return (false === $names)
            ? $this->_acls
            : array_keys($this->_acls);
    } // getAcls

    /**
     * Retrieve an array of names for this user's currently assigned Acls.
     *
     * @return String[]
     */
    public function getAclNames()
    {
        return array_keys($this->_acls);
    }

    /**
     * Overwrite this users current set of acls with the provided ones.
     *
     * @param array[] $acls
     */
    public function setAcls(array $acls)
    {
        $this->_acls = $acls;
    } // setAcls

    /**
     * Add the provided acl to this users set of acls if they do not already
     * have it. If the overwrite parameter is provided as true then it will be
     * added ( or overwrite the existing acl ) regardless of whether or not the
     * user currently has it.
     *
     * @param Acl $acl
     * @param bool $overwrite
     */
    public function addAcl(Acl $acl, $overwrite = false)
    {
        if ( ( !array_key_exists($acl->getName(), $this->_acls) && !$overwrite ) ||
            $overwrite === true
        ) {
            $this->_acls[$acl->getName()] = $acl;
        }
    } // addAcl

    /**
     * Remove the provided acl from this users set of acls.
     *
     * @param Acl $acl
     */
    public function removeAcl(Acl $acl)
    {
        if (array_key_exists($acl->getName(), $this->_acls)) {
            unset($this->_acls[$acl->getName()]);
        }
    } // removeAcl

    /**
     * Determine whether or not this user has a relation to the provided Acl.
     * The acl
     *
     * @param Acl|string $acl
     * @param string $property the name of the getter to use when determining if
     * the user has the provided acl or not. Defaults to 'name'.
     *
     * @return bool true iff the acl is found in this users set of acls.
     * @throws Exception if the provided acl is anything but an Acl or string.
     */
    public function hasAcl($acl, $property = 'name')
    {
        $isAcl = $acl instanceof Acl;
        $isString = is_string($acl);
        $getter = 'get' . ucfirst($property);
        if (false === $isAcl && false === $isString) {
            $aclClass = get_class($acl);
            throw new Exception("Unknown acl type encountered. Expected Acl or string got $aclClass.");
        }
        $value = $isAcl ? $acl->$getter() : $acl;
        return array_key_exists($value, $this->_acls);
    } // hasAcl


    /**
     * Determine whether or not this user has a relation to all of the provided
     * Acls.
     *
     * @param Acl[]|string[] $acls an array of Acls or an array of strings
     * @param string $property the name of the getter to use when determining if
     * the user has the provided set of acls or not. Defaults to 'name'
     *
     * @return bool true iff all of the acls are found in this users set of acls
     * @throws Exception if any of the provided acls are not a string or Acl
     */
    public function hasAcls(array $acls, $property = 'name')
    {
        $total = 0;
        foreach ($acls as $acl) {
            $found = $this->hasAcl($acl, $property);
            $total += $found ? 1 : 0;
        }
        return $total === count($acls);
    } // hasAcls

    /**
     * Attempt to retrieve an XDUser instance based on the provided $username.
     *
     * @param string $username the identifier to use when attempting to retrieve
     * the XDUser instance.
     *
     * @return XDUser An instantiated XDUser instance.
     *
     * @throws Exception If the user cannot be found or if null is provided as
     * the username.
     **/
    public static function getUserByUserName($username)
    {
        if (null === $username) {
            throw new Exception('No username provided');
        }

        // Note: due to the complexity of getUserById ( and it being the sole
        // repository of user creation logic ) we just retrieve the userId and
        // feed that to the getUserById function.
        $query = <<<SQL
SELECT
  u.id
FROM Users u
WHERE u.username = :username
SQL;
        $db = DB::factory('database');
        $row = $db->query($query, array(':username' => $username));
        if (count($row) > 0) {
            $uid = $row[0]['id'];
            return self::getUserByID($uid);
        }
        throw new Exception("User \"$username\" not found");
    } // getUserByUserName

    /**
     * Attempt to make a relationship between the provided acl and organization
     * identifier. These relationships are generally used when a user needs to
     * have the data XDMoD is providing to them filtered by a particular
     * organization.
     *
     * @param string $aclName        the name of the acl that should have a
     *                               relationship created for it with the
     *                               provided organization.
     * @param string $organizationId the name of the organization
     * @throws Exception if this user has not been saved yet
     * @throws Exception if the provided acl cannot be found.
     */
    public function addAclOrganization($aclName, $organizationId)
    {
        if (empty($this->_id)) {
            throw new Exception("This user must be saved prior to calling " . __FUNCTION__ . ".");
        }

        // If they haven't provided an organizationId then we can't add an
        // acl relation to it.
        if (!isset($organizationId)) {
            return;
        }

        $acl = Acls::getAclByName($aclName);

        if ( null == $acl) {
            throw new Exception("Unable to retrieve acl for: $aclName");
        }


        $cleanUserAclGroupByParameters = <<<SQL
DELETE FROM user_acl_group_by_parameters
WHERE user_id = :user_id AND
      acl_id  = :acl_id
SQL;

        $this->_pdo->execute($cleanUserAclGroupByParameters, array(
            ':user_id' => $this->_id,
            ':acl_id'  => $acl->getAclId()
        ));

        $populateUserAclGroupByParameters = <<<SQL
INSERT INTO user_acl_group_by_parameters (user_id, acl_id, group_by_id, value)
SELECT inc.*
FROM (
   SELECT
      :user_id AS user_id,
      :acl_id AS acl_id,
      gb.group_by_id AS group_by_id,
      :value AS value
   FROM group_bys gb
   WHERE gb.name = 'provider'
) inc
LEFT JOIN user_acl_group_by_parameters cur
  ON cur.user_id = inc.user_id
  AND cur.acl_id = inc.acl_id
  AND cur.group_by_id = inc.group_by_id
  AND cur.value = inc.value
WHERE cur.user_acl_parameter_id IS NULL;
SQL;

        $this->_pdo->execute($populateUserAclGroupByParameters, array(
            ':user_id' => $this->_id,
            ':acl_id'  => $acl->getAclId(),
            ':value'   => $organizationId
        ));
    } // addAclOrganization

        /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $ignored = array(
            '_pdo', '_primary_role', '_publicUser', '_timeCreated','_timeUpdated',
            '_timePasswordUpdated', '_token', 'logger'
        );
        $reflection = new ReflectionClass($this);
        $results = array();
        $properties = $reflection->getProperties();
        foreach($properties as $property) {
            $name = $property->getName();
            if (!in_array($name, $ignored)) {
                $property->setAccessible(true);

                $value = $property->getValue($this);
                $results[$name] = $value;
            }
        }
        return $results;
    }

    /**
     * Retrieve the `organization_id` for this user. This corresponds with the value found in the
     * `moddb.Users.organization_id` column.
     *
     * @return mixed
     */
    public function getOrganizationID()
    {
        return $this->_organizationID;
    }

    /**
     * Set the `organization_id` for this user. This corresponds to the value found in the
     * `moddb.Users.organization_id` column.
     *
     * @param int $organizationID the id of the organization that this user should be associated
     *            with
     */
    public function setOrganizationID($organizationID)
    {
        $this->_organizationID = $organizationID;
    }

    /**
     * Generate an RID value from the provided $user. This value is intended to be used in
     * authenticating / authorizing a password reset. If an $expiration value is provided, that will
     * be used instead of generating one via the 'email_token_expiration' portal settings value.
     *
     * @param int|null    $expiration the date after which this rid is considered invalid.
     * @return string in the form "userId|expiration|hash"
     * @throws Exception If there are any missing configuration properties that this function relies
     * on. These include: email_token_expiration and application_secret.
     */
    public function generateRID($expiration = null)
    {
        $expiresIn = \xd_utilities\getConfiguration('general', 'email_token_expiration');
        $appSecret = \xd_utilities\getConfiguration('general', 'application_secret');

        $userId = $this->_id;
        $userSecret = $this->_password;

        if ($expiration === null) {
            // set default expiration time of 10 minutes if we don't get one from configuration
            $expiration = !empty($expiresIn) && is_numeric($expiresIn) ? time() + $expiresIn : time() + 600;
        }

        $hash = hash_hmac("sha256", "$userId $expiration $userSecret", $appSecret);

        return "$userId|$expiration|$hash";
    }

    /**
     * This function will determine whether or not the provided $rid is valid. This includes
     * checking if the expiration date has passed, that the username provided is found to exist, and
     * whether or not the information contained within RID has retained it's integrity (i.e. that
     * somebody hasn't tried to monkey with the username / expiration date ).
     *
     * @param string $rid the rid value to be validated.
     * @return array
     * @throws Exception if there is a problem decoding the RID or if the
     * email_token_expiration_format configuration property is not present.
     */
    public static function validateRID($rid)
    {
        $log = \CCR\Log::factory('xms.auth.rid', array(
            'console' => false,
            'db' => true,
            'mail' => false,
            'file' => LOG_DIR . '/xms-auth-rid.log',
            'fileLogLevel' => Log::DEBUG
        ));

        $results = array(
            'status' => INVALID,
            'user_first_name' => 'INVALID',
            'user_id' => INVALID
        );

        list($userId, $expiration, $hash) = explode('|', $rid);

        $now = time();

        if ($now >= $expiration) {
            $expirationDate = date('Y-m-d H:i:s', $expiration);
            $log->debug("RID Token expired for: $userId | expired: $expirationDate");
            return $results;
        }

        try {
            $user = XDUser::getUserByID($userId);
            if ($user === null) {
                return $results;
            }
            $expected = explode('|', $user->generateRID($expiration))[2];
            $valid = hash_equals($expected, $hash);

            $results['status'] = $valid ? VALID : INVALID;
            $results['user_first_name'] = $valid ? $user->getFirstName() : 'INVALID';
            $results['user_id'] = $valid ? $user->getUserID() : INVALID;
        } catch (Exception $e) {
            // If there was an exception then it was because we couldn't find a user by that username
            // so log the error and return the default information.
            $expirationDate = date('Y-m-d H:i:s', $expiration );
            $log->debug("Error occurred while validating RID for User: $userId, Expiration: $expirationDate");
        }

        return $results;
    }

    /**
     * Executes any actions that need to be conducted immediately after a user is logged into XDMoD.
     *
     * @throws Exception if there is a problem executing any of the required post logged in steps.
     */
    public function postLogin() {
        if (!$this->isSticky()) {
            $this->updatePerson();
            $this->synchronizeOrganization();
        }
        $this->currentToken = XDSessionManager::recordLogin($this);
    }

    /**
     * Attempts to synchronize this users organization. This will ensure that if the organization
     * assigned to the user is not the same as the organization that should be assigned to the user
     * ( i.e. the organization assigned to this users person has been updated ). They have their
     * organization updated. If they have been associated with a 'center' related acl, center
     * director or center staff, then they are to have these acls removed and an email notice sent
     * to the admin / user notifying them that additional steps will need to be taken before their
     * former level of access is restored.
     *
     * @throws Exception
     */
    public function synchronizeOrganization()
    {
        // This is pulled from the moddb.Users.organization_id column for this user record.
        $actualOrganization = $this->getOrganizationID();

        // Retrieve the organization associated with this users Person. This is the value we expect
        // the user's organization to match.
        $expectedOrganization = Organizations::getOrganizationIdForPerson($this->getPersonID());

        // If we have ssoAttrs available and this user's person's organization is 'Unknown' ( -1 ).
        // Then go ahead and lookup the organization value from sso.
        if ($expectedOrganization == -1 && count($this->ssoAttrs) > 0) {
            $expectedOrganization = Organizations::getIdByName($this->ssoAttrs['organization'][0]);
        }

        // If these don't match then the user's organization has been updated. Steps need to be taken.
        if ($actualOrganization !== $expectedOrganization) {
            $originalAcls = $this->getAcls(true);

            // if the user is currently assigned an acl that interacts with XDMoD's centers ( i.e.
            // center director, center staff etc. ) then we need to remove these acls and notify
            // the admins that additional setup may be required for this user.
            if (count(array_intersect($originalAcls, self::$CENTER_ACLS)) > 0) {
                $otherAcls = array_values(array_diff($originalAcls, self::$CENTER_ACLS));

                // Make sure that they at least have 'usr'
                if (empty($otherAcls)) {
                    $otherAcls = array('usr');
                }

                // Now we need to make sure that the user is only assigned their non-center acls so
                // clear any existing acls they have.
                $this->setAcls(array());

                // Update the user w/ their new set of acls.
                foreach($otherAcls as $aclName) {
                    $acl = Acls::getAclByName($aclName);
                    $this->addAcl($acl);
                }

                // Retrieving the names for display purposes.
                $userOrganizationName = Organizations::getNameById($actualOrganization);
                $currentOrganizationName = Organizations::getNameById($expectedOrganization);

                // Notify the XDMoD Admin that a user has had their privileges altered due to an
                // organization change so that they can take any further steps that may be required.
                MailWrapper::sendMail(
                    array(
                        'subject' => 'XDMoD User: Organization Update',
                        'body' => sprintf(
                            self::ADMIN_NOTIFICATION_EMAIL,
                            $this->getFormalName(),
                            $this->getUsername(),
                            $this->getEmailAddress(),
                            $userOrganizationName,
                            $currentOrganizationName,
                            json_encode($originalAcls),
                            json_encode($otherAcls)
                        ),
                        'toAddress' => \xd_utilities\getConfiguration('general', 'tech_support_recipient'),
                    )
                );

                $contactAddress = \xd_utilities\getConfiguration('general', 'contact_page_recipient');

                // Notify the user that there was an organization change detected.
                MailWrapper::sendMail(
                    array(
                        'subject' => 'XDMoD User: Organization Update',
                        'body' => sprintf(
                            self::USER_NOTIFICATION_EMAIL,
                            $this->getFormalName(),
                            $userOrganizationName,
                            $currentOrganizationName,
                            $userOrganizationName,
                            $contactAddress,
                            MailWrapper::getMaintainerSignature()
                        ),
                        'toAddress' => $this->getEmailAddress(),
                        'replyAddress' => $contactAddress
                    )
                );
            }

            // Update / save the user with their new organization
            $this->setOrganizationId($expectedOrganization);
            $this->saveUser();
        }
    }

    /**
     * Updates this Users Person / Organization if they are not currently assigned to the Unknown User && they there are
     * ssoAttrs available ( logged in via SSO ). Also, if the person identified via ssoAttrs is not the Unknown User &&
     * is different than the users curent Person.
     *
     */
    public function updatePerson()
    {
        $currentPersonId = $this->getPersonID();
        $hasSSO = count($this->ssoAttrs) > 0;

        if ($currentPersonId == -1 && $hasSSO) {
            $username = $this->ssoAttrs['username'][0];
            $systemUserName = isset($this->ssoAttrs['system_username']) ? $this->ssoAttrs['system_username'][0] : $username;
            $expectedPersonId = \DataWarehouse::getPersonIdFromPII($systemUserName, null);

            // As long as the identified person is not Unknown and it is different than our current Person Id
            // go ahead and update this user with the new person & that person's organization.
            if ($expectedPersonId != -1 && $currentPersonId != $expectedPersonId) {
                $organizationId = Organizations::getOrganizationIdForPerson($expectedPersonId);
                $this->setPersonID($expectedPersonId);
                $this->setOrganizationID($organizationId);

                $this->saveUser();
            }
        }
    }

    public function setSSOAttrs($ssoAttrs)
    {
        $this->ssoAttrs = array_reduce(
            array_intersect(self::$INCLUDE_SSO_ATTRS, array_keys($ssoAttrs)),
            function ($carry, $key) use ($ssoAttrs) {
                $carry[$key] = $ssoAttrs[$key];
                return $carry;
            },
            array()
        );
    }

    /**
     * Retrieve this users current session token. This will only be populated if the user has had
     * its `postLogin` function called.
     *
     * @return string
     */
    public function getSessionToken()
    {
        return $this->currentToken;
    }

    /**
     * Set's the value of this user's `sticky` bit.
     *
     * @param $sticky
     */
    public function setSticky($sticky)
    {
        $this->sticky = $sticky;
    }

    /**
     * Return's the value of this user's `sticky` bit. If true, then this user's person and or
     * organization has been manually overridden, do not update unless via the admin interface.
     *
     * @return bool
     */
    public function isSticky()
    {
        return $this->sticky;
    }

    /**
     * Retrieves the resources that this user has access to. Specifically, it retrieves the resources
     * that are associated with this User's `organization_id`.
     *
     * **NOTE:** This function does not utilize the standard method of retrieving / filtering data,
     * i.e. via a code path that ends up utilizing `Query.php` because we do not currently restrict
     * access to resources. Also, the methods of filtering used in conjunction with `Query.php` are
     * either hard coded in the `GroupBy` classes, or are indirectly setup via the `modw_filters`
     * tables && the roles.json::acl::dimensions property ( via the `FilterListBuilder` class ).
     *
     * @param array $resourceNames [optional|default array()] an array of resourcefact.code values
     *                             that should optionally further constrain the resources returned.
     * @return integer[] an array of the resourcefact.id values
     *
     * @throws Exception if there is a problem connecting to / querying the database.
     */
    public function getResources($resourceNames = array())
    {
        $db = DB::factory('database');

        $query = <<<SQL
        SELECT rf.id,
              replace(rf.code, '-', ' ') as name,
              replace(rf.code, '-', ' ') as short_name
        FROM modw.resourcefact rf
        WHERE   rf.organization_id =  :organization_id
SQL;
        $params = array(':organization_id' => $this->getOrganizationID());

        // If we have resource names then update the query / params accordingly
        if (count($resourceNames) > 0) {
            $query .= "AND rf.code IN (:resource_codes)";

            $handle = $db->handle();
            $resourceNames = array_map(
                function ($value) use ($handle) {
                    return $handle->quote($value);
                },
                $resourceNames
            );
            $params[':resource_codes'] = implode(
                ',',
                $resourceNames
            );
        } // if (count($resourceNames) > 0) {

        return $db->query($query, $params);
    } // public function getResources($resourceNames = array())
}//XDUser
