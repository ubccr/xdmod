<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

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

        foreach ($resources as $resource) {
            $specs = $this->getSpecsForResource($resource['resource']);

            $this->console->displayMessage('Resource: ' . $resource['resource']);
            $this->console->displayMessage('Name: ' . $resource['name']);
            $this->console->displayMessage('Node count: ' . $specs['nodes']);
            $this->console->displayMessage('Processor count: ' . $specs['processors']);
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
