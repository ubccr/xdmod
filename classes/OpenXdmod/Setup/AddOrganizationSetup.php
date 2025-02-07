<?php
/**
 * @author Greg Dean <gmdean@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Resources setup sub-step for adding resources.
 */
class AddOrganizationSetup extends SetupItem
{

    /**
     * Main resources setup
     *
     * @var ResourcesSetup
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
      $organization = [];

      $organization['name'] = $this->console->prompt(
          'Organization Name:'
      );

      $organization['abbrev'] = $this->console->prompt(
          'Organization Abbreviation:'
      );

      $this->parent->addOrganization($organization);
    }
}
