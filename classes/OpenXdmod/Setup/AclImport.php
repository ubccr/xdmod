<?php namespace OpenXdmod\Setup;

abstract class AclImport extends SetupItem
{
    protected $sectionMessage = <<<MSG
Please provide the following information specific to your installation otherwise accept
the defaults provided.

Note: If you would like to execute this script outside of the setup script it is
located at: <xdmod_install_dir>/bin/
MSG;

    /**
     * Handle the setup.
     */
    public function handle()
    {
        $scriptName = $this->scriptName;
        $sectionMessage = $this->sectionMessage.$scriptName;
        $etlSection = $this->defaultSection;

        $configFile = realpath(CONFIG_DIR.'/etl/etl.json');

        $this->console->displaySectionHeader($this->sectionHeader);
        $this->console->displayMessage($sectionMessage);
        $this->console->displayBlankLine();

        $cmd = "$scriptName -g";
        $this->console->displayMessage("$cmd");
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
