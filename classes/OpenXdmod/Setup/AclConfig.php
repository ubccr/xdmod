<?php

/**
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Setup;

class AclConfig extends SetupItem
{
    protected $sectionHeader = 'Acl Database Configuration';
    protected $sectionMessage = <<<MSG
Verifying that the information contained in the following files are valid:
    - CONFIG_DIR/datawarehouse.json
    - CONFIG_DIR/datawarehouse.d/*.json
    - CONFIG_DIR/roles.json
    - CONFIG_DIR/roles.d/*.json
    - CONFIG_DIR/hierarchies.json
    - CONFIG_DIR/hierarchies.d/*.json

Populating the appropriate Acl related tables from the information found in said
configuration files.

Note: If you would like to execute this script outside of the setup script it is
located at: <xdmod_install_dir>/bin/acl-config
MSG;
    protected $scriptName = 'acl-config';

    public function handle()
    {
        $this->console->displaySectionHeader($this->sectionHeader);
        $this->console->displayMessage($this->sectionMessage);
        $this->console->displayBlankLine();

        $cmd = $this->scriptName;
        $this->console->displayMessage("This may take a minute or two...");

        $output = shell_exec($cmd);

        $hadError = strpos($output, 'error') !== false;

        if ($hadError == true) {
            $this->console->displayBlankLine();
            $this->console->displayMessage(<<<MSG
There was an error when attempting to execute the the script with the provided
parameters. Please see the output below, make any corrections necessary and
re-run this setup.

$output
MSG
            );
            $this->console->prompt('Press Any Key To Continue');
        } else {
            $this->console->displayBlankLine();
            $this->console->displayMessage(<<<MSG
The script executed without error.
MSG
            );
            $displayOutput = $this->console->promptBool(
                'Do you want to see the output?',
                false
            );
            if (true === $displayOutput) {
                $this->console->displayMessage($output);
                $this->console->prompt('Press Any Key To Continue.');
            }
        }
    }
}
