<?php
/* ==========================================================================================
 * Interface for objects used to pass state between ETL actions.  The state object should appear as
 * a normal stdClass object with data members accessible as properties. These are typically
 * implemented using the __get(), __set(), __isset(), and __unset() magic methods.
 *
 * State is intended to be shared in three ways:
 *
 * 1. Intra-action state. Actions that wish to save state and make it available to subsequent
 *    instantiations. In this case a unique key will be generated based on the action name and the
 *    schema is optional - data will be managed by the action itself since it is the only one using
 *    it. The action will automatically load state if it exists in the database. NOTE: Only a single
 *    intra-action state per action is supporterted
 *
 * 2. Inter-action state. Actions that wish to store state information and make it available to
 *    other actions. In this case the key must be provided and state data must be defined using a
 *    schema to provide a contract between producers and consumers.  The schema will follow the
 *    format described at http://json-schema.org/ and only data members described in the schema will
 *    be allowed. Verification will be optional.
 *
 * 3. Inter-action state as a data source. An action may store state for use as a data source for
 *    another action. A unique key and schema is also required in this case so that an action using
 *    the data source can make explicit use of the defined fields. For example, an action may store
 *    invalid identifiers in a state object an another action may use these ids as input for marking
 *    them as invalid in a table (e.g., the UpdateIngestor)
 *
 * Future additions:
 *
 * - Schema definition and validators. We should be able to define state objects and an associated
 *   schema to provide a contract between producers and consumers. The state object will enforce the
 *   schema and provide validators for basic types.  Possible set up constraints similar to
 *   https://github.com/justinrainbow/json-schema/tree/master/src/JsonSchema/Constraints
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-28
 * ==========================================================================================
 */

namespace ETL\State;

use Log;
use stdClass;

interface iActionState
{
    /* ------------------------------------------------------------------------------------------
     * Construct an ActionState object.
     *
     * @param $key The key used to identify this state object
     * @param $actionName The name of the action that is making the request
     * @param $type The state object type
     * @param $options An object containing the options defining this state object
     * @param $logger Optional logger for logging error messages
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($key, $actionName, $type, stdClass $options = null, Log $logger = null);

    /* ------------------------------------------------------------------------------------------
     * @return The key for this state object. 
     * ------------------------------------------------------------------------------------------
     */

    public function getKey();

    /* ------------------------------------------------------------------------------------------
     * Set the key for this state object. The key is used to identify the object in the database.
     *
     * @param $key A string representing a unique key.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setKey($key);

    /* ------------------------------------------------------------------------------------------
     * @return The type for this state object. 
     * ------------------------------------------------------------------------------------------
     */

    public function getType();

    /* ------------------------------------------------------------------------------------------
     * @return An object containing metadata about the state object including, but not limited to:
     *
     * creating_action - The action that created the state object
     * modifying_action - The last action that modified the state object
     * creation_time - The date and time that the object was created
     * modified_time - The date and time that the object was last modified
     * ------------------------------------------------------------------------------------------
     */

    public function getMetadata();
} // interface iActionState
