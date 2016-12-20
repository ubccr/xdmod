<?php namespace User;

use iModule;

interface iAsset {

    /**
     * @return integer
     */
    public function getAssetId();

    /**
     * @param integer $assetId
     * @return void
     */
    public function setAssetId($assetId);

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
     * @return integer
     */
    public function getAssetTypeId();

    /**
     * @param integer $assetTypeId
     * @return void
     */
    public function setAssetTypeId($assetTypeId);

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
     * @return iModule
     */
    public function getModule();

    /**
     * @param iModule $module
     * @return void
     */
    public function setModule(iModule $module);

    /**
     * @return iAssetType
     */
    public function getAssetType();

    /**
     * @param iAssetType $assetType
     * @return void
     */
    public function setAssetType(iAssetType $assetType);
}
