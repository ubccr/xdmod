<?php

interface iModuleVersion
{

    /**
     * @return integer
     */
    public function getModuleVersionId();

    /**
     * @param integer $moduleVersionId
     * @return void
     */
    public function setModuleVersionId($moduleVersionId);

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
     * @return iModule
     */
    public function getModule();

    /**
     * @param iModule $module
     * @return void
     */
    public function setModule(iModule $module);

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
     * @return integer
     */
    public function getVersionMajor();

    /**
     * @param integer $versionMajor
     * @return void
     */
    public function setVersionMajor($versionMajor);

    /**
     * @return integer
     */
    public function getVersionMinor();

    /**
     * @param integer $versionMinor
     * @return void
     */
    public function setVersionMinor($versionMinor);

    /**
     * @return integer
     */
    public function getVersionMicro();

    /**
     * @param integer $versionMicro
     * @return void
     */
    public function setVersionMicro($versionMicro);

    /**
     * @return string
     */
    public function getVersionPatch();

    /**
     * @param string $versionPatch
     * @return void
     */
    public function setVersionPatch($versionPatch);

    /**
     * @return string
     */
    public function getCreatedOn();

    /**
     * @param string $createdOn
     * @return void
     */
    public function setCreatedOn($createdOn);

    /**
     * @return string
     */
    public function getLastModifiedOn();

    /**
     * @param string $lastModifiedOn
     * @return void
     */
    public function setLastModifiedOn($lastModifiedOn);
}
