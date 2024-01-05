<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Open XDMoD setup menu item.
 */
class MenuItem
{

    /**
     * Menu item handler.
     *
     * @var SetupItem
     */
    protected $handler;

    /**
     * Constructor.
     *
     * @param string $trigger The text trigger.
     * @param string $label The description of the item.
     * @param SetupItem $handler The corresponding setup item.
     */
    public function __construct(/**
     * The string that should be used to trigger the item.
     *
     * This is typically a number or a single letter (e.g. "q" for
     * quit).
     */
    protected $trigger, /**
     * Menu item text label.
     */
    protected $label, SetupItem $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Label accessor.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Label accessor.
     *
     * @return SetupItem
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Trigger accessor.
     *
     * @return string
     */
    public function getTrigger()
    {
        return $this->trigger;
    }
}
