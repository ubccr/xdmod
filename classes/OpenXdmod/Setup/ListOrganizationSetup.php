<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Configuration\XdmodConfiguration;
use DateTime;

/**
 * Resources setup sub-step for listing resources.
 */
class ListOrganizationSetup extends SetupItem
{

    /**
     * Main resources setup
     *
     * @var OrganizationSetup
     */
    protected $parent;

    /**
     * @inheritdoc
     */
    public function __construct(Console $console, OrganizationSetup $parent)
    {
        parent::__construct($console);
        $this->parent = $parent;
    }

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->console->displaySectionHeader('Resources Added');

        $organizations = $this->parent->getOrganizations();

        if (count($organizations) == 0) {
            $this->console->displayMessage('No resources have been added.');
            $this->console->displayBlankLine();
        }

        foreach ($organizations as $organization) {
            $this->console->displayMessage('Organization Name: ' . $organization['name']);
            $this->console->displayMessage('Organization Abbreviation: ' . $organization['abbrev']);
            $this->console->displayMessage(str_repeat('-', 72));
            $this->console->displayBlankLine();
        }

        $this->console->prompt('Press ENTER to continue.');
    }
}
