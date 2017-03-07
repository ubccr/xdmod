<?php

/**
 * Class Hierarchy
 *
 * @method integer getHierarchyId()
 * @method void    setHierarchyId($hierarchyId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getName()
 * @method void    setName($name)
 */
class Hierarchy extends DBObject implements JsonSerializable
{

    protected $PROP_MAP = array(
        'hierarchy_id'=> 'hierarchyId',
        'module_id'=> 'moduleId',
        'name' => 'name'
    );
}
