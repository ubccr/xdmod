<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Resources setup.
 */
class ResourcesSetup extends SubMenuSetupItem
{

    /**
     * Resources menu.
     *
     * @var Menu
     */
    protected $menu;

    /**
     * Resource config.
     *
     * @var array
     */
    protected $resources;

    /**
     * Resource specs config.
     *
     * @var array
     */
    protected $resourceSpecs;

    /**
     * True if setup should quit.
     *
     * @var bool
     */
    protected $quit;

    /**
     * @inheritdoc
     */
    public function __construct(Console $console)
    {
        parent::__construct($console);

        $items = array(
            new MenuItem(
                '1',
                'Add a new resource',
                new AddResourceSetup($this->console, $this)
            ),
            new MenuItem(
                '2',
                'List entered resources',
                new ListResourcesSetup($this->console, $this)
            ),
            new MenuItem(
                's',
                'Save (and return to main menu)',
                new SubMenuSaveSetup($this->console, $this)
            ),
        );

        $this->menu = new Menu($items, $this->console, 'Resources Setup');
    }

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->quit = false;

        $this->resources     = $this->loadJsonConfig('resources');
        $this->resourceSpecs = $this->loadJsonConfig('resource_specs');

        while (!$this->quit) {
            $this->menu->display();
        }
    }

    /**
     * Return the current list of resources.
     *
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Return the current list of resource specs.
     *
     * @return array
     */
    public function getResourceSpecs()
    {
        return $this->resourceSpecs;
    }

    /**
     * Quit this setup step at the next opportunity.
     */
    public function quit()
    {
        $this->quit = true;
    }

    /**
     * Add a resource to the current list.
     *
     * @param array $resource
     */
    public function addResource(array $resource)
    {
        $this->resources[] = array(
            'resource'         => $resource['resource'],
            'resource_type_id' => 1,
            'name'             => $resource['name'],
        );

        $this->resourceSpecs[] = array(
            'resource'         => $resource['resource'],
            'processors'       => $resource['processors'],
            'nodes'            => $resource['nodes'],
            'ppn'              => $resource['ppn'],
        );
    }

    /**
     * Save the current list of resources.
     */
    public function save()
    {
        $this->saveJsonConfig($this->resources, 'resources');
        $this->saveJsonConfig($this->resourceSpecs, 'resource_specs');
    }
}
