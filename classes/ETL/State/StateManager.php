<?php
/* ==========================================================================================
 * Singleton manager class for managing ETL action state objects. State objects must implement the
 * ETL\State\iActionState interface.  The following functionality is provided:
 *
 * 1. Save and load state to the database using a unique key as the identifier along with metadata
 *    about the action that created or modified the state and the times those changes ocurred.
 * 2. Delete a state object from the database.
 * 3. An action must be able to perform the following operations:
 *    - Create a new state object
 *    - Load an existing state object from the database using the unique key
 *    - Optionally modify the contents of a state object
 *    - Optionally save the contents back to the database
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-28
 * ==========================================================================================
 */

namespace ETL\State;

use Exception;
use Log;
use PDO;
use stdClass;
use ETL\DataEndpoint\iRdbmsEndpoint;

class StateManager
{
    const STATE_TABLE = 'etl_action_state';

    // Intra-action states are meant to be used by subsequent instantiations of the same action
    const INTRA_ACTION = 'intra';

    // Inter-aaction states are meant to be shared between actions or used as a data source
    const INTER_ACTION = 'inter';

    // Max length of the object key
    const MAX_STATE_KEY_LEN = 64;

    /* ------------------------------------------------------------------------------------------
     * Throw an exceptiona and optionally log the message.
     *
     * @param $msg The message to present
     * @param $logger Optional logger for logging error messages
     *
     * @throws An Exception with the specified message.
     * ------------------------------------------------------------------------------------------
     */

    private static function logAndThrowException($msg, Log $logger = null, $logLevel = PEAR_LOG_ERR)
    {
        if ( null !== $logger ) {
            $logger->log($msg, $logLevel);
        }
        throw new Exception($msg);
    }  // logAndThrowException()

    /* ------------------------------------------------------------------------------------------
     * Get an instance of an ActionState object, either by loading an existing object from the
     * database or by creating a new one.  If a key is not provided this is assumed to be an
     * intra-action state object and a unique key will be automatically generated so that it will
     * not collide with the name of another state object.
     *
     * @param $actionName The name of the action that is making the request
     * @param $endpoint DataEndpoint used to load the state object
     * @param $logger Optional logger for logging error messages
     * @param $key Optional unique key used to identify this state object
     * @param $options Optional directives including a schema definition for inter-action state
     *   objects, represented as a stdClass object
     *
     * @returns The loaded state object or FALSE if the object could not be loaded.
     *
     * @throws Exception if the key is invalid, there was an error querying the database, or no
     *   state object was found with the given key
     * ------------------------------------------------------------------------------------------
     */

    public static function get(
        $actionName,
        iRdbmsEndpoint $endpoint,
        Log $logger = null,
        $key = null,
        stdClass $options = null)
    {
        if ( empty($actionName) || ! is_string($actionName) ) {
            $msg = "Action name must be a non-empty string";
            self::logAndThrowException($msg, $logger);
        }

        // Generate the key if it was not provided

        $type = self::INTER_ACTION;

        if ( null === $key ) {
            // If this string changes, the key for intra-action state objects will also change
            $key = self::generateKey($actionName);
            $type = self::INTRA_ACTION;
        } else if ( empty($key) || ! is_string($key) ) {
            $msg = "Key must be a non-empty string";
            self::logAndThrowException($msg, $logger);
        }

        // Attempt to load the state object from the database. If it was not found, create a new
        // one.

        try {
            $stateObj = self::load($key, $endpoint, $logger);
            if ( false === $stateObj ) {
                $stateObj = new ActionState($key, $actionName, $type, $options, $logger);
                if ( null !== $logger) {
                    $logger->info("Created new state object '$key'");
                }
            }
        } catch (Exception $e) {
            // If an exception was thrown, the message should have already been logged.
            return false;
        }

        return $stateObj;

    }  // get()

    /* ------------------------------------------------------------------------------------------
     * Load an ActionState object from the database.
     *
     * @param $key The unique key used to identify this state object
     * @param $endpoint DataEndpoint used to load the state object
     * @param $logger Optional logger for logging error messages
     *
     * @return The state object, or FALSE if no state with the given key was found.
     *
     * @throws Exception if the key is invalid, there was an error querying the database, or no
     *   state object was found with the given key
     * ------------------------------------------------------------------------------------------
     */

    public static function load($key, iRdbmsEndpoint $endpoint, Log $logger = null)
    {
        if ( empty($key) || ! is_string($key) ) {
            $msg = "Key must be a non-empty string";
            self::logAndThrowException($msg, $logger);
        }

        if ( strlen($key) > self::MAX_STATE_KEY_LEN ) {
            $msg = "Object state key cannot exceed " . self::MAX_STATE_KEY_LEN. " bytes";
            self::logAndThrowException($msg, $logger);
        }

        $tableName = $endpoint->getSchema(true) . "." . $endpoint->quoteSystemIdentifier(self::STATE_TABLE);
        $sql = "SELECT
state_type, creating_action, modifying_action, creation_time, modified_time, state_size_bytes, state_object
FROM $tableName
WHERE state_key = ?";

        if ( null !== $logger ) {
            $logger->info("Load action state object with key '$key'");
            $logger->debug("$sql");
        }

        try {
            $stmt = $endpoint->getHandle()->query($sql, array($key), true);
            $stmt->bindColumn('state_type', $type);
            $stmt->bindColumn('modifying_action', $actionName);
            $stmt->bindColumn('modified_time', $modifiedTime);
            $stmt->bindColumn('state_size_bytes', $stateBytes);
            $stmt->bindColumn('state_object', $serializedObj, PDO::PARAM_LOB);
            $stmt->fetch(PDO::FETCH_BOUND);
        } catch ( PDOException $e ) {
            $msg = "Error loading state for key '$key': " . $e->getMessage();
            self::logAndThrowException($msg, $logger);
        }

        if ( 0 == $stmt->rowCount() ) {
            if ( null !== $logger ) {
                $msg = "No state object found with key '$key'";
                $logger->warning($msg);
            }
            return false;
        }
        
        $stateObj = unserialize($serializedObj);

        // We don't serialize the logger so add it back in upon re-hydration

        $stateObj->setLogger($logger);

        // Update any object metadata that may have changed. We update this on load rather than save
        // because the database handles updating the modified_time.

        $stateObj->getMetadata()->type = $type;
        $stateObj->getMetadata()->modifying_action = $actionName;
        $stateObj->getMetadata()->modified_time = $modifiedTime;
        $stateObj->getMetadata()->state_size_bytes = $stateBytes;

        return $stateObj;
        
    }  // load()

    /* ------------------------------------------------------------------------------------------
     * Delete an ActionState object from the database.
     *
     * @param $identifier Mixed - Either the state object or key to delete
     * @param $endpoint DataEndpoint used to delete the state object
     * @param $logger Optional logger for logging error messages
     *
     * @return TRUE if the state object was deleted, FALSE otherwise.
     *
     * @throws Exception if the key is invalid, there was an error deleting the data, or no
     *   state object was found with the given key
     * ------------------------------------------------------------------------------------------
     */

    public static function delete($identifier, iRdbmsEndpoint $endpoint, Log $logger = null)
    {
        $key = null;

        // If we have an object extract the key, otherwise assume the identifier is a key string

        if ( $identifier instanceof iActionState) {
            $key = $identifier->getKey();
        } else if ( ! empty($identifier) && is_string($identifier) ) {
            $key = $identifier;
        } else {
            $msg = "Identifier must be an object implementing iActionState or a non-empty key string";
            self::logAndThrowException($msg, $logger);
        }
        
        if ( strlen($key) > self::MAX_STATE_KEY_LEN ) {
            $msg = "Object state key cannot exceed " . self::MAX_STATE_KEY_LEN. " bytes";
            self::logAndThrowException($msg, $logger);
        }

        $tableName = $endpoint->getSchema(true) . "." . $endpoint->quoteSystemIdentifier(self::STATE_TABLE);
        $sql = "DELETE FROM $tableName WHERE state_key = ?";

        if ( null !== $logger ) {
            $logger->info("Delete action state object with key '$key'");
            $logger->debug("$sql");
        }

        try {
            $rowsAffected = $endpoint->getHandle()->execute($sql, array($key));
        } catch ( PDOException $e ) {
            $msg = "Error deleting state for key '$key': " . $e->getMessage();
            self::logAndThrowException($msg, $logger);
        }

        if ( 0 == $rowsAffected ) {
            if ( null !== $logger ) {
                $msg = "No state object found with with key '$key'";
                $logger->warning($msg);
            }
            return false;
        }

        return true;

    }  // delete()

    /* ------------------------------------------------------------------------------------------
     * Save the current ActionState object to the database.
     *
     * @param $stateObj A state object implementing the iActionState interface
     * @param $actionName The name of the current action that is saving the state object
     * @param $endpoint DataEndpoint used to save the state object
     * @param $logger Optional logger for logging error messages
     *
     * @return TRUE on success
     *
     * @throws Exception if the action name is not a valid string, or there was an error saving the
     *   state object to the database
     * ------------------------------------------------------------------------------------------
     */

    public static function save(iActionState $stateObj, $actionName, iRdbmsEndpoint $endpoint, Log $logger = null)
    {

        if ( empty($actionName) || ! is_string($actionName) ) {
            $msg = "Action name must be a non-empty string";
            self::logAndThrowException($msg, $logger);
        }

        if ( strlen($stateObj->getKey()) > self::MAX_STATE_KEY_LEN ) {
            $msg = "Object state key cannot exceed " . self::MAX_STATE_KEY_LEN. " bytes";
            self::logAndThrowException($msg, $logger);
        }

        $tableName = $endpoint->getSchema(true) . "." . $endpoint->quoteSystemIdentifier(self::STATE_TABLE);
        $sql = "
INSERT INTO $tableName
  (state_key, state_type, creating_action, modifying_action, creation_time, state_size_bytes, state_object)
VALUES
  (:key, :state_type, :creating_action, :modifying_action, :creation_time, :object_size, :object)
ON DUPLICATE KEY UPDATE
  modifying_action = :modifying_action_upd,
  state_size_bytes = :object_size_upd,
  state_object= :object_upd";
        
        $key = $stateObj->getKey();
        $type = $stateObj->getType();

        if ( null !== $logger ) {
            $logger->info("Save action state object with key '$key' and type '$type'");
            $logger->debug("$sql");
        }
        
        $serialized = serialize($stateObj);
        $size = strlen($serialized);

        try {
            $stmt = $endpoint->getHandle()->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':state_type', $type);
            $stmt->bindParam(':creating_action', $actionName);
            $stmt->bindParam(':modifying_action', $actionName);
            $stmt->bindParam(':creation_time', $stateObj->getMetadata()->creation_time);
            $stmt->bindParam(':object_size', $size, PDO::PARAM_INT);
            $stmt->bindParam(':object', $serialized, PDO::PARAM_LOB);
            $stmt->bindParam(':modifying_action_upd', $actionName);
            $stmt->bindParam(':object_size_upd', $size, PDO::PARAM_INT);
            $stmt->bindParam(':object_upd', $serialized, PDO::PARAM_LOB);
            $stmt->execute();
        } catch ( PDOException $e ) {
            $msg = "Error saving state object for action '$actionName' with key '{$this->key}'";
            self::logAndThrowException($msg, $logger);
        }

        return true;

    }  // save()

    /* ------------------------------------------------------------------------------------------
     * Retrieve a list of all state objects and return the names along with metadata,
     *
     * @param $endpoint DataEndpoint used to save the state object
     * @param $logger Optional logger for logging error messages
     *
     * @return An array of state object names and metadata
     *
     * @throws Exception if the action name is not a valid string, or there was an error saving the
     *   state object to the database
     * ------------------------------------------------------------------------------------------
     */

    public static function getList(iRdbmsEndpoint $endpoint, Log $logger = null)
    {
        $tableName = $endpoint->getSchema(true) . "." . $endpoint->quoteSystemIdentifier(self::STATE_TABLE);
        $sql = "SELECT
state_key, state_type, creating_action, creation_time, modifying_action, modified_time, state_size_bytes
FROM $tableName";

        if ( null !== $logger ) {
            $logger->debug("$sql");
        }

        try {
            $result = $endpoint->getHandle()->query($sql);
        } catch ( PDOException $e ) {
            $msg = "Error listing state objects: " . $e->getMessage();
            self::logAndThrowException($msg, $logger);
        }

        return $result;
    }  // getList()

    /* ------------------------------------------------------------------------------------------
     * State objects are identified by a unique key. Inter-action state objects must be explicitly
     * named in the configuration file, but intra-action state objects use only the name of the
     * action to generate the key (need to be sure no 2 actions have the same name or actions in
     * processes use the process name to maintain uniqueness. To ensure a unique key in case an
     * inter-action key uses the same name as an action, we add a randomly generated static string
     * when generating the key for intra-action state objects.
     *
     * @param $actionName The name of the action we are generating the key for
     * @param $type The action type (inter-action or intra-action)
     *
     * @return A unique key to identify the state object
     * ------------------------------------------------------------------------------------------
     */

    public static function generateKey($actionName, $type = self::INTRA_ACTION)
    {
        if ( self::INTRA_ACTION == $type ) {
            // If this string changes, the key for intra-action state objects will also change
            return sha1('/Xuu$2tTjxW9f$P~@s#:' . $actionName);
        } else {
            return $actionName;
        }
    }  // generateKey()

}  // class StateManager
