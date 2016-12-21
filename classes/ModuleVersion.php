<?php

/**
 * Class ModuleVersion
 *
 * @method integer getModuleVersionId()
 * @method void    setModuleVersionId($moduleVersionId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method integer getVersionMajor()
 * @method void    setVersionMajor($versionMajor)
 * @method integer getVersionMinor()
 * @method void    setVersionMinor($versionMinor)
 * @method integer getVersionMicro()
 * @method void    setVersionMicro($versionMicro)
 * @method string  getVersionPatch()
 * @method void    setVersionPatch($versionPatch)
 * @method string  getCreatedOn()
 * @method void    setCreatedOn($createdOn)
 * @method string  getLastModifiedOn()
 * @method void    setLastModifiedOn($lastModifiedOn)
 * @method Module getModule()
 * @method void    setModule(Module $module)
 */
class ModuleVersion extends DBOBject
{
    /**
     * @var integer
     */
    protected $moduleVersionId;

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
     * @var integer
     */
    protected $versionMajor;

    /**
     * @var integer
     */
    protected $versionMinor;

    /**
     * @var integer
     */
    protected $versionMicro;

    /**
     * @var string
     */
    protected $versionPatch;

    /**
     * @var string
     */
    protected $createdOn;

    /**
     * @var string
     */
    protected $lastModifiedOn;

    /**
     * @var Module
     */
    protected $module;

}
