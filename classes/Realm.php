<?php

/**
 * Class Realm
 *
 * the 'getters' and 'setters' for this class:
 * @method integer getRealmId()
 * @method void    setRealmId($realmId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method string  getTableName()
 * @method void    setTableName($tableName)
 * @method string  getSchemaName()
 * @method void    setSchemaName($schemaName)
 */
class Realm extends DBObject implements JsonSerializable
{
    protected $PROP_MAP = array(
        'realm_id'=> 'realmId',
        'module_id' => 'moduleId',
        'name' => 'name',
        'display'=> 'display',
        'schema_schema' => 'schemaName',
        'table_name' => 'tableName'
    );
}
