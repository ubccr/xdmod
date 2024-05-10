<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Configuration\XdmodConfiguration;
use DateTime;

/**
 * Resources setup sub-step for adding resources.
 */
class AddResourceSetup extends SetupItem
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
        $typeOptions = array();
        $typeDescriptionText = "";
        $availableTypes = XdmodConfiguration::assocArrayFactory('resource_types.json', CONFIG_DIR)['resource_types'];
        $resourceAllocationTypeOptions = array();
        $resourceAllocationTypeDescriptionText = "";
        $availableResourceAllocationTypes = XdmodConfiguration::assocArrayFactory('resource_allocation_types.json', CONFIG_DIR)['resource_allocation_types'];
        $gpu_nodes = 0;
        $gpus = 0;
        $gpu_ppn = 0;

        foreach ( $availableTypes as $abbrev => $type ) {
            if ( 'UNK' == $abbrev ) {
                continue;
            }
            // Note that Console::prompt() expects lowercase values for options
            $typeOptions[] = strtolower($abbrev);
            $typeDescriptionText .= sprintf("%-10s - %s", $abbrev, $type['description']) . PHP_EOL;
        }

        foreach ( $availableResourceAllocationTypes as $abbrev => $type ) {
            if ( 'UNK' == $abbrev ) {
                continue;
            }
            // Note that Console::prompt() expects lowercase values for options
            $resourceAllocationTypeOptions[] = strtolower($abbrev);
            $resourceAllocationTypeDescriptionText .= sprintf("%-10s - %s", $abbrev, $type['description']) . PHP_EOL;
        }

        $this->console->displaySectionHeader('Add A New Resource');

        $this->console->displayMessage(<<<"EOT"
The resource name you enter should match the name used by your resource
manager.  This is the resource name that you will need to specify during
the shredding process.  If you are using Slurm this must match the
cluster name used by Slurm.

Available resource types are:
$typeDescriptionText

Available resource allocation types are:
$resourceAllocationTypeDescriptionText
EOT
        );
        $this->console->displayBlankLine();

        $resource = $this->console->prompt('Resource Name:');
        $name     = $this->console->prompt('Formal Name:');
        $type     = $this->console->prompt('Resource Type:', 'hpc', $typeOptions);
        $resource_allocation_type     = $this->console->prompt('Resource Allocation Type:', 'cpu', $resourceAllocationTypeOptions);
        $resource_start_date = $this->getResourceStartDate();

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
The number of nodes and processors are used to determine resource
utilization.

If this is a storage resource you may enter 0 for the number of nodes
and processors.
EOT
        );
        $this->console->displayBlankLine();

        $nodes = $this->console->prompt('How many CPU nodes does this resource have?');
        if (strlen($nodes) === 0 || !is_numeric($nodes)) { $nodes = 1; }

        $cpus = $this->console->prompt('How many total CPU processors (cpu cores) does this resource have?');
        if (strlen($cpus) === 0 || !is_numeric($cpus)) { $cpus = 1; }

        $ppn = ($nodes == 0) ? 0 : $cpus / $nodes;

        if ($resource_allocation_type == 'gpu' || $resource_allocation_type == 'gpunode') {
            $gpu_nodes = $this->console->prompt('How many GPU nodes does this resource have?');
            if (empty($gpu_nodes) || !is_numeric($gpu_nodes)) {
                $gpu_nodes = 0;
            }

            $gpus = $this->console->prompt('How many total GPUs does this resource have?');
            if (empty($gpus) || !is_numeric($gpus)) {
                $gpus = 0;
            }

            $gpu_ppn = ($gpu_nodes == 0) ? 0 : $gpus / $gpu_nodes;
        }

        $this->parent->addResource(
            array(
                'resource'   => $resource,
                'name'       => $name,
                'type'       => $type,
                'resource_allocation_type'    => $resource_allocation_type,
                'cpu_processor_count' => (int)$cpus,
                'cpu_node_count'      => (int)$nodes,
                'cpu_ppn'             => (int)$ppn,
                'gpu_processor_count' => (int)$gpus,
                'gpu_node_count'      => (int)$gpu_nodes,
                'gpu_ppn'             => (int)$gpu_ppn,
                'start_date'          => $resource_start_date
            )
        );
    }

    /**
    * Prompt the user for the start date of a resource. If left empty it will default to the current day.
    * It also validates that any date string entered is a valid date and in the correct format.
    *
    * @return string $resource_start_date string
    */
    public function getResourceStartDate()
    {
        $resource_start_date  = $this->console->prompt('Resource Start Date, in YYYY-mm-dd format', date('Y-m-d'));
        $resource_start_date_parsed = DateTime::createFromFormat("Y-m-d", $resource_start_date);

        if (empty($resource_start_date)) {
            $resource_start_date_parsed = DateTime::createFromFormat("Y-m-d", date('Y-m-d'));
        }
        elseif ($resource_start_date_parsed === false) {
            $this->console->displayMessage("The date you entered is in the wrong format. Please enter the date in YYYY-mm-dd format.");
            return $this->getResourceStartDate();
        }

        return $resource_start_date_parsed->format('Y-m-d');
    }
}
