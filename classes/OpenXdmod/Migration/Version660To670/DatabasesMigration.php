<?php

/**
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Migration\Version660To670;

/**
 *
 **/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{

    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $sections = array(
            'acl-xdmod-management' => 'acls-xdmod-management',
            'acl-import' => 'acls-import'
        );
        foreach($sections as $scriptName => $etlSection) {
            $baseDirectory = realpath(BASE_DIR.DIRECTORY_SEPARATOR.'..');
            $configDirectory = CONFIG_DIR;
            $configFile = realpath(CONFIG_DIR.'/etl/etl.json');

            $oldWD = getcwd();
            $binPath = BASE_DIR . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . 'bin';
            $binDirectory = realpath($binPath);

            chdir($binDirectory);

            $params = array(
                '-b',
                $baseDirectory,
                '-d',
                $configDirectory,
                '-c',
                $configFile,
                '-s',
                $etlSection
            );

            $script = realpath($binDirectory . DIRECTORY_SEPARATOR . $scriptName);
            $cmd = "$script ". implode(' ', $params);

            $this->logger->info("Executing $cmd");

            $output = shell_exec($cmd);

            $hadError = strpos($output, 'error') !== false;

            chdir($oldWD);

            if ($hadError == true) {
                $this->logger->debug(<<<MSG
There was an error when attempting to execute the the script with the provided
parameters. Please see the output below, make any corrections necessary and
re-run this setup.

$output
MSG
                );
            } else {
                $this->logger->debug(<<<MSG
The script executed without error.
MSG
                );
                $this->logger->debug($output);
            }
        }
    }
}
