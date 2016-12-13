<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Open XDMoD setup menu.
 */
class Menu
{

    /**
     * Menu title.
     *
     * @var null|string
     */
    protected $title;

    /**
     * Menu items.
     *
     * @var MenuItem[]
     */
    protected $items;

    /**
     * Console IO.
     *
     * @var Console
     */
    protected $console;

    /**
     * Constructor.
     *
     * @param MenuItems[] $items The menu's items.
     * @param Console $console The console used to display the menu.
     * @param string $title The menu's title.
     */
    public function __construct(array $items, Console $console, $title = null)
    {
        $this->items   = $items;
        $this->console = $console;
        $this->title   = $title;
    }

    /**
     * Display the menu.
     */
    public function display()
    {
        $this->console->displaySectionHeader($this->title, false);

        $triggerSet = array();

        foreach ($this->items as $item) {
            $trigger = $item->getTrigger();
            $label   = $item->getLabel();
            $this->console->displayMessage("$trigger) $label");
            $triggerSet[] = $trigger;
        }

        $this->console->displayBlankLine();

        $option = $this->console->prompt('Select an option:' , '', $triggerSet);

        $this->getHandlerForTrigger($option)->handle();
    }

    /**
     * Find the handler for the trigger.
     */
    protected function getHandlerForTrigger($trigger)
    {
        foreach ($this->items as $item) {
            if ($item->getTrigger() == $trigger) {
                return $item->getHandler();
            }
        }

        throw new Exception("No handler found for trigger '$trigger'");
    }
}
