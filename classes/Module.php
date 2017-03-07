<?php

/**
 * Class Module
 *
 * @method integer          getModuleId()
 * @method void             setModuleId($moduleId)
 * @method integer          getCurrentVersionId()
 * @method void             setCurrentVersionId($currentVersionId)
 * @method string           getName()
 * @method void             setName($name)
 * @method string           getDisplay()
 * @method void             setDisplay($display)
 * @method boolean          getEnabled()
 * @method void             setEnabled($enabled)
 */
class Module extends DBObject
{
    protected $PROP_MAP = array(
        'module_id'=> 'moduleId',
        'current_version_id' => 'currentVersionId',
        'name' => 'name',
        'display' => 'display',
        'enabled' => 'enabled'
    );
}
