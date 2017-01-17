<?php

/**
 * Class GroupBy
 *
 * @method integer getGroupById()
 * @method void    setGroupById($groupById)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method string  getSchemaName()
 * @method void    setSchemaName($schemaName)
 * @method string  getTableName()
 * @method void    setTableName($tableName)
 * @method string  getAlias()
 * @method void    setAlias($alias)
 * @method string  getIdColumn()
 * @method void    setIdColumn($idColumn)
 * @method string  getNameColumn()
 * @method void    setNameColumn($nameColumn)
 * @method string  getShortnameColumn()
 * @method void    setShortnameColumn($shortnameColumn)
 * @method string  getOrderIdColumn()
 * @method void    setOrderIdColumn($orderIdColumn)
 * @method string  getFkColumn()
 * @method void    setFkColumn($fkColumn)
 */
class GroupBy extends DBObject implements JsonSerializable
{

    const GROUP_BY_ID = 'group_by_id';
    const MODULE_ID = 'module_id';
    const NAME = 'name';
    const DISPLAY = 'display';
    const SCHEMA_NAME = 'schema_name';
    const TABLE_NAME = 'table_name';
    const ALIAS = 'alias';
    const ID_COLUMN = 'id_column';
    const NAME_COLUMN = 'name_column';
    const SHORTNAME_COLUMN = 'shortname_column';
    const ORDER_ID_COLUMN = 'order_id_column';
    const FK_COLUMN = 'fk_column';

    protected $groupById;
    protected $moduleId;
    protected $name;
    protected $display;
    protected $schemaName;
    protected $tableName;
    protected $alias;
    protected $idColumn;
    protected $nameColumn;
    protected $shortnameColumn;
    protected $orderIdColumn;
    protected $fkColumn;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::GROUP_BY_ID => $this->groupById,
            static::MODULE_ID => $this->moduleId,
            static::NAME => $this->name,
            static::DISPLAY => $this->display,
            static::SCHEMA_NAME => $this->schemaName,
            static::TABLE_NAME => $this->tableName,
            static::ALIAS => $this->alias,
            static::ID_COLUMN => $this->idColumn,
            static::NAME_COLUMN => $this->nameColumn,
            static::SHORTNAME_COLUMN => $this->shortnameColumn,
            static::ORDER_ID_COLUMN => $this->orderIdColumn,
            static::FK_COLUMN => $this->fkColumn,
        );
    }
}
