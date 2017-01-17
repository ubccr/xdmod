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

    const REALM_ID = 'realm_id';
    const MODULE_ID = 'module_id';
    const NAME = 'name';
    const DISPLAY = 'display';
    const TABLE_NAME = 'table_name';
    const SCHEMA_NAME = 'schema_name';

    protected $realmId;
    protected $moduleId;
    protected $name;
    protected $display;
    protected $tableName;
    protected $schemaName;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::REALM_ID => $this->realmId,
            static::MODULE_ID => $this->moduleId,
            static::NAME => $this->name,
            static::DISPLAY => $this->display,
            static::TABLE_NAME => $this->tableName,
            static::SCHEMA_NAME => $this->schemaName
        );
    }
}
