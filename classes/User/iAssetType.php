<?php namespace User;

use iModule;

interface iAssetType
{
    /**
     * @return integer
     */
    public function getAssetTypeId();

    /**
     * @param integer $assetTypeId
     * @return void
     */
    public function setAssetTypeId($assetTypeId);

    /**
     * @return integer
     */
    public function getModuleId();

    /**
     * @param integer $moduleId
     * @return integer
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
     * @return string
     */
    public function getModule();

    /**
     * @param iModule $module
     * @return void
     */
    public function setModule(iModule $module);
}
