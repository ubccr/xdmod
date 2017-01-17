<?php
/**
 * @author Joseph P. White <jpwhite4@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * A sub-menu is a setup item that has one or more setup items
 * under it.
 */
abstract class SubMenuSetupItem extends SetupItem
{
    /**
     * Callback that should save the configuration properties
     * that are manipulated by this submenu
     */
    abstract public function save();

    /**
     * Callback that should cause the handle() function to return
     * on the next tick.
     */
    abstract public function quit();
}
