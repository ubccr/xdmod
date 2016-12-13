<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Quit setup.
 */
class QuitSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        exit;
    }
}
