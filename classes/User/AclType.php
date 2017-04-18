<?php namespace User;

use Models\DBObject;

/**
 * Class AclType
 * @package User
 *
 * @method integer getAclTypeId()
 * @method void    setAclTypeId($aclTypeId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 */
class AclType extends DBObject
{
    protected $PROP_MAP = array(
        'acl_type_id' => 'aclTypeId',
        'module_id' => 'moduleId',
        'name' => 'name',
        'display'=> 'display'
    );
}
