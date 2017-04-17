<?php namespace User;

use DBObject;
use Module;

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
 */
class AssetType extends DBObject
{
    protected $PROP_MAP = array(
        'asset_type_id' => 'assetTypeId',
        'module_id' => 'moduleId',
        'name' => 'name',
        'display'=> 'display'
    );
}
