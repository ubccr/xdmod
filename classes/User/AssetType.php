<?php namespace User;

use DBObject;
use iModule;

/**
 * Class AssetType
 *
 * @package User
 *
 * @method integer getAssetTypeId()
 * @method void    setAssetTypeId($assetTypeId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method iModule getModule()
 * @method void    setModule(iModule $module)
 */
class AssetType extends DBObject implements iAssetType
{

    /**
     * @var integer
     */
    protected $assetTypeId;

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
