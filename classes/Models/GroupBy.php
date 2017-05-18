<?php namespace Models;

/**
 * Class GroupBy
 *
 * @method integer getGroupById()
 * @method void    setGroupById($groupById)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getRealmId()
 * @method void    setRealmId($realmId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method string  getDescription()
 * @method void    setDescription($description)
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
 * @method string  getClazz()
 * @method void    setClazz($clazz)
 */
class GroupBy extends DBObject
{

    protected $PROP_MAP = array(
        'group_by_id'=> 'groupById',
        'module_id' => 'moduleId',
        'realm_id' => 'realmId',
        'name' => 'name',
        'display' => 'display',
        'description' => 'description',
        'schema_name'=> 'schemaName',
        'table_name' => 'tableName',
        'alias'=> 'alias',
        'id_column' => 'idColumn',
        'name_column' => 'nameColumn',
        'shortname_column' => 'shortnameColumn',
        'order_id_column' => 'orderIdColumn',
        'fk_column' => 'fkColumn',
        'class' => 'clazz'
    );
}
