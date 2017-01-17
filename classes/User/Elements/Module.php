<?php

namespace User\Elements;

/**
 * This class represents the information needed to describe a module in
 * the portal for presentation to the user.
 *
 * @author Amin Ghadersohi
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */
class Module extends \Common\Identity
{

    /**
     * Is this a default module?
     *
     * @var bool
     */
    private $_is_default = false;

    /**
     * Module title.
     *
     * @var string
     */
    private $_title;

    /**
     * The module's relative position.
     *
     * @var int
     */
    private $_positon;

    /**
     * The module's Javascript 'class'.
     *
     * @var string
     **/
    private $_javascriptClass;

    /**
     * The module's Javascript static reference.
     *
     * @var string
     **/
    private $_javascriptReference;

    /**
     * The module's tooltip.
     *
     * @var string
     **/
    private $_tooltip;


    /**
     * The term that is fed to the user manual search box when the user has this
     * module selected while clicking on the User Manual.
     *
     * @var string
     **/
    private $_userManualSectionName;

    /**
     * Constructor.
     *
     * @param array $config the array to be used in configuring this Module.
     *                      the following keys are currently required:
     *                        - name       : the name of the module.
     *                        - title      : the title of the module.
     *                        - position   : the position ( integer ) of the module.
     *                      the following keys are currently optional:
     *                        - is_default : if the module is 'default'.
     *                        - permitted_modules : an array of permited modules.
     *                        - cls               : javascript class that corresponds to this module.
     *                        - ref               : javascript static reference for the 'cls' property.
     *                        - tooltip           : the tooltip that should be displayed when the tab is receives the hover event.
     *                        - userManualSectionName : the term that is meant to identify which User Manual Section corresponds with this module.
     */
    public function __construct(array $config)
    {
        if (!isset($config['name'])) {
            throw new Exception("'name' property required for module construction.");
        }
        if (!isset($config['title'])) {
            throw new Exception("'title' property required for module construction.");
        }
        if (!isset($config['position'])) {
            throw new Exception("'position' property required for module construction.");
        }

        parent::__construct(\xd_utilities\array_get($config, 'name'));

        $this->_is_default          = \xd_utilities\array_get($config, 'default');
        $this->_title               = \xd_utilities\array_get($config, 'title');
        $this->_position            = \xd_utilities\array_get($config, 'position');
        $this->_permitted_modules   = \xd_utilities\array_get($config, 'permitted_modules');
        $this->_javascriptClass     = \xd_utilities\array_get($config, 'javascriptClass');
        $this->_javascriptReference = \xd_utilities\array_get($config, 'javascriptReference');
        $this->_tooltip             = \xd_utilities\array_get($config, 'tooltip');
        $this->_userManualSectionName = \xd_utilities\array_get($config, 'userManualSectionName');
    }

    /**
     * Is this a default module?
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->_is_default;
    }

    /**
     * Get the module's title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Get the module's relative position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->_position;
    }

    /**
     * Get the module's permited modules
     *
     * @return int
     */
    public function getPermittedModules()
    {
        return $this->_permitted_modules;
    }

    /**
     * Get this module's javascriptClass.
     *
     * @return string
     **/
    public function getJavascriptClass()
    {
        return $this->_javascriptClass;
    }

    /**
     * Get this module's javascriptReference.
     *
     * @return string
     **/
    public function getJavascriptReference()
    {
        return $this->_javascriptReference;
    }

    /**
     * Get this module's tooltip.
     *
     * @return string
     **/
    public function getTooltip()
    {
        return $this->_tooltip;
    }

    /**
     * Get this module's userManualSectionName property.
     *
     * @return string
     **/
    public function getUserManualSectionName()
    {
        return $this->_userManualSectionName;
    }
}
