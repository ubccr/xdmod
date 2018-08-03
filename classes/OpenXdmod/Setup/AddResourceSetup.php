<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

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
        $availableTypes = json_decode(file_get_contents(CONFIG_DIR . '/resource_types.json'), true);

        usort(
            $availableTypes,
            function ($a, $b) {
                return strcmp($a['abbrev'], $b['abbrev']);
            }
        );

        foreach ( $availableTypes as $type ) {
            if ( 'UNK' == $type['abbrev'] ) {
                continue;
            }
            // Note that Console::prompt() expects lowercase values for options
            $typeOptions[] = strtolower($type['abbrev']);
            $typeDescriptionText .= sprintf("%-10s - %s", $type['abbrev'], $type['description']) . PHP_EOL;
        }

        $this->console->displaySectionHeader('Add A New Resource');

        $this->console->displayMessage(<<<"EOT"
The resource name you enter should match the name used by your resource
manager.  This is the resource name that you will need to specify during
the shredding process.  If you are using Slurm this must match the
cluster name used by Slurm.

Available resource types are:
$typeDescriptionText
EOT
        );
        $this->console->displayBlankLine();

        $resource = $this->console->prompt('Resource Name:');
        $name     = $this->console->prompt('Formal Name:');
        $type     = $this->console->prompt('Resource Type:', 'hpc', $typeOptions);

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
The number of nodes and processors are used to determine resource
utilization.
EOT
        );
        $this->console->displayBlankLine();

        $nodes = $this->console->prompt('How many nodes does this resource have?');
        if (empty($nodes) || !is_numeric($nodes)) { $nodes = 1; }

        $cpus = $this->console->prompt('How many total processors (cpu cores) does this resource have?');
        if (empty($cpus) || !is_numeric($cpus)) { $cpus = 1; }

        $ppn = $cpus / $nodes;

        $this->parent->addResource(
            array(
                'resource'   => $resource,
                'name'       => $name,
                'type'       => $type,
                'processors' => (int)$cpus,
                'nodes'      => (int)$nodes,
                'ppn'        => (int)$ppn,
            )
        );
    }
}
