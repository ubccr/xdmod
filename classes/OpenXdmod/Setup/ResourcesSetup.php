<?php
/**
 * @author Greg Dean
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use \CCR\Json;

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

        foreach (array('cloud', 'storage') as $realm) {
            if ($this->getRealmSpecificResources($realm)) {
                $this->addRolesFile($realm);
            } else {
                $this->removeRolesFile($realm);
            }
        }
    }

    /**
     * Checks if a roles file exists for the given realm.
     *
     * @param string $realm The name of the realm in all lower-case letters.
     *
     * @return boolean True if the file exists.
     */
    private function rolesFileExists($realm)
    {
        return file_exists(CONFIG_DIR . '/roles.d/' . $realm . '.json');
    }

    /**
     * Checks to see if the roles file in configuration/roles.d matches the
     * roles file in and templates/roles.d for the given realm.
     *
     * @param string $realm The name of the realm in all lower-case letters.
     *
     * @return boolean True if the file exists and is the same as the default.
     */
    private function rolesFileMatches($realm)
    {
        $rolesFile = CONFIG_DIR . '/roles.d/' . $realm . '.json';

        if (!file_exists($rolesFile)) {
            return false;
        }

        return JSON::loadFile(TEMPLATE_DIR . '/roles.d/' . $realm . '.json', false)
            == JSON::loadFile($rolesFile, false);
    }

    /**
     * Copies roles file from templates/roles.d to configuration/roles.d
     * to enable the realm.
     *
     * @param string $realm The name of the realm in all lower-case letters.
     */
    private function addRolesFile($realm)
    {
        if (!$this->rolesFileMatches($realm)) {
            if ($this->rolesFileExists($realm)) {
                $this->console->displayBlankLine();
                $this->console->displayMessage(<<<"EOMSG"
Roles file for the $realm realm exists and does not match the default
roles file for the $realm realm.  No changes will be made to the $realm
roles file.  Please consult the documentation for more information about
the $realm roles file.
EOMSG
                );
                $this->console->displayBlankLine();
                $this->console->prompt('Press ENTER to continue.');
                return;
            }
            $rolesConfigDir = CONFIG_DIR . '/roles.d';
            if (!is_dir($rolesConfigDir)) {
                mkdir($rolesConfigDir);
            }
            $this->console->displayMessage(
                "Enabling $realm realm. Please wait."
            );
            copy(
                TEMPLATE_DIR . '/roles.d/' . $realm . '.json',
                $rolesConfigDir . '/' . $realm . '.json'
            );
            $this->updateAcls();
        }
    }

    /**
     * Remove roles configuration file and update ACLs.
     *
     * @param string $realm The name of the realm in all lower-case letters.
     */
    private function removeRolesFile($realm)
    {
        $rolesFile = CONFIG_DIR . '/roles.d/' . $realm . '.json';

        if (file_exists($rolesFile)) {
            $this->console->displayMessage(
                "Disabling $realm realm.  Please wait."
            );
            unlink($rolesFile);
            $this->updateAcls();
        }
    }

    /**
     * Execute all ACL actions.
     */
    private function updateAcls()
    {
        passthru('acl-config');
    }

    /**
     * Get all of the current resources that belong to a specific realm.
     *
     * Only applies to resource types that contain the name of the realm in the
     * description of the resource type.  For the default resource types this
     * works for both "cloud" and "storage".  Does not work for "jobs" or
     * "supremm".
     *
     * @param string $realm The name of the realm in all lower-case letters.
     *
     * @return array
     */
    private function getRealmSpecificResources($realm)
    {
        $resourceTypeIds = $this->getRealmResourceTypeIds($realm);

        return array_filter(
            $this->resources,
            function ($resource) use ($resourceTypeIds) {
                return in_array(
                    $resource['resource_type_id'],
                    $resourceTypeIds
                );
            }
        );
    }

    /**
     * Get all the resource type IDs for a given realm.
     *
     * Only applies to resource types that contain the name of the realm in the
     * description of the resource type.  For the default resource types this
     * works for both "cloud" and "storage".  Does not work for "jobs" or
     * "supremm".
     *
     * @param string $realm The name of the realm in all lower-case letters.
     *
     * @return array
     */
    private function getRealmResourceTypeIds($realm)
    {
        return array_map(
            function ($type) {
                return $type['id'];
            },
            array_filter(
                json_decode(
                    file_get_contents(CONFIG_DIR . '/resource_types.json'),
                    true
                ),
                function ($type) use ($realm) {
                    return preg_match('/' . $realm . '/i', $type['description'])
                        === 1;
                }
            )
        );
    }
}
