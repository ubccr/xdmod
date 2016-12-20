<?php namespace User;

use iModule;

interface iAcl
{

    /**
     * @return integer
     */
    public function getAclId();

    /**
     * @param integer $aclId
     * @return void
     */
    public function setAclId($aclId);

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
    public function getAclTypeId();

    /**
     * @param integer $aclTypeId
     * @return void
     */
    public function setAclTypeId($aclTypeId);

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


}
