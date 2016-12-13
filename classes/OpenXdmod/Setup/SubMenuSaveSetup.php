<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Resources setup sub-step for saving resources.
 */
class SubMenuSaveSetup extends SetupItem
{

    /**
     * Main resources setup
     *
     * @var SubMenuSetupItem
     */
    protected $parent;

    /**
     * @inheritdoc
     */
    public function __construct(Console $console, SubMenuSetupItem $parent)
    {
        parent::__construct($console);
        $this->parent = $parent;
    }

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->parent->save();
        $this->parent->quit();
    }
}
