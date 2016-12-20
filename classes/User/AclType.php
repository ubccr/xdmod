<?php namespace User;

use DBObject;
use iModule;


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
 * @method iModule getModule()
 * @method void    setModule(iModule $module)
 *
 */
class AclType extends DBObject implements iAclType
{
    /**
     * @var integer
     */
    protected $aclTypeId;

    /**
     * @var integer
     */
    protected $moduleId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $display;

    /**
     * @var iModule
     */
    protected $module;
}
