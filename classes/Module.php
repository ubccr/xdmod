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
 * @method ModuleVersion    getCurrentVersion()
 * @method void             setCurrentVersion(ModuleVersion $currentVersion)
 * @method ModuleVersion[]  getVersions()
 * @method void             setVersions(array $versions)
 *
 */
class Module extends DBObject
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
     * @var ModuleVersion
     */
    protected $currentVersion;

    /**
     * @var ModuleVersion[]
     */
    protected $versions;


}
