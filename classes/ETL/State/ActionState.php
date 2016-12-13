<?php
/* ==========================================================================================
 * ActionState object implementation.  Data is stored in the $this->properties array and magic
 * methods are provided to make this object appear as a stdClass object.
 *
 * Future work:
 *
 * - Implement schema-based object definition. Based on a provided schema, create a set of
 *   properties and only allow those properties to be set/modified.
 * - Implement schema-based property value validation by providing a set of validators for each
 *   property. (see
 *   https://github.com/justinrainbow/json-schema/tree/master/src/JsonSchema/Constraints).
 *
 * @see iActionState for further information.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2016-06-28
 * ==========================================================================================
 */

namespace ETL\State;

// PEAR logger
use Log;
use stdClass;
use Exception;
use PDO;
use ETL\Loggable;
use ETL\DataEndpoint\iRdbmsEndpoint;

class ActionState extends Loggable implements iActionState
{
    // The unique key for identifying this state object in the database
    private $key = null;

    // Object containing state metadata such as date created, last modified, creating action, etc.
    private $metadata = null;

    // List of properties supported by this state object.
    private $properties = array();
    
    // Guard against E_NOTICE for returning null by reference from __get()
    private $nullGuard = null;

    /* ------------------------------------------------------------------------------------------
     * @see iActionState::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct($key, $actionName, $type, stdClass $options = null, Log $logger = null)
    {
        parent::__construct($logger);

        if ( empty($key) || ! is_string($key) ) {
            $msg = "Key must be a valid non-empty string";
            $this->logAndThrowException($msg);
        }

        if ( empty($actionName) || ! is_string($actionName) ) {
            $msg = "Action name must be a valid non-empty string";
            $this->logAndThrowException($msg);
        }

        $this->key = $key;

        $this->metadata = new stdClass;
        $this->metadata->type = $type;
        $this->metadata->creating_action = $actionName;
        $this->metadata->creation_time = date('Y-m-d H:i:s');
        $this->metadata->modifying_action = null;
        $this->metadata->modified_time = null;
        $this->metadata->state_size_bytes = 0;

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iActionState::getKey()
     * ------------------------------------------------------------------------------------------
     */

    public function getKey() {
        return $this->key;
    }  // getKey()
            
    /* ------------------------------------------------------------------------------------------
     * @see iActionState::setKey()
     * ------------------------------------------------------------------------------------------
     */

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }  // setKey()

    /* ------------------------------------------------------------------------------------------
     * @see iActionState::getType()
     * ------------------------------------------------------------------------------------------
     */

    public function getType() {
        return $this->metadata->type;
    }  // getType()

    /* ------------------------------------------------------------------------------------------
     * @see iActionState::getMetadata()
     * ------------------------------------------------------------------------------------------
     */

    public function getMetadata() {
        return $this->metadata;
    }  // getMetadata()

    /* ------------------------------------------------------------------------------------------
     * Return an object property. Note that the return by reference is needed for the array square
     * bracket syntax to work propperly (e.g., $obj->data[] = "new data"). Also note that in order
     * to use square bracket notation the property must first be initialized to an array.
     *
     * @param $prop The property being retrieved
     *
     * @return The requested object property, or NULL if the property was not found.
      * ------------------------------------------------------------------------------------------
     */

    public function &__get($prop) {
        if ( array_key_exists($prop, $this->properties) ) {
            return $this->properties[$prop];
        }

        return $this->nullGuard;
    }  // __get()

    /* ------------------------------------------------------------------------------------------
     * Set the value of an object property.
     *
     * @param $prop The property being set
     * @param $value The value of the object property
     * ------------------------------------------------------------------------------------------
     */

    public function __set($prop, $value) {

        $this->properties[$prop] = $value;

    }  // __set()

    /* ------------------------------------------------------------------------------------------
     * Return TRUE if the property exists and is not NULL.
     *
     * @param $property The name of the property to retrieve
     *
     * @return TRUE if the property exists and is not NULL, FALSE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function __isset($prop) {

        return ( array_key_exists($prop, $this->properties) && null !== $this->properties[$prop] );

    }  // __isset()

    /* ------------------------------------------------------------------------------------------
     * Unset the specified property.
     *
     * @param $property The name of the property to retrieve
     * ------------------------------------------------------------------------------------------
     */

    public function __unset($prop) {
        if ( array_key_exists($prop, $this->properties) ) {
            unset($this->properties[$prop]);
        }
    }  // __unset()

    /* ------------------------------------------------------------------------------------------
     * Exclude the logger object from object serialization.
     *
     * @return An array of properties that will be included in the serialized object
     * ------------------------------------------------------------------------------------------
     */

    public function __sleep() {
        return array_diff(array_keys(get_object_vars($this)), array('logger'));
    }  // __unset()

        /* ------------------------------------------------------------------------------------------
     * Exclude the logger object from object serialization.
     *
     * @return An array of properties that will be included in the serialized object
     * ------------------------------------------------------------------------------------------
     */

    public function __toString() {
        $str = "Key: {$this->key}\n" .
            "Metadata: " . print_r($this->metadata, true) .
            "Properties: " . print_r($this->properties, true);
        return $str;
    }  // __unset()

}  // class ActionState