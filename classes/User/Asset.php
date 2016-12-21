<?php namespace User;

use DBObject;
use Module;

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
 * @method Module     getModule()
 * @method void       setModule(Module $module)
 * @method AssetType  getAssetType()
 * @method void       setAssetType(AssetType $assetType)
 */
class Asset extends DBObject
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
     * @var Module
     */
    protected $module;

    /**
     * @var AssetType
     */
    protected $assetType;
}
