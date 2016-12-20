<?php

interface iModule {

    /**
     * @return integer
     */
    public function getModuleId();

    /**
     * @param integer $moduleId
     * @return void
     */
    public function setModuleId($moduleId);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return void
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getDisplay();

    /**
     * @param string $display
     * @return void
     */
    public function setDisplay($display);

    /**
     * @return boolean
     */
    public function getEnabled();

    /**
     * @param boolean $enabled
     * @return void
     */
    public function setEnabled($enabled);

    /**
     * @return integer
     */
    public function getCurrentVersionId();

    /**
     * @param integer $currentVersionId
     * @return void
     */
    public function setCurrentVersionId($currentVersionId);

    /**
     * @return iModuleVersion
     */
    public function getCurrentVersion();

    /**
     * @param iModuleVersion $currentVersion
     * @return void
     */
    public function setCurrentVersion(iModuleVersion $currentVersion);

    /**
     * @return iModuleVersion[]
     */
    public function getVersions();

    /**
     * @param iModuleVersion[] $versions
     * @return void
     */
    public function setVersions(array $versions);

}
