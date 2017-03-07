<?php namespace User;

use DBObject;
use JsonSerializable;
use Module;

/**
 * Class Acl
 *
 * Represents a named grouping under which a selection of 'Assets' can be
 * secured and to which a number of users can be belong. Data for this class
 * is stored in the 'acls' table while the relationship of user to acl is stored
 * in 'user_acls'.
 *
 * @package User
 *
 * The 'getters' and 'setters' for this class:
 * @method integer getAclId()
 * @method void    setAclId($aclId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method integer getAclTypeId()
 * @method void    setAclTypeId($aclTypeId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method boolean getEnabled()
 * @method void    setEnabled($enabled)
 * @method Module  getModule()
 * @method void    setModule(Module $module)
 *
 */
class Acl extends DBObject implements JsonSerializable
{
    protected $PROP_MAP = array(
        'acl_id' => 'aclId',
        'module_id' => 'moduleId',
        'acl_type_id' => 'aclTypeId',
        'name' => 'name',
        'display' => 'display',
        'enabled' => 'enabled'
    );
}

