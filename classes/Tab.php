<?php

/**
 * Class Tab
 *
 * the 'getters' and 'setters' for this class:
 * @method integer getTabId()
 * @method void    setTabId($tabId)
 * @method integer getModuleId()
 * @method void    setModuleId($moduleId)
 * @method string  getName()
 * @method void    setName($name)
 * @method string  getDisplay()
 * @method void    setDisplay($display)
 * @method integer getPosition()
 * @method void    setPosition($position)
 * @method bool    getIsDefault()
 * @method void    setIsDefault($isDefault)
 * @method string  getJavaScriptClass()
 * @method void    setJavascriptClass($javascriptClass)
 * @method string  getJavascriptReference()
 * @method void    setJavascriptReference($javascriptReference)
 * @method string  getTooltip()
 * @method void    setTooltip($tooltip)
 * @method string  getUserManualSectionName()
 * @method void    setUserManualSectionName($userManualSectionName)
 */
class Tab extends DBObject implements JsonSerializable
{
    const TAB_ID = 'tab_id';
    const MODULE_ID = 'module_id';
    const PARENT_TAB_ID = 'parent_tab_id';
    const NAME = 'name';
    const DISPLAY = 'display';
    const POSITION = 'position';
    const IS_DEFAULT = 'is_default';
    const JAVASCRIPT_CLASS = 'javascript_class';
    const JAVASCRIPT_REFERENCE = 'javascript_reference';
    const TOOLTIP = 'tooltip';
    const USER_MANUAL_SECTION_NAME = 'user_manual_section_name';

    protected $tabId;
    protected $moduleId;
    protected $parentTabId;
    protected $name;
    protected $display;
    protected $position;
    protected $isDefault;
    protected $javascriptClass;
    protected $javascriptReference;
    protected $tooltip;
    protected $userManualSectionName;

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return array(
            static::TAB_ID => $this->tabId,
            static::MODULE_ID => $this->moduleId,
            static::PARENT_TAB_ID => $this->parentTabId,
            static::NAME => $this->name,
            static::DISPLAY => $this->display,
            static::POSITION => $this->position,
            static::IS_DEFAULT => $this->isDefault,
            static::JAVASCRIPT_CLASS => $this->javascriptClass,
            static::JAVASCRIPT_REFERENCE => $this->javascriptReference,
            static::TOOLTIP => $this->tooltip,
            static::USER_MANUAL_SECTION_NAME => $this->userManualSectionName
        );
    }
}
