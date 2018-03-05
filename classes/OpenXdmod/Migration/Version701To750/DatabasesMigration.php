<?php

/**
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 **/
namespace OpenXdmod\Migration\Version701To750;

/**
 * Ensure that the tables / data exists that will support the Acl subsystem
 * going into version 7.5.
 **/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::__construct
     **/
    public function __construct($currentVersion, $newVersion)
    {
        parent::__construct($currentVersion, $newVersion);
    }

    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $this->migrateTables();
        $this->populateTables();
    }

    /**
     * Attempt to migrate the acl tables into the current system database. This process will
     * utilize the script acl-xdmod-management which in turn utilizes the ETL overseer.
     *
     * @return void
     **/
    public function migrateTables()
    {
        $scripts = array(
            'acl-xdmod-management' => array()
        );
        $this->runScripts($scripts);
    }

    /**
     * Attempt to execute the scripts: acl-import and acl-config which will
     * populate the acl related tables with the correct information for this
     * installation based on the following configuration (directories of) files:
     *     - CONF_DIR/datawarehouse.[d|json]
     *     - CONF_DIR/roles.[d|json]
     *     - CONF_DIR/hierarchies.[d|json]
     *
     * @return void
     **/
    public function populateTables()
    {
        $scripts = array(
            'acl-config' => array(),
            'acl-import' => array()
        );

        $this->runScripts($scripts);
    }

    public function runScripts(array $scripts)
    {
        foreach ($scripts as $scriptName => $params) {
            $cmd = "$scriptName ". implode(' ', $params);
            $this->logger->info("Executing $cmd");

            $output = shell_exec($cmd);

            $hadError = strpos($output, 'error') !== false;

            if ($hadError == true) {
                $this->logger->err(<<<MSG
There was an error when attempting to execute the the script with the provided
parameters. Please see the output below, make any corrections necessary and
re-run this setup.

$output
MSG
                );
                exit(1);
            } else {
                $this->logger->notice("The script executed without error.");
                $this->logger->notice($output);
            }
        }
    }
}
