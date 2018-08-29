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
        // Look up the resource type id for the string that was entered

        $availableTypes = json_decode(file_get_contents(CONFIG_DIR . '/resource_types.json'));

        $resourceTypeId = 0; // Unknown
        foreach ( $availableTypes as $type ) {
            // Note that Console::prompt() expects lowercase values for options
            if ( strtolower($type->abbrev) == $resource['type'] ) {
                $resourceTypeId = $type->id;
                break;
            }
        }

        $this->resources[] = array(
            'resource'         => $resource['resource'],
            'resource_type_id' => $resourceTypeId,
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
     * Save the current list of resources. If a cloud resource exists in the list of resources
     * add the cloud acl files and run acl config commands
     */
    public function save()
    {
        $this->saveJsonConfig($this->resources,     'resources');
        $this->saveJsonConfig($this->resourceSpecs, 'resource_specs');

        $cloud_resources_exist = array_filter($this->resources, function ($resource) {
            return $resource['resource_type_id'] == 5;
        });

        if (!empty($cloud_resources_exist)) {
            $this->addCloudAcls();
        }

        // Any resource type that contains the string "storage" is considered to
        // be a storage resource type.
        $storageResourceTypeIds = array_map(
            function ($type) {
                return $type['id'];
            },
            array_filter(
                json_decode(
                    file_get_contents(CONFIG_DIR . '/resource_types.json'),
                    true
                ),
                function ($type) {
                    return preg_match('/storage/i', $type['description']) === 1;
                }
            )
        );

        $storageResources = array_filter(
            $this->resources,
            function ($resource) use ($storageResourceTypeIds) {
                return in_array(
                    $resource['resource_type_id'],
                    $storageResourceTypeIds
                );
            }
        );

        if (empty($storageResources)) {
            $this->removeStorageRolesConfig();
        } else {
            $this->addStorageRolesConfig();
        }
    }

    /**
     * Checks to see if the cloud.json files in configuration/datawarehouse.d and configuration/roles.d
     * match the cloud.json files in templates/datawarehouse.d and templates/roles.d
     */
    private function doCloudAclFilesMatch()
    {
        $roles_config = CONFIG_DIR . '/roles.d/cloud.json';

        if (!file_exists($roles_config)) {
            return false;
        }

        $roles_config_template = md5(file_get_contents(TEMPLATE_DIR . '/roles.d/cloud.json'));

        return (md5(file_get_contents($roles_config)) == $roles_config_template) ? true : false;
    }

    /**
     * Checks to see if the storage.json file in configuration/roles.d matches
     * the storage.json file in templates/roles.d.
     */
    private function doesStorageRolesFileMatch()
    {
        $rolesConfig = CONFIG_DIR . '/roles.d/storage.json';

        if (!file_exists($rolesConfig)) {
            return false;
        }

        return file_get_contents($rolesConfig)
            === file_get_contents(TEMPLATE_DIR . '/roles.d/storage.json');
    }

    /**
     * Moves cloud.json files from templates/datawarehouse.d and templates/roles.d to configuration/roles.d
     * and configuration/datawarehouse.d and then runs acls-xdmod-management, acl-config and acl-import to
     * enable the Cloud realm.
     */
    private function addCloudAcls()
    {
        $roles_config_dir = CONFIG_DIR . '/roles.d';
        $roles_config_template_dir = TEMPLATE_DIR . '/roles.d';

        if (!$this->doCloudAclFilesMatch()) {

            if (!is_dir($roles_config_dir)) {
                mkdir($roles_config_dir);
            }

            $this->console->displayMessage("Enabling cloud realm. Please wait a few moments.");
            copy(TEMPLATE_DIR . '/roles.d/cloud.json', $roles_config_dir.'/cloud.json');

            $this->updateAcls();
        }
    }

    /**
     * Copy storage roles file if it doesn't exist or is different from the
     * version included and then update ACLs.
     */
    private function addStorageRolesConfig()
    {
        $rolesDir = CONFIG_DIR . '/roles.d';

        if (!$this->doesStorageRolesFileMatch()) {
            if (!is_dir($rolesDir)) {
                mkdir($rolesDir);
            }
            $this->console->displayMessage(
                'Enabling storage realm.  Please wait.'
            );
            copy(
                TEMPLATE_DIR . '/roles.d/storage.json',
                $rolesDir . '/storage.json'
            );
            $this->updateAcls();
        }
    }

    /**
     * Remove storage roles configuration file and update ACLs.
     */
    private function removeStorageRolesConfig()
    {
        $storageRolesConfig = CONFIG_DIR . '/roles.d/storage.json';

        if (file_exists($storageRolesConfig)) {
            $this->console->displayMessage(
                'Disabling storage realm.  Please wait.'
            );
            unlink($storageRolesConfig);
            $this->updateAcls();
        }
    }

    /**
     * Execute all ACL actions.
     */
    private function updateAcls()
    {
        $manageAcls = new AclEtl(['section' => 'acls-xdmod-management']);
        $manageAcls->execute();
        passthru('acl-config');
        $importAcls = new AclEtl(['section' => 'acls-import']);
        $importAcls->execute();
    }
}
