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

    /**
     * Organization menu.
     *
     * @var Menu
     */
    protected $menu;

    /**
     * Organization config.
     *
     * @var array
     */
    protected $organizations;

    /**
     * True if setup should quit.
     *
     * @var bool
     */
    protected $quit;

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
                '2',
                'List current organizations',
                new ListOrganizationSetup($this->console, $this)
            ),
            new MenuItem(
                's',
                'Save (and return to main menu)',
                new SubMenuSaveSetup($this->console, $this)
            ),
        );

        $this->menu = new Menu($items, $this->console, 'Organization Setup');
    }

    /**
     * Add an organization to the current list
     * 
     * @param array $organization
     */
    public function addOrganization(array $organization)
    {
        $this->organizations[] = $organization;
    }

    /**
     * Return the current list of organizations.
     *
     * @return array
     */
    public function getOrganizations() 
    {
        return $this->organizations;
    }
    
    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->quit = false;

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
