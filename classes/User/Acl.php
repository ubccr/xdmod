<?php namespace User;

use DBObject;
use iModule;

/**
 * Class Acl
 *
 * @package User
 *
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
 * @method iModule getModule()
 * @method void    setModule(iModule $module)
 *
 */
class Acl extends DBObject implements iAcl
{

    protected $aclId;
    protected $moduleId;
    protected $aclTypeId;
    protected $name;
    protected $display;
    protected $enabled;
    protected $module;

}

