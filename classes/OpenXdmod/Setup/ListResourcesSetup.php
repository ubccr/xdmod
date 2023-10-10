<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Configuration\XdmodConfiguration;
use DateTime;

/**
 * Resources setup sub-step for listing resources.
 */
class ListResourcesSetup extends SetupItem
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
    public function __construct(Console $console, ResourcesSetup $parent)
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

        $resources = $this->parent->getResources();

        if (count($resources) == 0) {
            $this->console->displayMessage('No resources have been added.');
            $this->console->displayBlankLine();
        }

        $availableTypes = XdmodConfiguration::assocArrayFactory('resource_types.json', CONFIG_DIR)['resource_types'];
        $availableResourceAllocationTypes = XdmodConfiguration::assocArrayFactory('resource_allocatable_types.json', CONFIG_DIR)['resource_allocatable_types'];

        foreach ($resources as $resource) {
            $specs = $this->getSpecsForResource($resource['resource']);

            // Look up the resource type in the list of available types

            $resourceType = 'UNK';
            foreach ( $availableTypes as $name => $type ) {
                if ( $name === $resource['resource_type'] ) {
                    // Note that Console::prompt() expects lowercase values for options
                    $resourceType = strtolower($name);
                    break;
                }
            }

            foreach ( $availableResourceAllocationTypes as $name => $type ) {
                if ( $name === $resource['allocation_type'] ) {
                    // Note that Console::prompt() expects lowercase values for options
                    $resourceAllocatableType = strtolower($name);
                    break;
                }
            }

            $this->console->displayMessage('Resource: ' . $resource['resource']);
            $this->console->displayMessage('Name: ' . $resource['name']);
            $this->console->displayMessage('Type: ' . $resourceType);
            $this->console->displayMessage('Resource Allocation Type: ' . $resourceAllocatableType);
            $this->console->displayMessage('CPU Node count: ' . $specs['cpu_node_count']);
            $this->console->displayMessage('CPU Processor count: ' . $specs['cpu_processor_count']);
            $this->console->displayMessage('GPU Node count: ' . $specs['gpu_node_count']);
            $this->console->displayMessage('GPU Processor count: ' . $specs['gpu_processor_count']);
            $this->console->displayMessage('Resource Start Date ' . $specs['start_date']);
            $this->console->displayMessage(str_repeat('-', 72));
            $this->console->displayBlankLine();
        }

        $this->console->prompt('Press ENTER to continue.');
    }

    /**
     * Returns the current specs for the given resource.
     *
     * @param string $resource The resource identifier.
     *
     * @return array The current resource specs.
     */
    private function getSpecsForResource($resource)
    {

        // Default placeholder values to use if no specs are found. An
        // end date timestamp is added to the specs for ease of
        // comparing the end date with that of other specs.
        $currentSpecs = array(
            'nodes' => '',
            'processors' => '',
            'end_date_ts' => 0,
        );

        foreach ($this->parent->getResourceSpecs() as $specs) {
            if ($specs['resource'] !== $resource) {
                continue;
            }

            // Any specs with no end date must be the most recent.
            if (!isset($specs['end_date'])) {
                return $specs;
            }

            $endDate = new DateTime($specs['end_date']);
            $endDateTs = $endDate->getTimestamp();

            // Replace the current specs if one with a more recent end
            // date is found.
            if ($endDateTs > $currentSpecs['end_date_ts']) {
                $currentSpecs = $specs;
                $currentSpecs['end_date_ts'] = $endDateTs;
            }
        }

        // Remove the timstamp since it technically isn't part of the
        // resource specs.
        unset($currentSpecs['end_date_ts']);

        return $currentSpecs;
    }
}
