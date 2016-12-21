<?php

require_once dirname(__FILE__).'/../configuration/linker.php';

use CCR\DB;
use User\Acl;
use User\Asset;
use User\iAcl;
use User\iAsset;

/**
 * XDMoD Portal User
 *
 * @Class XDUser
 */
class XDUser {

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
   private $_primary_role;             // Instance of class \User\aRole
   private $_active_role;              // Instance of class \User\aRole

   private $_field_of_science = 0;

   private $_organizationID;
   private $_personID;

   private $_user_type = 0;

   private $_update_token = false;
   private $_token;

   private $_cachedActiveRole;

    /**
     * @var iAsset[]
     */
   private $_assets;

    /**
     * @var iAcl[]
     */
   private $_acls;

   const PUBLIC_USER = 1;
   const INTERNAL_USER = 2;

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
   * @param string $primary_role  <--- reference to object returned from \User\aRole::factory(...)
   *
   */

   function __construct($username = NULL, $password = NULL, $email_address = NO_EMAIL_ADDRESS_SET,
                        $first_name = NULL, $middle_name = NULL, $last_name = NULL,
                        $role_set = array(ROLE_ID_USER), $primary_role = ROLE_ID_USER, $organization_id = NULL, $person_id = NULL
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

      $this->_roles = $role_set;

      // Role Checking ====================

      if (count($this->_roles) == 0) {
         throw new Exception("At least one role must be associated with this user");
      }

      if ($this->_getFormalRoleName($primary_role) == NULL) {
         throw new Exception("A valid primary role must be specified");
      }

      foreach($this->_roles as $role) {

         if ($this->_getFormalRoleName($role) == NULL) {
            throw new Exception("Unrecognized role $role");
         }

      }

      if (!in_array($primary_role, $this->_roles)) {
         throw new Exception("Primary role $primary_role must be a member of the set of roles assigned to this user");
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

      // =================================

      $primary_role_name = $this->_getFormalRoleName($primary_role);

      // These roles cannot be used immediately after constructing a new XDUser (since a user id has not been defined at this point).
      // If you are explicitly calling 'new XDUser(...)', saveUser() must be called on the newly created XDUser object before accessing
      // these roles using getPrimaryRole() and getActiveRole()

      $this->_primary_role = \User\aRole::factory($primary_role_name);
      $this->_active_role = \User\aRole::factory($primary_role_name);

   }//construct

   // ---------------------------

  /*
   *
   * @function reloadUser  (Retrieves updated information for the user from the database)
   *
   */

   public function reloadUser() {

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

   public function getProfile() {

      if(!isset($this->_id)) {
         throw new \Exception('This user must be saved first.');
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

   public static function userExistsWithUsername($username) {

      $pdo = DB::factory('database');

      $userCheck = $pdo->query("SELECT id FROM Users WHERE username=:username", array(
         ':username' => $username,
      ));

      if (count($userCheck) > 0) {
         return $userCheck[0]['id'];
      }
      else {
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

   public static function userExistsWithEmailAddress($email_address, $include_exception_addresses = FALSE) {

      if ($email_address == NO_EMAIL_ADDRESS_SET){

         //Empty values for e-mail address are allowed
         return INVALID;

      }

      $pdo = DB::factory('database');

      $user_check_query = "SELECT id FROM Users WHERE email_address=:email_address AND user_type != :user_type";

      if ($include_exception_addresses == FALSE){

         // If a user is attempting to reset their password based on their e-mail address, it is important that
         // the e-mail address does NOT map to more than one account (we cannot deal with multiple users mapped
         // to a common e-mail address).  $include_exception_addresses is set to TRUE only in the pass_reset
         // controller of user_auth -- which would not append the following to the SELECT query:

         $user_check_query .= " AND email_address NOT IN (SELECT email_address FROM ExceptionEmailAddresses)";

      }

      // We don't want to acknowledge XSEDE-derived accounts...

      $userCheck = $pdo->query(
                                 $user_check_query,
                                 array(
                                    'email_address' => $email_address,
                                    'user_type' => XSEDE_USER_TYPE
                                 )
                              );

      if (count($userCheck) == 1) {
         return $userCheck[0]['id'];
      }
      elseif (count($userCheck) > 1) {

         // E-mail address maps to more than one account (present in ExceptionEmailAddresses table)
         return AMBIGUOUS;

      }
      else {

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

   public function getFieldOfScience() {

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

   public function setFieldOfScience($field_of_science) {

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

   public static function getUserByToken ($token) {

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

   /*
    *
    * @function getPublicUser
    *
    *
    */

    public static function getPublicUser() {

      $user = new self (
             'Public User',            // Username
             NULL,                     // Password
             NO_EMAIL_ADDRESS_SET,     // E-Mail Address
             'Public',                 // First Name
             '',                       // Middle Name
             'User',                   // Last Name
             array(ROLE_ID_PUBLIC),    // Role Set
             ROLE_ID_PUBLIC,           // Primary Role
             NULL,                     // Organization ID
             NULL                      // Person ID
      );

      //$user->setActiveRole(ROLE_ID_PUBLIC);
      //$user->setPrimaryRole(ROLE_ID_PUBLIC);

      return $user;

    }//getPublicUser

   // ---------------------------

    /**
     * Check if this user is a public user.
     *
     * @return boolean If this user is a public user, true. Otherwise, false.
     */
    public function isPublicUser() {
        return $this->getPrimaryRole()->getIdentifier() === ROLE_ID_PUBLIC;
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

   public static function getUserByID($uid, &$targetInstance = NULL) {

      $pdo = DB::factory('database');

      $userCheck = $pdo->query("
         SELECT username, password, email_address, first_name, middle_name, last_name,
         time_created, time_last_updated, password_last_updated, account_is_active, organization_id, person_id, field_of_science, token, user_type
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

      $user->_id = $uid;

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
      $user->_user_type = (int) $userCheck[0]['user_type'];

      $user->_roles = array();

      $rolesResult = $pdo->query("
         SELECT 
            r.abbrev,
            r.description,
            IF(ur.is_primary, COALESCE(urp.is_primary, ur.is_primary), ur.is_primary) AS is_primary,
            IF(ur.is_active, COALESCE(urp.is_active, ur.is_active), ur.is_active) AS is_active,
            urp.param_value
         FROM
            UserRoles AS ur
            JOIN Roles AS r ON ur.role_id = r.role_id
            LEFT JOIN UserRoleParameters AS urp ON ur.user_id = urp.user_id AND ur.role_id = urp.role_id
         WHERE ur.user_id = :user_id
      ", array(
         ':user_id' => $user->_id,
      ));

      foreach($rolesResult as $roleSet) {

         if (!in_array($roleSet['abbrev'], $user->_roles)) {
            $user->_roles[] = $roleSet['abbrev'];
         }

         if ($roleSet['is_primary'] == '1') {
            $user->_primary_role = \User\aRole::factory($roleSet['description']);
            $user->_primary_role->configure($user, $roleSet['param_value']);
         }

         if ($roleSet['is_active'] == '1') {
            $user->_active_role = \User\aRole::factory($roleSet['description']);
            $user->_active_role->configure($user, $roleSet['param_value']);
         }

      }//foreach

       // BEGIN: ACL population
       $query = <<<SQL
SELECT a.*
FROM user_acls ua
  JOIN acls a
    ON a.acl_id = ua.acl_id
WHERE ua.user_id = :user_id
      AND a.enabled = TRUE
SQL;
      $results = $pdo->query($query,
          array(
              'user_id' => $uid
          ));


      $acls = array_reduce($results, function($carry, $item) {
          $carry []= new Acl($item);
      }, array());

      $user->setAcls($acls);
      // END: ACL population

      // BEGIN: Asset Population
       $query = <<<SQL
SELECT DISTINCT
  ast.*
FROM acl_assets aa
  JOIN acls a
    ON a.acl_id = aa.acl_id
  JOIN assets ast
    ON ast.asset_id = aa.asset_id
  JOIN user_acls AS ua
    ON aa.acl_id = ua.acl_id
WHERE
  ua.user_id = :user_id
AND a.enabled = TRUE
AND ast.enabled = TRUE;
SQL;
       $results = $pdo->query($query, array('user_id' => $uid));
       $assets = array_reduce($results, function($carry, $item) {
           $carry []= new Asset($item);
       }, array());
       $user->setAssets($assets);

      // END:   Asset Population

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

   public function setPassword($raw_password) {

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

   public function getUsername() {

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

   public function isDeveloper() {

      return (in_array(ROLE_ID_DEVELOPER, $this->getRoles()));

   }//isDeveloper

   // ---------------------------

   /*
    *
    * @function isManager
    *
    * @return boolean
    *
    */

   public function isManager() {

      return (in_array(ROLE_ID_MANAGER, $this->getRoles()));

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

   public static function isPrincipalInvestigator($person_id) {

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

   public static function isCampusChampion($person_id) {

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

      }
      else {

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

   public static function authenticate($uname, $pass) {

      if(strlen($uname) == 0 || strlen($pass) == 0) {
         return NULL;
      }

      $pdo = DB::factory('database');

      $userCheck = $pdo->query("SELECT id
        FROM Users
        WHERE username=:username
        AND password=MD5(:password)
        AND user_type NOT IN (:xsede_user_type, :federated_user_type)",
        array(
          'username' => $uname,
          'password' => $pass,
          'xsede_user_type' => XSEDE_USER_TYPE,
          'federated_user_type' => FEDERATED_USER_TYPE
      ));
      if (count($userCheck) == 0) {
        return NULL;
      }
      return self::getUserByID($userCheck[0]['id']);

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

   public static function isAuthenticated($user){
      return ($user != NULL);
   }

   // ---------------------------

   /*
    *
    * @function issueNewToken
    *
    */

   public function issueNewToken() {
      $this->_update_token = true;
   }

   // ---------------------------
    /**
     * Returns a parameterized Update query for the 'User' table. If the
     * $updateToken parameter is set then it includes the 'token'
     * and the 'token_expiration' fields.
     *
     * @param bool $updateToken     signifies whether or not to include the 'token'
     *             related columns in the return value.

     * @param bool $includePassword signifies whether or not to include the
     *             'password' related columns in the return value.
     *
     * @return string a parameterized query for the 'User' table
     */
    public function getUpdateQuery( $updateToken = false, $includePassword = false )
    {
        $result = 'UPDATE moddb.Users SET username = :username,  email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, user_type = :user_type WHERE id = :id';
        if ( $updateToken && $includePassword ) {
            $result = 'UPDATE moddb.Users SET username = :username, password = :password, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, token = :token, user_type = :user_type, password_last_updated = :password_last_updated WHERE id = :id';
        } else if ( !$updateToken && $includePassword ) {
            $result = 'UPDATE moddb.Users SET username = :username, password = :password, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, user_type = :user_type, password_last_updated = :password_last_updated WHERE id = :id';
        } else if ( $updateToken && !$includePassword ) {
            $result = 'UPDATE moddb.Users SET username = :usernam, email_address = :email_address, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, account_is_active = :account_is_active, person_id = :person_id, organization_id = :organization_id, field_of_science = :field_of_science, token = :token, user_type = :user_type WHERE id = :id';
        }
        return $result;
    }

   /**
    * Returns a parameterized Insert query for the 'User' table. If the
    * $updateToken parameter is set then it includes the 'token'
    * and the 'token_expiration' fields.
    *
    * @param bool $updateToken     signifies whether or not to include the 'token'
    *             related columns in the return value.
    * @param bool $includePassword signifies whether or not to include the
    *             'password' related columns in the return value.
    *
    * @return string a parameterized query for the 'User' table
    */
   public function getInsertQuery( $updateToken = false, $includePassword = false )
   {
        $result = 'INSERT INTO moddb.Users (username, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, user_type) VALUES (:username, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :user_type)';
        if ( $updateToken && $includePassword ) {
            $result = 'INSERT INTO moddb.Users (username, password, password_last_updated, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, token, user_type) VALUES (:username, :password, :password_last_updated, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :token, :user_type)';
        } else if ( !$updateToken && $includePassword ) {
            $result = 'INSERT INTO moddb.Users (username, password, password_last_updated,  email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, user_type) VALUES (:username, :password, :password_last_updated, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :user_type)';
        } else if ( $updateToken && !$includePassword ) {
            $result = 'INSERT INTO moddb.Users (username, email_address, first_name, middle_name, last_name, account_is_active, person_id, organization_id, field_of_science, token, user_type) VALUES (:username, :email_address, :first_name, :middle_name, :last_name, :account_is_active, :person_id, :organization_id, :field_of_science, :token, :user_type)';
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
    public function arrayToString($array = array() )
    {
        $result = 'Keys [ ';
        $result .= implode(', ', array_keys($array)) .']';
        $result .= 'Values [ ';
        $result .= implode(', ', array_values($array)) .']';
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
        if ($this->_active_role->getIdentifier() == ROLE_ID_PUBLIC) {
            throw new \Exception('The public role user cannot be saved.');
        }

        if ($this->_user_type == 0) {
            throw new \Exception('The user must have a valid user type.');
        }

        // Retrieve the userId (if any) for the email associated with this User
        // object.
        $id_of_user_holding_email_address = self::userExistsWithEmailAddress($this->_email);

        // A common e-mail address CAN be shared among multiple XSEDE accounts...
        // Each XDMoD (local) account must have a distinct e-mail address (unless that e-mail address is present in moddb.ExceptionEmailAddresses)

        // The second condition is in place to account for the case where a new XSEDE user is being created (and is not currently in the XDMoD DB)
        if (($id_of_user_holding_email_address != INVALID) && ($this->getUserType() != XSEDE_USER_TYPE)) {

            if (!isset($this->_id)) {
                // This user has no record in the database (never saved).  If $id_of_user_holding_email_address
                // holds a valid id, then an already saved user has the e-mail address.

                throw new Exception("An XDMoD user with e-mail address {$this->_email} exists");
            }
            else {

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
        if ( $forUpdate  ) {
            $update_data['id'] = $this->_id;
        }
        $update_data['username'] = $this->_username;
        $includePassword = strlen($this->_password) <= CHARLIM_PASSWORD ;
        if ($includePassword) {
          if($this->_password == "" || is_null($this->_password))
          {
            $update_data['password'] =  NULL;
          }
          else {
            $update_data['password'] =  md5($this->_password);
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
        /* END: Query Data Population */
        try {
            /* BEGIN: Construct the parameterized query */
            $query = $forUpdate
                ? $this->getUpdateQuery($this->_update_token, $includePassword)
                : $this->getInsertQuery($this->_update_token, $includePassword);
            /* END: Construct the parameterized query */

            /* BEGIN: Execute the query */
            if ($forUpdate) {
                /* $rowCount = */$this->_pdo->execute($query, $update_data);
            } else {
                // NOTE: There may be a better way to do this (atomicity issue) ?
                $new_user_id = $this->_pdo->insert($query, $update_data);
                // New User Creation -- assign the new user id to the associated roles
                $this->_id = $new_user_id;
            }
        } catch (\Exception $e) {
              throw new Exception("Exception occured while inserting / updating. UpdateToken: [{$this->_update_token}] Query: [$query] data: [{$this->arrayToString($update_data)}]",null, $e);
        }
        /* END: Execute the query */

        /* BEGIN: Update Token Information */
        if ($this->_update_token) {
            // Set token to expire in 30 days from now...
            $this->_pdo->execute(
                'UPDATE Users SET token_expiration=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id=:id',
                array( 'id' => $this->_id )
            );
            $this->_update_token = false;
        }
        /* END: Update Token Information */



        /* BEGIN: ACL data processing */

        // REMOVE: existing user -> acl relations
        $this->_pdo->execute(
            'DELETE FROM user_acls WHERE user_id = :user_id',
            array('user_id', $this->_id)
        );

        // ADD: current user -> acl relations
        foreach($this->_acls as $acl) {
            $this->_pdo->execute(
                'INSERT INTO user_acls(user_id, acl_id) VALUES(:user_id, :acl_id)',
                array(
                    'user_id' => $this->_id,
                    'acl_id' => $acl->getAclId()
                )
            );
        }
        /* END:   ACL data processing */

        /* BEGIN: UserRole Updating */
        // Rebuild roles data for user --------------
        $this->_pdo->execute(
            'DELETE FROM UserRoles WHERE user_id=:id',
            array('id' => $this->_id)
        );

        foreach ($this->_roles as $role) {
            $roleId = $this->_getRoleID($role);
            $this->_pdo->execute(
                "INSERT INTO UserRoles VALUES(:id, :roleId, '0', '0')",
                array('id' => $this->_id,
                      'roleId' => $roleId)
            );
        }
        $primaryRoleId = $this->_getRoleID($this->_primary_role->getIdentifier());
        $this->_pdo->execute(
            "UPDATE UserRoles SET is_primary='1' WHERE user_id=:id AND role_id=:roleId",
            array('id' => $this->_id, 'roleId' => $primaryRoleId )
        );

        // If the updater (e.g. Manager) has pulled out the (recently) active role for this user, reassign the active role to the primary role.

        $active_role_id  = ( in_array($this->_active_role->getIdentifier(), $this->_roles) ) ?
            $this->_getRoleID($this->_active_role->getIdentifier()) :
            $primaryRoleId;


        $this->_pdo->execute(
            "UPDATE UserRoles SET is_active='1' WHERE user_id=:id AND role_id=:roleId",
            array('id' => $this->_id, 'roleId' => $active_role_id)
        );
        /* END: UserRole Updating */

        /* BEGIN: Configure Primary and Active Roles */
        $this->_primary_role->configure($this);
        $this->_active_role->configure($this);
        /* END: Configure Primary and Active Roles */


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

   public function getLastLoginTimestamp() {

      $results = $this->_pdo->query("SELECT init_time FROM SessionManager WHERE user_id=:user_id ORDER BY init_time DESC LIMIT 1", array(
         ':user_id' => $this->_id,
      ));

      if (count($results) == 0) { return "Never logged in"; }

      $init_time = $results[0]['init_time'];

      $time_frags = explode('.', $init_time);

      return date('m/d/Y, g:i:s A', $time_frags[0]);

   }//getLastLoginTimestamp

   // ---------------------------

   /*
    *
    * @function getToken
    *
    * @return string
    *
    */

   public function getToken() {

      if ($this->_active_role->getIdentifier() == ROLE_ID_PUBLIC) {
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

   public function getTokenExpiration() {

      if ($this->_active_role->getIdentifier() == ROLE_ID_PUBLIC) {
         return '';
      }

      $tokenResults = $this->_pdo->query("SELECT token_expiration FROM Users WHERE id=:id", array(
         ':id' => $this->_id,
      ));

      return $tokenResults[0]['token_expiration'];

   }//getTokenExpiration

   // ---------------------------

   /*
    *
    * @function _getRoleID
    *
    * @param string $role_abbrev
    *
    * @return int
    *
    */

   private function _getRoleID($role_abbrev) {

      $roleResults = $this->_pdo->query("SELECT role_id FROM Roles WHERE abbrev=:abbrev", array(
         ':abbrev' => $role_abbrev,
      ));

      return $roleResults[0]['role_id'];

   }//_getRoleID

   // ---------------------------

   /*
    *
    * @function removeUser
    *
    */

   public function removeUser() {

      if ($this->_active_role->getIdentifier() == ROLE_ID_PUBLIC) {
         throw new \Exception('Cannot remove public user');
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

      $this->_pdo->execute("DELETE FROM UserRoleParameters WHERE user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));
      $this->_pdo->execute("DELETE FROM UserRoles WHERE user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));

      // Reset any associations to dependent users
      $this->_pdo->execute("UPDATE UserRoleParameters SET promoter='-1' WHERE promoter=:promoter", array(
         ':promoter' => $this->_id,
      ));

      $this->_pdo->execute("DELETE FROM Users WHERE id=:id", array(
         ':id' => $this->_id,
      ));

      unset($this);

   }//removeUser

   // ---------------------------

   /*
    *
    * @function getUserType;
    *
    * @return int (maps to one of the TYPE_* class constants at the top of this file)
    *
    */

   public function getUserType() {
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

   public function setUserType($userType) {
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

   public function getAccountStatus() {
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

   public function setAccountStatus($status) {
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

   public function getEmailAddress() {
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

   public function setEmailAddress($email_address) {
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

   public function getFormalName($includeMiddleName = false) {
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

   public function getFirstName() {
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

   public function setFirstName($firstName) {
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

   public function getLastName() {
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

   public function setLastName($lastName) {
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

   public function enumAllAvailableRoles() {

      if (empty($this->_id)) {

         // It is likely that the public user ended up here
         return array();

      }

      // Program Officer

      $role_query_1 = "SELECT r.description, r.abbrev AS param_value, urp.is_primary, urp.is_active " .
                      "FROM moddb.UserRoles AS urp, moddb.Roles AS r " .
                      "WHERE r.role_id = urp.role_id AND user_id=:user_id " .
                      "AND r.description = 'Program Officer'";
      $role_query_1_params = array(
         ':user_id' => $this->_id,
      );

      // Center Director and Center Staff

      $role_query_2 = "SELECT CONCAT(r.description, ' - ', o.abbrev) AS description, CONCAT(r.abbrev, ':', urp.param_value) AS param_value, urp.is_primary, urp.is_active " .
                      "FROM moddb.UserRoleParameters AS urp, moddb.Roles AS r, modw.organization AS o " .
                      "WHERE urp.param_value = o.id AND r.role_id = urp.role_id AND urp.user_id=:user_id AND r.description != 'Campus Champion'" .
                      "ORDER BY r.description, o.abbrev";
      $role_query_2_params = array(
         ':user_id' => $this->_id,
      );

      // Campus Champion

      $role_query_3 = "SELECT CONCAT(r.description, ' - ', o.name) AS description, CONCAT(r.abbrev, ':', urp.param_value) AS param_value, urp.is_primary, ur.is_active " .
                      "FROM moddb.UserRoleParameters AS urp, moddb.UserRoles AS ur, moddb.Roles AS r, modw.organization AS o " .
                      "WHERE urp.param_value = o.id AND ur.role_id = r.role_id AND ur.user_id =:ur_user_id AND r.role_id = urp.role_id " .
                      "AND urp.user_id=:urp_user_id AND r.description = 'Campus Champion'" .
                      "ORDER BY r.description, o.abbrev";
      $role_query_3_params = array(
         ':ur_user_id' => $this->_id,
         ':urp_user_id' => $this->_id,
      );

       // Principal Investigator

      $role_query_4 = "SELECT r.description, r.abbrev AS param_value, urp.is_primary, urp.is_active " .
                      "FROM moddb.UserRoles AS urp, moddb.Roles AS r " .
                      "WHERE r.role_id = urp.role_id AND user_id=:user_id " .
                      "AND r.description = 'Principal Investigator'";
      $role_query_4_params = array(
         ':user_id' => $this->_id,
      );

      // User

      $role_query_5 = "SELECT r.description, r.abbrev AS param_value, urp.is_primary, urp.is_active " .
                      "FROM moddb.UserRoles AS urp, moddb.Roles AS r " .
                      "WHERE r.role_id = urp.role_id AND user_id=:user_id " .
                      "AND r.description = 'User'";
      $role_query_5_params = array(
         ':user_id' => $this->_id,
      );

      $available_roles = array_merge(
         $this->_pdo->query($role_query_1, $role_query_1_params),
         $this->_pdo->query($role_query_2, $role_query_2_params),
         $this->_pdo->query($role_query_3, $role_query_3_params),
         $this->_pdo->query($role_query_4, $role_query_4_params),
         $this->_pdo->query($role_query_5, $role_query_5_params)
      );

      return $available_roles;

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

   public function setInstitution($institution_id, $is_primary = false) {

      // This feature currently applies to campus champions...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling setInstitution()");
      }

      $primary_flag = $is_primary ? 1 : 0;

      $cleanupStatement = "DELETE FROM UserRoleParameters " .
                          "WHERE user_id=:user_id AND role_id=6 AND param_name='institution'";

      $insertStatement = "INSERT INTO UserRoleParameters " .
                         "(user_id, role_id, param_name, param_op, param_value, is_primary, is_active, promoter) " .
                         "VALUES (:user_id, 6, 'institution', '=', :param_value, :is_primary, 1, -1)";

      $this->_pdo->execute($cleanupStatement, array(
         ':user_id' => $this->_id,
      ));
      $this->_pdo->execute($insertStatement, array(
         ':user_id' => $this->_id,
         ':param_value' => $institution_id,
         ':is_primary' => $primary_flag,
      ));

   }//setInstitution

   // ---------------------------

   /*
    *
    * @function disassociateWithOrganizations
    *
    * @param int $organization_id
    *
    *
    */

   public function disassociateWithInstitution() {

      // This feature currently applies to campus champions...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling disassociateWithInstitution()");
      }

      $cleanupStatement = "DELETE FROM UserRoleParameters " .
                          "WHERE user_id=:user_id AND role_id=6 AND param_name='institution'";

      $this->_pdo->execute($cleanupStatement, array(
         ':user_id' => $this->_id,
      ));

   }//disassociateWithInstitution

   // ---------------------------

   /*
    *
    * @function getActiveRoleID
    *
    * @returns string (the id of the active role) (see ROLES section in constants.php)
    *
    */

   public function getActiveRoleID() {

      return $this->_active_role->getIdentifier();

   }//getActiveRoleID

   // ---------------------------

   private function _getActiveProvider($role_id) {

      $active_provider = $this->_pdo->query("SELECT param_value FROM UserRoleParameters " .
                                            "WHERE user_id=:user_id AND role_id=:role_id AND param_name='provider' AND is_active=1", array(
         ':user_id' => $this->_id,
         ':role_id' => $role_id,
      ));

      if (count($active_provider) > 0) {
         return $active_provider[0]['param_value'];
      }
      else {
         return NULL;
      }

   }//_getActiveProvider

   public function getActiveRoleSettings() {

      $mainRole = $this->_pdo->query("SELECT r.abbrev, r.role_id FROM Roles AS r, UserRoles AS ur WHERE r.role_id = ur.role_id AND ur.user_id=:user_id AND ur.is_active=1", array(
         ':user_id' => $this->_id,
      ));

      $mainRoleID = $mainRole[0]['role_id'];
      $mainRole = $mainRole[0]['abbrev'];

      $activeCenter = -1;

      if ($mainRole == ROLE_ID_CENTER_DIRECTOR || $mainRole == ROLE_ID_CENTER_STAFF) {

         $activeCenter = $this->_pdo->query("SELECT param_value FROM UserRoleParameters WHERE user_id=:user_id AND role_id=:role_id AND is_active=1", array(
            ':user_id' => $this->_id,
            ':role_id' => $mainRoleID,
         ));

         if (count($activeCenter) > 0)
            $activeCenter = $activeCenter[0]['param_value'];
         else
            $activeCenter = -1;

      }

      return array('main_role' => $mainRole, 'active_center' => $activeCenter);

   }//getActiveRoleSettings

   // ---------------------------

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

   public function setOrganizations($organization_ids = array(), $role = ROLE_ID_CENTER_DIRECTOR, $reassignActiveToPrimary = false) {

      // This feature currently applies to center directors and center staff members...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling setOrganization()");
      }

      if ($role != ROLE_ID_CENTER_DIRECTOR && $role != ROLE_ID_CENTER_STAFF) {
         throw new \Exception("This user must be saved prior to calling setOrganization()");
      }

      $role_id = $this->_getRoleID($role);

      // -------------------------------------------------------

      $this->_pdo->execute("DELETE FROM UserRoleParameters " .
                           "WHERE user_id=:user_id AND role_id=:role_id AND param_name='provider'", array(
         ':user_id' => $this->_id,
         ':role_id' => $role_id,
      ));

      // =======================================

      $active_is_in_set = false;
      $primary_is_in_set = false;

      $active_organization = NULL;

      foreach ($organization_ids as $organization_id => $config) {

         $active_flag = 0;
         $primary_flag = 0;

         if (($config['active'] == true) && ($reassignActiveToPrimary == false)) {
            $active_flag = 1;
            $active_is_in_set = true;
         }

         if ($config['primary'] == true) {

            $primary_flag = 1;
            $primary_is_in_set = true;

            if ($reassignActiveToPrimary == true) {
               $active_flag = 1;
               $active_is_in_set = true;
            }

         }

         if ($active_flag == 1) {
            $active_organization = $organization_id;
         }

         $insertStatement = "INSERT INTO UserRoleParameters " .
                            "(user_id, role_id, param_name, param_op, param_value, is_primary, is_active, promoter) " .
                            "VALUES (:user_id, :role_id, 'provider', '=', :param_value, :is_primary, :is_active, -1)";

         $this->_pdo->execute($insertStatement, array(
            ':user_id' => $this->_id,
            ':role_id' => $role_id,
            ':param_value' => $organization_id,
            ':is_primary' => $primary_flag,
            ':is_active' => $active_flag,
         ));

      }//foreach

      // =======================================

      if ($active_is_in_set == true) {
         $this->setActiveRole($role, $active_organization);
      }

      if ($primary_is_in_set == true) {
         $this->setPrimaryRole($role);
      }

   }//setOrganizations

   // ---------------------------

   /*
    *
    * @function enumCenterStaffSites
    *
    */

   public function enumCenterStaffSites() {

      // This feature currently applies to center staff members...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling enumCenterStaffSites()");
      }

      $sites = $this->_pdo->query("SELECT param_value AS provider, is_primary FROM moddb.UserRoleParameters WHERE role_id=5 AND param_name='provider' AND user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));

      return $sites;

   }//enumCenterStaffSites

   // ---------------------------

   /*
    *
    * @function enumCenterDirectorSites
    *
    */

   public function enumCenterDirectorSites() {

      // This feature currently applies to center directors...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling enumCenterDirectorSites()");
      }

      $sites = $this->_pdo->query("SELECT param_value AS provider, is_primary FROM moddb.UserRoleParameters WHERE role_id=1 AND param_name='provider' AND user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));

      return $sites;

   }//enumCenterDirectorSites

   // ---------------------------

   /*
    *
    * @function disassociateWithOrganizations
    *
    * @param int $organization_id
    *
    *
    */

   public function disassociateWithOrganizations() {

      // This feature currently applies to center directors...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling disassociateWithOrganizations()");
      }

      $cleanupStatement = "DELETE FROM UserRoleParameters WHERE user_id=:user_id AND role_id=1 AND param_name='provider'";

      $this->_pdo->execute($cleanupStatement, array(
         ':user_id' => $this->_id,
      ));

   }//disassociateWithOrganizations

   // ---------------------------

   /*
    *
    * @function getInstitution
    *
    * @return array
    *
    *
    */

   public function getInstitution() {

      // This feature currently applies to campus champions...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling getInstitution()");
      }

      $query = "SELECT param_value FROM UserRoleParameters WHERE user_id=:user_id AND param_name='institution'";

      $results = $this->_pdo->query($query, array(
         ':user_id' => $this->_id,
      ));

      return (count($results) > 0) ? $results[0]['param_value'] : '-1';

   }//getInstitution

   // ---------------------------

   public function isCenterDirectorOfOrganization($organization_id) {

      $results = $this->_pdo->query(
                           "SELECT COUNT(*) AS num_matches FROM UserRoleParameters WHERE user_id=:user_id AND role_id=:role_id AND param_name=:param_name AND param_value=:param_value",
                           array(
                              'user_id' => $this->_id,
                              'role_id' => \xd_roles\getRoleIDFromIdentifier(ROLE_ID_CENTER_DIRECTOR),
                              'param_name' => 'provider',
                              'param_value' => $organization_id
                           )
      );

      $matches = $results[0]['num_matches'];

      return ($matches != 0);

   }//isCenterDirectorOfOrganization

   // ---------------------------

   /*
    *
    * @function getActiveOrganization
    *
    * @return int (corresponding to the primary organization)
    *
    *
    */

   public function getPrimaryOrganization() {

      // This feature currently applies to center directors...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling getPrimaryOrganization()");
      }

      $query = "SELECT urp.param_value FROM UserRoleParameters AS urp, Roles AS r WHERE urp.role_id = r.role_id AND r.abbrev='cd' AND urp.user_id=:user_id AND urp.param_name='provider' AND urp.is_primary=1";

      $results = $this->_pdo->query($query, array(
         ':user_id' => $this->_id,
      ));

      return (count($results) > 0) ? $results[0]['param_value'] : '-1';

   }//getPrimaryOrganization

   // ---------------------------

   /*
    *
    * @function getActiveOrganization
    *
    * @return int (corresponding to the active organization)
    *
    *
    */

   public function getActiveOrganization() {

      // This feature currently applies to center directors...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling getActiveOrganization()");
      }

      $query = "SELECT urp.param_value FROM UserRoleParameters AS urp, Roles AS r WHERE urp.role_id = r.role_id AND r.abbrev='cd' AND urp.user_id=:user_id AND urp.param_name='provider' AND urp.is_active=1";

      $results = $this->_pdo->query($query, array(
         ':user_id' => $this->_id,
      ));

      return (count($results) > 0) ? $results[0]['param_value'] : '-1';

   }//getActiveOrganization


   // ---------------------------

   /*
    *
    * @function getOrganizationCollection
    *
    * @return array of integers (where each int represents an organization that user is affiliated with as a center staff member or center director)
    *
    *
    */

   public function getOrganizationCollection($center_staff_or_director = ROLE_ID_CENTER_STAFF) {

      // This feature currently applies to center staff / center directors...

      if (empty($this->_id)) {
         throw new \Exception("This user must be saved prior to calling getOrganizationCollection()");
      }

      $query = "SELECT urp.param_value FROM UserRoleParameters AS urp, Roles AS r WHERE urp.role_id = r.role_id AND r.abbrev=:abbrev AND urp.user_id=:user_id AND urp.param_name='provider'";

      $center_collection = array();

      $results = $this->_pdo->query($query, array(
         ':abbrev' => $center_staff_or_director,
         ':user_id' => $this->_id,
      ));

      foreach ($results as $center_data) {
         $center_collection[] = $center_data['param_value'];
      }

      return $center_collection;

   }//getOrganizationCollection

   // ---------------------------

   /*
    *
    * @function getRoles
    *
    * @param string $flag ('formal' or 'informal')
    *
    * @return array
    *
    */

   public function getRoles($flag = 'informal') {

      if ($flag == 'informal'){ return $this->_roles; }

      if ($flag == 'formal') {

         $query  = "SELECT r.description, r.abbrev FROM Roles AS r, UserRoles AS ur ";
         $query .= "WHERE r.role_id = ur.role_id AND ur.user_id = :user_id ORDER BY ur.is_primary DESC";

         $results = $this->_pdo->query($query, array(
            ':user_id' => $this->_id,
         ));

         $roles = array();

         foreach($results as $roleSet) {

            $roles[$roleSet['description']] = $roleSet['abbrev'];

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

   public function setRoles($role_set) {
      $this->_roles = $role_set;
   }

   // ---------------------------

  /*
   *
   * @function getPrimaryRole
   *
   * @return string
   *
   */

   public function getPrimaryRole() {

      if ($this->_primary_role->getIdentifier() == ROLE_ID_PUBLIC) {
         return $this->_primary_role;
      }

      if ($this->_id == NULL) {
         throw new Exception('You must call saveUser() on this newly created XDUser prior to using getPrimaryRole()');
      }

      return $this->_primary_role;

   }//getPrimaryRole

   // ---------------------------

  /*
   *
   * @function setPrimaryRole
   *
   * @param string $primary_role
   *
   */

   public function setPrimaryRole($primary_role) {

      $primary_role_name = $this->_getFormalRoleName($primary_role);

      if ($primary_role_name == NULL) {
         throw new Exception("Attempting to set an invalid primary role");
      }

      $this->_primary_role = \User\aRole::factory($primary_role_name);

      if ($this->_id != NULL) {
         $this->_primary_role->configure($this);
      }

   }//setPrimaryRole

   // ---------------------------

  /*
   *
   * @function getActiveRole
   *
   * @return aRole subclass instance
   *
   */

   public function getActiveRole() {

      if ($this->_active_role->getIdentifier() == ROLE_ID_PUBLIC) {
         return $this->_active_role;
      }

      if ($this->_id == NULL) {
         throw new Exception('You must call saveUser() on this newly created XDUser prior to using getActiveRole()');
      }

      return $this->_active_role;

   }//getActiveRole


   public function setCachedActiveRole($role) {

      $this->_cachedActiveRole = $role;

   }

   public function getCachedActiveRole() {

      return $this->_cachedActiveRole;

   }

  // ---------------------------

  /*
   *
   * @function getMostPrivilegedRole
   *
   * @return aRole subclass instance
   *
   */

   public function getMostPrivilegedRole() {

      // XDUser::enumAllAvailableRoles already orders the roles in terms of 'visibility' / 'highest privilege'
      // so just acquire the first item in the set.

      $availableRoles = $this->enumAllAvailableRoles();
      if(count($availableRoles) > 0)
      {

         $roleData = explode(':', $availableRoles[0]['param_value']);
         $roleData = array_pad($roleData, 2, NULL);

         return $this->assumeActiveRole($roleData[0], $roleData[1]);

      }else
      {

         return $this->getActiveRole();

      }

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

       foreach($this->enumAllAvailableRoles() as $availableRole)
       {
           $roleData = array_pad(explode(':', $availableRole['param_value']), 2, NULL);
           $allroles[] = $this->assumeActiveRole($roleData[0], $roleData[1]);
       }

       if ($includePublicRole) {
           $allroles[] = $this->assumeActiveRole(ROLE_ID_PUBLIC);
       }

       return $allroles;
   }

   // ---------------------------

  /*
   *
   * @function _isValidOrganizationID
   *
   * @param int $role_id
   * @param int $organization_id
   *
   * @return boolean
   *
   */

   private function _isValidOrganizationID($role_id, $organization_id) {

      $results = $this->_pdo->query("SELECT COUNT(*) AS num_matches FROM UserRoleParameters WHERE user_id=:user_id AND role_id=:role_id AND param_value=:organization_id",
         array(
            'user_id' => $this->_id,
            'role_id' => $role_id,
            'organization_id' => $organization_id
         )
      );

      return ($results[0]['num_matches'] != 0);

   }//_isValidOrganizationID

   // ---------------------------

  /*
   *
   * @function _getRoleIDFromIdentifier
   *
   * @param string $identifier (see constants.php, ROLE_ID_... constants)
   *
   * @return int (the numerical id corresponding to the role identifier passed in)
   *
   */

   private function _getRoleIDFromIdentifier($identifier) {

      $role_data = $this->_pdo->query("SELECT role_id FROM Roles WHERE abbrev=:abbrev", array(
         ':abbrev' => $identifier,
      ));

      if (count($role_data) == 0) {
         //throw new Exception('Invalid role identifier specified -- '.$identifier);
         return -1;
      }

      return $role_data[0]['role_id'];

   }//_getRoleIDFromIdentifier

   // ---------------------------

  /*
   *
   * @function assumeActiveRole
   *
   * Allows this user to take on a role, yet does not 'record' this fact into the database (a 'virtual' role, if you will)
   *
   * @param int $active_role (see constants.php, ROLE_ID_... constants)
   * @param int $role_param [depending on the role, a specific value tied to that role that behaves as an additional filter (e.g. organization / institution id)
   *
   *
   */

   public function assumeActiveRole($active_role, $role_param = NULL) {

      if (empty($active_role)) $active_role = ROLE_ID_PUBLIC;

      $active_role_name = $this->_getFormalRoleName($active_role);

      $virtual_active_role = \User\aRole::factory($active_role_name);
      $virtual_active_role->configure($this, $role_param);

      return $virtual_active_role;

   }//assumeActiveRole

   // ---------------------------

  /*
   *
   * @function setActiveRole  (NOTE: When using setActiveRole(), ensure that a subsequent call to saveUser() is made)
   *
   * @param int $active_role (see constants.php, ROLE_ID_... constants)
   * @param int $role_param [required depending on what role is being set as active]
   *
   */

   public function setActiveRole($active_role, $role_param = NULL) {

      $active_role_name = $this->_getFormalRoleName($active_role);

      if ($active_role_name == NULL) {
         throw new Exception("Attempting to set an invalid active role");
      }

      $role_id = $this->_getRoleIDFromIdentifier($active_role);

      $campus_champion_role_id = $this->_getRoleIDFromIdentifier(ROLE_ID_CAMPUS_CHAMPION);

      if ($active_role == ROLE_ID_CENTER_DIRECTOR || $active_role == ROLE_ID_CENTER_STAFF) {

         if ($role_param == NULL) {
            throw new Exception("An additional parameter must be passed for this role (organization id)");
         }

         if ($this->_isValidOrganizationID($role_id, $role_param) == true) {

            $this->_pdo->execute("UPDATE moddb.UserRoleParameters SET is_active=0 WHERE user_id=:user_id AND role_id != :role_id", array(
               ':user_id' => $this->_id,
               ':role_id' => $campus_champion_role_id,
            ));
            $this->_pdo->execute("UPDATE moddb.UserRoleParameters SET is_active=1 WHERE user_id=:user_id AND role_id=:role_id AND param_value=:param_value", array(
               ':user_id' => $this->_id,
               ':role_id' => $role_id,
               ':param_value' => $role_param,
            ));

         }
         else {

            throw new Exception("An invalid organization id has been specified for the role you are attempting to make active");

         }

      }
      else {

         $this->_pdo->execute("UPDATE moddb.UserRoleParameters SET is_active=0 WHERE user_id=:user_id AND role_id != :role_id", array(
            ':user_id' => $this->_id,
            ':role_id' => $campus_champion_role_id,
         ));

      }

      $this->_pdo->execute("UPDATE moddb.UserRoles SET is_active=0 WHERE user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));
      $this->_pdo->execute("UPDATE moddb.UserRoles SET is_active=1 WHERE user_id=:user_id AND role_id=:role_id", array(
         ':user_id' => $this->_id,
         ':role_id' => $role_id,
      ));

      $this->_active_role = \User\aRole::factory($active_role_name);

      if ($this->_id != NULL) {
         $this->_active_role->configure($this);
      }

   }//setActiveRole


   // ---------------------------

  /*
   *
   * @function assignActiveRoleToPrimary (Re-assigns the user's active role to their primary role (failover))
   *
   */

   public function assignActiveRoleToPrimary() {

      $this->_pdo->execute("UPDATE moddb.UserRoles SET is_active=is_primary WHERE user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));
      $this->_pdo->execute("UPDATE moddb.UserRoleParameters SET is_active=is_primary WHERE user_id=:user_id", array(
         ':user_id' => $this->_id,
      ));

   }//assignActiveRoleToPrimary


   // ---------------------------

  /*
   *
   * @function getUserID
   *
   * @return int
   *
   */

   public function getUserID() {
      return (empty($this->_id)) ? '0' : $this->_id;
   }

   // --------------------------------------------------

   // @function getPromoter
   //
   // @param int $role_id (consult the ROLE_ID.. constants in constants.php)
   // @param int $organization_id
   //
   // @returns id (the id of the user who promoted this user to center director)
   //
   // If the value returned is -1, then either:
   // - The promoter's account has been removed from the system
   // - The center director is a primary center director


   public function getPromoter($role_id, $organization_id) {

      $pdo = DB::factory('database');

      $promoterResults = $pdo->query(
         "SELECT promoter FROM UserRoleParameters WHERE user_id=:user_id AND role_id=:role_id AND param_value=:param_value",
         array(
            'user_id' => $this->_id,
            'role_id' => \xd_roles\getRoleIDFromIdentifier($role_id),
            'param_value' => $organization_id
         )
      );

      if (count($promoterResults) == 0) return -1;

      return $promoterResults[0]['promoter'];

   }//getPromoter

   // ---------------------------

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

   public function getPersonID($default = FALSE) {

      // NOTE: RESTful services do not operate on the concept of a session, so we need to check for $_SESSION[..] entities using isset

      if (isset($_SESSION['xdUser']) && ($_SESSION['xdUser'] == $this->_id) && ($default == FALSE)){

         // The user object pertains to the user logged in..

         if (isset($_SESSION['assumed_person_id'])){
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

   public function setPersonID($person_id) {
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

   public function getCreationTimestamp() {
      return $this->_formalizeTimestamp($this->_timeCreated);
   }

   // ---------------------------

  /*
   *
   * @function getPasswordLastUpdatedTimestamp
   *
   * @return string
   *
   */

   public function getPasswordLastUpdatedTimestamp() {
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

   public function getUpdateTimestamp() {
      return $this->_formalizeTimestamp($this->_timeUpdated);
   }

   // ---------------------------

  /*
   *
   * @function _formalizeTimestamp
   * (transforms the DB stored timestamp into a more readable format)
   *
   * @return string
   *
   */

   private function _formalizeTimestamp($db_timestamp) {

      if (!isset($db_timestamp)) return "??";

      list($db_date, $db_time) = explode(' ', $db_timestamp);

      list($year, $month, $day) = explode('-', $db_date);

      $formal_date = $month.'/'.$day.'/'.$year;

      // ------------------------

      list($m_hour, $min, $sec) = explode(':', $db_time);

      $meridiem = ($m_hour > 11) ? 'PM' : 'AM';

      $s_hour = ($m_hour > 12) ? $m_hour - 12 : $m_hour;
      if ($s_hour == 0) $s_hour = 12;

      $formal_time = $s_hour.':'.$min.':'.$sec;

      return $formal_date.', '.$formal_time.' '.$meridiem;

   }//_formalizeTimestamp

   // ---------------------------

  /*
   * @function _getFormalRoleName
   * (determines the formal description of a role based on its abbreviation)
   *
   * @return string representing the formal role name if the abbreviation is recognized
   * @return NULL otherwise
   *
   */

   public  function _getFormalRoleName($role_abbrev) {

      if ($role_abbrev == ROLE_ID_PUBLIC) {
         return 'Public';
      }

      if ($role_abbrev == NULL){
         return 'Public';
      }

      $pdo = DB::factory('database');

      $roleData = $pdo->query("SELECT description FROM Roles WHERE abbrev=:abbrev", array(
         ':abbrev' => $role_abbrev,
      ));

      if (count($roleData) == 0) {
         return 'Public';
      }

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

   private function _generateToken() {

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


   // --------------------------------

  /*
   *
   * @function validateRID
   * (determines whether the reset identifier (RID) is valid -- used for account password updating)
   *
   * @param string $rid
   *
   * @return array:  if first element in the array is VALID, then the array structure will be (VALID, id, first_name)
   *                 if first element in the array is INVALID, then the array structure will be (INVALID)
   *
   */

   public static function validateRID($rid) {

      $pdo = DB::factory('database');

      $accountCheck = $pdo->query("SELECT id, first_name FROM Users WHERE MD5(CONCAT(username, password_last_updated)) = :rid", array(
         ':rid' => $rid,
      ));

      if (count($accountCheck) > 0) {
         return array('status' => VALID, 'user_id' => $accountCheck[0]['id'], 'user_first_name' => $accountCheck[0]['first_name']);
      }
      else {
         return array('status' => INVALID);
      }

   }//validateRID


   public function getRoleCategories() {

      $availableRoles = $this->enumAllAvailableRoles();
      $userRoles = array();
      $userHasProgramOfficerRole = false;

      $center_ids = array();

      foreach ($availableRoles as $role) {

         if ($role['param_value'] == ROLE_ID_PROGRAM_OFFICER){ $userHasProgramOfficerRole = true; }

         $userRoles[$role['param_value']] = $role['description'];

         $roleMetaData = explode(':', $role['param_value']);

         if (count($roleMetaData) == 2) {

            $center_ids[] = $roleMetaData[1];

         }

      }//foreach

      // ---------------------------------------

      $rpCategories = array();

      $pdo = DB::factory('datawarehouse');
      $rps = $pdo->query(
         "SELECT distinct
         o.organization_id as id,
         short_name
         FROM
         serviceprovider o
      order by short_name"
      );

      $role_parameters = $this->getActiveRole()->getParameters();

      // If there is only one service provider, don't display any.
      if (count($rps) > 1) {
         foreach($rps as $rp)
         {
            if(isset($role_parameters['provider']) && $role_parameters['provider'] == $rp['id'])continue; //ignore the rp that is linked to the role
            $rpCategories['rp_'.$rp['id']] = $rp['short_name'];//.' - '.$rp['name'];
         }
      }

      $roleCategories = $this->getActiveRole()->getRoleCategories(false);//toms wants XSEDE to show no matter if you are po or not.

      /*
      foreach($roleCategories as $key => $value)
      {
         if($key == 'my')
         {
            $roleCategories[$key] = $this->getActiveRole()->getFormalName();
         }
      }
      */

      $roleCategories = array_reverse($roleCategories);
      $roleCategories = array_merge($roleCategories, $rpCategories);


      $filteredRoleCategories = array();

      foreach ($roleCategories as $k => $v) {

         $resourceProviderMetaData = explode('_', $k);

         if (count($resourceProviderMetaData) == 1) {

            $filteredRoleCategories[$k] = $v;

         }
         else if (!in_array($resourceProviderMetaData[1], $center_ids)) {

            $filteredRoleCategories[$k] = $v;

         }

      }//foreach ($roleCategories as $k => $v)


      $roleCategories = array_merge($userRoles, array('separator'), $filteredRoleCategories);

      return json_encode($roleCategories);

   }

   // XSEDE-Centric functionality =========================================================

   /*
    * @function initializeXSEDEUser
    *
    * Manifests a XDUser from an XSEDE account (referenced by XSEDE username and the CN of the certificate)
    *
    * @param String $username (The XSEDE username (username;Formal Name)
    *
    * @returns an XDUser object
    *
    */

   public static function initializeXSEDEUser($username) {

      list($xsede_username, $formal_name) = explode(';', $username);
      list($first_name, $last_name) = explode(' ', $formal_name, 2);

      $person_id = self::resolvePersonIDFromXSEDEUsername($xsede_username);

      $email_address = self::_getXSEDEEmailAddressFromPersonID($person_id);

      $user = new self(
         $username,
         NULL,                    // password
         NO_EMAIL_ADDRESS_SET,    // e-mail address
         $first_name,
         NULL,                    // middle name
         $last_name
      );

      $user->setUserType(XSEDE_USER_TYPE);                   // XSEDE User
      $user->setPersonID($person_id);

      $user->setEmailAddress($email_address);

      $user->saveUser();

      // Role detection -------------------------------

      $user_role_set = array(ROLE_ID_USER);

      if (self::isPrincipalInvestigator($person_id) === true) {

         // Add PI role to the to-be-created user
         $user_role_set[] = ROLE_ID_PRINCIPAL_INVESTIGATOR;

         $user->setActiveRole(ROLE_ID_PRINCIPAL_INVESTIGATOR);

         $cc_org_id = self::isCampusChampion($person_id);

         if ($cc_org_id !== false) {

            // Add CC role to the to-be-created user
            $user_role_set[] = ROLE_ID_CAMPUS_CHAMPION;

            $user->setInstitution($cc_org_id);

         }

      }

      $user->setRoles($user_role_set);

      // ----------------------------------------------

      $user->saveUser();

      return $user;

   }//initializeXSEDEUser

   // --------------------------------

   /*
    * @function deriveUserFromXSEDEUser
    *
    * Maps an XSEDE user to an XDUser
    *
    * @param String $username (The XSEDE username (username;Formal Name)
    *
    * @returns an XDUser object
    *
    */

   public static function deriveUserFromXSEDEUser($username) {

      $pdo = DB::factory('database');

      $userCheck = $pdo->query(
         "SELECT id FROM Users WHERE username=:username AND user_type=:user_type",
         array(
            'username' => $username,
            'user_type' => XSEDE_USER_TYPE
         )
      );

      if (count($userCheck) == 0) {
         return NULL;
      }

      return self::getUserByID($userCheck[0]['id']);

   }//deriveUserFromXSEDEUser

   // --------------------------------

   /*
    * @function XSEDEUserExists
    *
    * Determines whether an XSEDE user is already established in our accounts registry
    *
    * @param String $username (The XSEDE username (username;Formal Name)
    *
    * @returns boolean
    *
    */

   public static function XSEDEUserExists($username) {

      $pdo = DB::factory('database');

      $userCheck = $pdo->query(
         'SELECT id FROM moddb.Users WHERE username = :username AND user_type=:user_type',
         array(
            'username' => $username,
            'user_type' => XSEDE_USER_TYPE
         )
      );

      return (count($userCheck) > 0);

   }//XSEDEUserExists

   // --------------------------------

   /*
    * @function isXSEDEUser
    *
    * Determines whether the user is an XSEDE-oriented user
    *
    * @returns boolean
    *
    */

   public function isXSEDEUser() {

      return ($this->getUserType() == XSEDE_USER_TYPE);

   }//isXSEDEUser

   // --------------------------------

   /*
    * @function getXSEDEUsername
    *
    * Resolves the XSEDE username from the XDMoD-formatted username
    *
    * @returns String (the XSEDE username)
    *
    */

   public function getXSEDEUsername() {

      if (strpos($this->getUsername(), ';') !== false) {

         list($xsede_username, $formal_name) = explode(';', $this->getUsername(), 2);

         return $xsede_username;

      }
      else {
         throw new \Exception('The user is not a valid XSEDE user');
      }

   }//getXSEDEUsername

   // --------------------------------

   /*
    * @function resolvePersonIDFromXSEDEUsername
    *
    * Determines the person id from the XSEDE username
    *
    * @param String $username (The XSEDE username)
    *
    * @returns int (the person id corresponding to the username)
    *
    * @throws Exception if the username cannot be mapped to a person id (which may happen if the state of the production tgcdb is 'ahead' of our local copy)
    *
    * (Verified by Dave Hart) -- Every XSEDE user has a record in acct.system_accounts which pertains to the 'portal.teragrid' resource
    *
    */

   public static function resolvePersonIDFromXSEDEUsername($username) {

      $pdo = DB::factory('database');

      $result = $pdo->query(
         'SELECT sa.person_id FROM modw.systemaccount AS sa, modw.resourcefact AS r WHERE sa.username=:username AND sa.resource_id = r.id AND r.name="portal.teragrid"',
         array(
            'username' => $username
         )
      );

      if (count($result) == 0) {
         throw new \Exception("Cannot locate information for user $username");
      }

      return $result[0]['person_id'];

   }//resolvePersonIDFromXSEDEUsername

   // --------------------------------

   /*
    * @function _getXSEDEEmailAddressFromPersonID
    *
    * Determines the email address for an XSEDE user based on his/her person id
    *
    * @param int $person_id (The XSEDE person id)
    *
    * @returns string (the corresponding email address)
    * If no e-mail address can be found (or the person id does not validate), then NO_EMAIL_ADDRESS_SET is returned
    *
    */

   private static function _getXSEDEEmailAddressFromPersonID($person_id) {

      $pdo = DB::factory('database');

      $result = $pdo->query(
         'SELECT email_address FROM modw.person WHERE id=:person_id',
         array(
            'person_id' => $person_id
         )
      );

      return (count($result) > 0 && !empty($result[0]['email_address'])) ? $result[0]['email_address'] : NO_EMAIL_ADDRESS_SET;

   }//_getXSEDEEmailAddressFromPersonID


   public function getDisabledMenus($realms)
   {
      // Get the set of disabled menus for each role the user has.
      $disabledMenusByRole = array();

      foreach($this->_roles as $role_abbrev)
      {

         if ($role_abbrev == 'dev') continue;

         $role = \User\aRole::factory($this->_getFormalRoleName($role_abbrev));
         $disabledMenusByRole[$role_abbrev] = $role->getDisabledMenus($realms);

      }
      $role = \User\aRole::factory($this->_getFormalRoleName('pub'));
      $disabledMenusByRole['pub'] = $role->getDisabledMenus($realms);

      // If the user only has one role, return that role's menus immediately.
      if (count($disabledMenusByRole) === 1) {
         return reset($disabledMenusByRole);
      }

      // Select only menus that are disabled for every role the user has.
      // (If any role has no disabled menus, an empty list can be returned
      // immediately, as there can be no menus that will be available for
      // every role.)
      $returnData = array();
      foreach ($disabledMenusByRole as $role => $roleDisabledMenus) {
         if (empty($roleDisabledMenus)) {
            return $returnData;
         }
      }

      $checkedMenus = array();
      foreach ($disabledMenusByRole as $role => $roleDisabledMenus) {
         foreach ($roleDisabledMenus as $roleDisabledMenu) {
            $roleDisabledMenuId = $roleDisabledMenu['id'];
            if (array_key_exists($roleDisabledMenuId, $checkedMenus)) {
               continue;
            }

            $menuDisabledForAllRoles = true;
            foreach ($disabledMenusByRole as $checkedRole => $checkedRoleDisabledMenus) {
               if ($role === $checkedRole) {
                  continue;
               }

               $checkedDisabledMenuFound = false;
               foreach ($checkedRoleDisabledMenus as $checkedRoleDisabledMenu) {
                  if ($checkedRoleDisabledMenu['id'] === $roleDisabledMenuId) {
                     $checkedDisabledMenuFound = true;
                     break;
                  }
               }

               if (!$checkedDisabledMenuFound) {
                  $menuDisabledForAllRoles = false;
                  break;
               }
            }

            $checkedMenus[$roleDisabledMenuId] = true;
            if ($menuDisabledForAllRoles) {
               $returnData[] = $roleDisabledMenu;
            }
         }
      }

      return $returnData;
   }

   public function getAssets()
   {
       return $this->_assets;
   }

   public function setAssets(array $assets)
   {
       $this->_assets = $assets;
   }

   public function getAcls()
   {
       return $this->_acls;
   }

   public function setAcls(array $acls)
   {
       $this->_acls = $acls;
   }


}//XDUser
