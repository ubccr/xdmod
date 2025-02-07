<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Organization setup.
 */
class OrganizationSetup extends SubMenuSetupItem
{

    protected $organizations;

    public function __construct(Console $console)
    {
        parent::__construct($console);

        $items = array(
            new MenuItem(
                '1',
                'Add a new organzation',
                new AddOrganizationSetup($this->console, $this)
            ),
            new MenuItem(
                's',
                'Save (and return to main menu)',
                new SubMenuSaveSetup($this->console, $this)
            ),
        );

        $this->menu = new Menu($items, $this->console, 'Organization Setup');
    }

    public function addOrganization(array $organization)
    {
        $this->organizations[] = $organization;
    }
    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->quit = false;

        //$this->console->displaySectionHeader('Organization Setup');
        $this->organizations = $this->loadJsonConfig('organization');

        while (!$this->quit) {
            $this->menu->display();
        }
    }

    public function quit()
    {
        $this->quit = true;
    }

    public function save()
    {
        $this->saveJsonConfig($this->organizations, 'organization');
    }
}
