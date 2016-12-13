<?php

   use CCR\DB;

   // @class XDUserProfile

   require_once dirname(__FILE__).'/../configuration/linker.php';

   class XDUserProfile {

      const TARGET_SCHEMA = 'moddb.UserProfiles';
      /*
      CREATE TABLE moddb.UserProfiles (
         user_id INT(11), primary key(user_id),
         serialized_profile_data BLOB
      );
      */

      // Class constants (used as keys for accessing/setting values in the profile) -------------

      const INITIAL_TAB = 0;
      const LAST_VISITED_TAB = 1;
      const DASHBOARD_CHARTS = 2;

      // Private variables --------------------------------

		private $_pdo;                       // PDO Handle (set in __construct)
		private $_user_id;                   // User ID (set in __construct)
		private $_profile_data;              // Represents cached profile data

      // -----------------------------------------------------

      // @function __construct()
      //
      // @param int $user_id [user id pertaining to the user whose profile you want to manage]

      function __construct($user_id) {

         $this->_pdo = DB::factory('database');

         $this->_user_id = $user_id;

         $this->_load();

      }//__construct

      // -----------------------------------------------------

      // @function setValue()
      //
      // @param String $param [The parameter / key]
      // @param Object $value [The object to be mapped to the parameter / key]

      public function setValue($param, $value) {

         $this->_profile_data[$param] = $value;

      }//setValue

      // -----------------------------------------------------

      // @function getDump()
      //
      // NOTE: Should be used for debugging purposes only

      public function getDump() {

         print '<pre>';
         print_r($this->_profile_data);
         print '</pre>';

      }//getDump

      // -----------------------------------------------------

      // @function valueExists()
      //
      // @param String $param
      //
      // @return boolean [Indicating whether a value already exists for a given parameter]

      public function valueExists($param) {

         return (isset($this->_profile_data[$param]));

      }//valueExists

      // -----------------------------------------------------

      // @function fetchValue()
      //
      // @param String $param
      //
      // @return Object [The value of corresponding datatype mapped to the parameter of interest]
      // If there is no mapping, then NULL is returned

      public function fetchValue($param) {

         return (isset($this->_profile_data[$param])) ? $this->_profile_data[$param] : NULL;

      }//fetchValue

      // -----------------------------------------------------

      // @function dropValue()
      //
      // Removes an item from the local cache (made permanent by a subsequent call to save())
      //
      // @param String $param [The parameter identifying the item to be discarded]

      public function dropValue($param) {

         // NOTE: The actual value will stay in the profile cache until a call to save() is made.

         if (isset($this->_profile_data[$param])) {

            unset($this->_profile_data[$param]);

         }

      }//dropValue

      // -----------------------------------------------------

      // @function clear()
      //
      // Clear local cache as well as data in the persistence model (implicit save)

      public function clear() {

         $this->_profile_data = array();

         $this->_pdo->execute(
                              'DELETE FROM '.self::TARGET_SCHEMA.' WHERE user_id=:user_id',
                              array(
                                 'user_id' => $this->_user_id
                              )
         );

      }//clear

      // -----------------------------------------------------

      // @function reload()
      //
      // Re-Populate local cache with data currently in the persistence model
      // NOTE: Any unsaved data will be lost as the result of calling reload()

      public function reload() {

         $this->_load();

      }//reload

      // -----------------------------------------------------

      // @function _load()
      //
      // Discard what is in the local cache and (re-)populate it with data in the persistence model
      // NOTE: Any unsaved data will be lost as the result of calling load()

      private function _load() {

         $this->_profile_data = array();

         $results = $this->_pdo->query('SELECT serialized_profile_data FROM '.self::TARGET_SCHEMA.' WHERE user_id=:user_id',
          array(
            'user_id' => $this->_user_id
          )
         );

         if (count($results) > 0) {

            $this->_profile_data = @unserialize($results[0]['serialized_profile_data']);

            if (!is_array($this->_profile_data)) {

               // Unserialization failed -- initialize to an empty array
               $this->_profile_data = array();

            }

         }//if (count($results) > 0)

      }//_load

      // -----------------------------------------------------

      // @function save()
      //
      // Writes cached profile data back to the persistence model in a serialized format
      //
      // @throws Exception if there was an issue executing the SQL command to write the data to the DB

      public function save() {

         $this->_pdo->execute('LOCK TABLES '.self::TARGET_SCHEMA.' WRITE');
         $this->_pdo->execute('BEGIN');

         // BEGIN Critical Section ========================
         $targetQuery = 'INSERT INTO '.self::TARGET_SCHEMA.' (user_id, serialized_profile_data) VALUES (:user_id, :serialized_profile_data)';
         $targetQuery .= ' ON DUPLICATE KEY UPDATE serialized_profile_data=:serialized_profile_data';

         try {
            $serialized = serialize($this->_profile_data);
            $this->_pdo->execute(
              $targetQuery,
              array(
                'serialized_profile_data' => $serialized,
                'user_id' => $this->_user_id
              )
            );

            $this->_pdo->execute('COMMIT');
            $this->_pdo->execute('UNLOCK TABLES');
         }
         catch(Exception $e) {
            $this->_pdo->execute('ROLLBACK');
            $this->_pdo->execute('UNLOCK TABLES');

            throw new Exception($e->getMessage());

         }

      }//save

   }//XDUserProfile

?>
