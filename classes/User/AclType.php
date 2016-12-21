<?php namespace User;

use DBObject;
use Module;


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
 * @method Module getModule()
 * @method void    setModule(Module $module)
 *
 */
class AclType extends DBObject
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
     * @var Module
     */
    protected $module;
}
