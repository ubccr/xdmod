<?php namespace Models;

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
 */
class ModuleVersion extends DBOBject
{
    protected $PROP_MAP = array(
        'module_version_id' => 'moduleVersionId',
        'module_id'=> 'moduleId',
        'name' => 'name',
        'display'=> 'display',
        'version_major' => 'versionMajor',
        'version_minor' => 'versionMinor',
        'version_micro' => 'versionMicro',
        'version_patch' => 'versionPatch',
        'created_on' => 'createdOn',
        'last_modified_on' => 'lastModifiedOn'
    );
}
