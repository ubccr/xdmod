<?php namespace Models;

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
class GroupBy extends DBObject
{

    protected $PROP_MAP = array(
        'group_by_id'=> 'groupById',
        'module_id' => 'moduleId',
        'name' => 'name',
        'display' => 'display',
        'schema_name'=> 'schemaName',
        'table_name' => 'tableName',
        'alias'=> 'alias',
        'id_column' => 'idColumn',
        'name_column' => 'nameColumn',
        'shortname_column' => 'shortnameColumn',
        'order_id_column' => 'orderIdColumn',
        'fk_column' => 'fkColumn'
    );
}
