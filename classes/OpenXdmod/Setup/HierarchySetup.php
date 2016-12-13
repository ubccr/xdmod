<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Organization setup.
 */
class HierarchySetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->console->displaySectionHeader('Hierarchy Setup');

        $this->console->displayMessage(<<<"EOT"
Specify the levels (top, middle, and bottom) in your organization which would
be analogous to the following structure:

Top Level: Decanal Unit
Middle Level: Department
Bottom Level: PI Group
EOT
        );
        $this->console->displayBlankLine();

        $items = array(
            'top_level_label'    => 'Top Level Name:',
            'top_level_info'     => 'Top Level Description:',
            'middle_level_label' => 'Middle Level Name:',
            'middle_level_info'  => 'Middle Level Description:',
            'bottom_level_label' => 'Bottom Level Name:',
            'bottom_level_info'  => 'Bottom Level Description:',
        );

        $hierarchy = $this->loadJsonConfig('hierarchy');

        foreach ($items as $key => $prompt) {
            $hierarchy[$key] = $this->console->prompt(
                $prompt,
                $hierarchy[$key]
            );
        }

        $this->saveJsonConfig($hierarchy, 'hierarchy');
    }
}
