<?php namespace Models;

use DBObject;

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
class Tab extends DBObject
{
    protected $PROP_MAP = array(
        'tab_id' => 'tabId',
        'module_id' => 'moduleId',
        'parent_tab_id'=> 'parentTabId',
        'name' => 'name',
        'display' => 'display',
        'position' => 'position',
        'is_default' => 'isDefault',
        'javascript_class' => 'javascriptClass',
        'javascript_reference' => 'javascriptReference',
        'tooltip' => 'tooltip',
        'user_manual_section_name' => 'userManualSectionName'
    );
}
