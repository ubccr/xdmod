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
    public function __construct(array $items, Console $console, /**
     * Menu title.
     */
    protected $title = null)
    {
        $this->items   = $items;
        $this->console = $console;
    }

    /**
     * Display the menu.
     */
    public function display(): void
    {
        $this->console->displaySectionHeader($this->title);

        $triggerSet = [];

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

        throw new \Exception("No handler found for trigger '$trigger'");
    }
}
