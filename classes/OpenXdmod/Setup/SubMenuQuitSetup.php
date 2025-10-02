<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Quit setup.
 */
class SubMenuQuitSetup extends SetupItem
{

    /**
     * The parent object for this SubMenuQuitSetup item.
     *
     * @var SubMenuSetupItem
     */
    protected $parent;

    public function __construct($console, SubMenuSetupItem $parent)
    {
        parent::__construct($console);
        $this->parent = $parent;
    }

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->parent->quit();
    }
}
