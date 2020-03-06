<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use TimePeriodGenerator;
use CCR\DB\MySQLHelper;
use CCR\DB;
use CCR\Log;
use ETL\Utilities;

/**
 * Database setup.
 */
class DatabaseSetup extends DatabaseSetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $conf = array(
            'console' => true,
            'consoleLogLevel' => Log::WARNING
        );

        $logger = Log::factory('xdmod-setup', $conf);

        $settings = $this->loadIniConfig('portal_settings');

        $this->console->displaySectionHeader('Database Setup');

        $this->console->displayMessage(<<<"EOT"
Please provide the information required to connect to your MySQL database
server.  A user will be created using the username and password you provide.

NOTE: The database password cannot include single quote characters (') or
double quote characters (").
EOT
        );
        $this->console->displayBlankLine();

        $settings['db_host'] = $this->console->prompt(
            'DB Hostname or IP:',
            $settings['database_host']
        );

        $settings['db_port'] = $this->console->prompt(
            'DB Port:',
            $settings['database_port']
        );

        $settings['db_user'] = $this->console->prompt(
            'DB Username:',
            $settings['database_user']
        );

        $settings['db_pass'] = $this->console->silentPrompt(
            'DB Password:'
        );

        while (
               strpos($settings['db_pass'], "'") !== false
            || strpos($settings['db_pass'], '"') !== false
        ) {
            $this->console->displayMessage('Invalid password!');
            $settings['db_pass'] = $this->console->silentPrompt(
                'DB Password:'
            );
        }

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
Please provide the password for the administrative account that will be
used to create the MySQL user and databases.
EOT
        );
        $this->console->displayBlankLine();

        $adminUsername = $this->console->prompt(
            'DB Admin Username:',
            'root'
        );

        $adminPassword = $this->console->silentPrompt(
            'DB Admin Password:'
        );

        try {
            $databases = array(
                'mod_shredder',
                'mod_hpcdb',
                'moddb',
                'modw',
                'modw_aggregates',
                'modw_filters',
                'mod_logger',
            );

            $this->createDatabases(
                $adminUsername,
                $adminPassword,
                $settings,
                $databases
            );
        } catch (\Exception $e) {
            $this->console->displayBlankLine();
            $this->console->displayMessage('Failed to create databases:');
            $this->console->displayBlankLine();
            $this->console->displayMessage($e->getMessage());
            $this->console->displayBlankLine();
            $this->console->displayMessage($e->getTraceAsString());
            $this->console->displayBlankLine();
            $this->console->displayMessage('Settings file not saved!');
            $this->console->displayBlankLine();
            $this->console->prompt('Press ENTER to continue.');
            return;
        }

        // Copy DB info to each section.
        $db_sections = array(
            'logger',
            'database',
            'datawarehouse',
            'shredder',
            'hpcdb',
        );

        foreach ($db_sections as $section) {
            $settings[$section . '_host'] = $settings['db_host'];
            $settings[$section . '_port'] = $settings['db_port'];
            $settings[$section . '_user'] = $settings['db_user'];
            $settings[$section . '_pass'] = $settings['db_pass'];
        }

        $this->saveIniConfig($settings, 'portal_settings');

        /**
         *  ETLv2 database bootstrap start
         */
        Utilities::runEtlPipeline(array(
            'xdb-bootstrap',
            'jobs-xdw-bootstrap',
            'xdw-bootstrap-storage',
            'shredder-bootstrap',
            'staging-bootstrap',
            'hpcdb-bootstrap',
            'acls-xdmod-management',
            'gateways.bootstrap'
        ), $logger);


        $aggregationUnits = array(
            'day',
            'month',
            'quarter',
            'year'
        );

        foreach ($aggregationUnits as $aggUnit) {
            $tpg = TimePeriodGenerator::getGeneratorForUnit($aggUnit);
            $tpg->generateMainTable(DB::factory('datawarehouse'), new \DateTime('2000-01-01'), new \DateTime('2038-01-18'));
        }

        passthru('acl-config');
    }
}
