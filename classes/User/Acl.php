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
    const ACL_ID = 'acl_id';
    const MODULE_ID = 'module_id';
    const ACL_TYPE_ID = 'acl_type_id';
    const NAME = 'name';
    const DISPLAY = 'display';
    const ENABLED = 'enabled';


    protected $aclId;
    protected $moduleId;
    protected $aclTypeId;
    protected $name;
    protected $display;
    protected $enabled;
    protected $module;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            Acl::ACL_ID => $this->aclId,
            Acl::MODULE_ID => $this->moduleId,
            Acl::ACL_TYPE_ID => $this->aclTypeId,
            Acl::NAME => $this->name,
            Acl::DISPLAY => $this->display,
            Acl::ENABLED => $this->enabled
        );
    }
}

