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
 */
class Asset extends DBObject
{
    protected $PROP_MAP = array(
        'asset_id' => 'assetId',
        'module_id' => 'moduleId',
        'asset_type_id' => 'assetTypeId',
        'name' => 'name',
        'display' => 'display',
        'enabled' => 'enabled'
    );

}
