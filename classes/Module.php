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
 * @method iModuleVersion   getCurrentVersion()
 * @method void             setCurrentVersion(iModuleVersion $currentVersion)
 * @method iModuleVersion[] getVersions()
 * @method void             setVersions(array $versions)
 *
 */
class Module extends DBObject implements iModule
{
    /**
     * @var integer
     */
    protected $moduleId;

    /**
     * @var integer
     */
    protected $currentVersionId;

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
     * @var iModuleVersion
     */
    protected $currentVersion;

    /**
     * @var iModuleVersion[]
     */
    protected $versions;


}
