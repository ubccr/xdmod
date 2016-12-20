<?php namespace User;

use DBObject;
use iModule;

/**
 * Class Asset
 *
 * @package User
 *
 * @method integer    getAssetId()
 * @method void       setAssetId($assetId)
 * @method integer    getModuleId()
 * @method void       setModuleId($moduleId)
 * @method integer    getAssetTypeId()
 * @method void       setAssetTypeId($assetTypeId)
 * @method string     getName()
 * @method void       setName($name)
 * @method string     getDisplay()
 * @method void       setDisplay($display)
 * @method boolean    getEnabled()
 * @method void       setEnabled($enabled)
 * @method iModule    getModule()
 * @method void       setModule(iModule $module)
 * @method iAssetType getAssetType()
 * @method void       setAssetType(iAssetType $assetType)
 */
class Asset extends DBObject implements iAsset
{
    /**
     * @var integer
     */
    protected $assetId;

    /**
     * @var integer
     */
    protected $moduleId;

    /**
     * @var integer
     */
    protected $assetTypeId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $display;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var iModule
     */
    protected $module;

    /**
     * @var iAssetType
     */
    protected $assetType;
}
