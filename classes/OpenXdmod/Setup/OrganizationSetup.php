<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Organization setup.
 */
class OrganizationSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->console->displaySectionHeader('Organization Setup');

        $org = $this->loadJsonConfig('organization');

        $org['name'] = $this->console->prompt(
            'Organization Name:',
            $org['name']
        );

        $org['abbrev'] = $this->console->prompt(
            'Organization Abbreviation:',
            $org['abbrev']
        );

        $this->saveJsonConfig($org, 'organization');
    }
}
