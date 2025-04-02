<?php
/**
 * @author Greg Dean
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Configuration\XdmodConfiguration;

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
        // Look up the resource type id for the string that was entered

        $availableTypes = XdmodConfiguration::assocArrayFactory('resource_types.json', CONFIG_DIR)['resource_types'];
        $availableResourceAllocationTypes = XdmodConfiguration::assocArrayFactory('resource_allocation_types.json', CONFIG_DIR)['resource_allocation_types'];
        $organizations = XdmodConfiguration::assocArrayFactory('organization.json', CONFIG_DIR);

        $typeAbbrev = 'UNK';
        $resourceAllocationTypeAbbrev = 'UNK';
        $organizationAbbrev = 'UNK';

        foreach($availableTypes as $abbrev => $type) {
            if (strtolower($abbrev) === $resource['type']) {
                $typeAbbrev = $abbrev;
                break;
            }
        }

        foreach($availableResourceAllocationTypes as $abbrev => $type) {
            if (strtolower($abbrev) === $resource['resource_allocation_type']) {
                $resourceAllocationTypeAbbrev = $abbrev;
                break;
            }
        }

        foreach($organizations as $abbrev => $type) {
            if (strtolower($type['abbrev']) === $resource['organization']) {
                $organizationAbbrev = $type['abbrev'];
                break;
            }
        }

        $this->resources[] = array(
            'resource'                  => $resource['resource'],
            'resource_type'             => $typeAbbrev,
            'name'                      => $resource['name'],
            'resource_allocation_type'  => $resourceAllocationTypeAbbrev,
            'organization'              => $organizationAbbrev
        );

        $this->resourceSpecs[] = array(
            'resource'            => $resource['resource'],
            'start_date'          => $resource['start_date'],
            'cpu_node_count'      => $resource['cpu_node_count'],
            'cpu_processor_count' => $resource['cpu_processor_count'],
            'cpu_ppn'             => $resource['cpu_ppn'],
            'gpu_node_count'      => $resource['gpu_node_count'],
            'gpu_processor_count' => $resource['gpu_processor_count'],
            'gpu_ppn'             => $resource['gpu_ppn']
        );
    }

    /**
     * Save the current list of resources.
     *
     * If a realm specific resource exists in the list of resources then add
     * the roles file for that realm and run the ACL actions.  If there are no
     * realm specific resources in the list then remove the roles file for that
     * realm and run the ACL actions.
     */
    public function save()
    {
        $this->saveJsonConfig($this->resources,     'resources');
        $this->saveJsonConfig($this->resourceSpecs, 'resource_specs');
    }
}
