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

    const HIERARCHY_ID = 'hierarchy_id';
    const MODULE_ID = 'module_id';
    const NAME = 'name';

    protected $hierarchyId;
    protected $moduleId;
    protected $name;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::HIERARCHY_ID => $this->hierarchyId,
            static::MODULE_ID => $this->moduleId,
            static::NAME => $this->name
        );
    }
}
