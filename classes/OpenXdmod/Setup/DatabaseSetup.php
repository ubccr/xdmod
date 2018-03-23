<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use TimePeriodGenerator;
use CCR\DB\MySQLHelper;
use CCR\DB;

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
        } catch (Exception $e) {
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

        $command = 'php ' . DATA_DIR . '/tools/etl/etl_overseer.php '
            . ' -p ' . 'hpcdb-modw.bootstrap';
        $pipes = array();
        $process = proc_open(
            $command,
            array(
                0 => array('file', '/dev/null', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $pipes
        );
        if (!is_resource($process)) {
            $this->console->displayBlankLine();
            $this->console->displayMessage('Failed to create initialize databases:');
            $this->console->displayBlankLine();
            $this->console->displayMessage('Unable execute command:');
            $this->console->displayMessage("\t" . $command);
            $this->console->displayBlankLine();
            $this->console->displayMessage('Details:');
            $this->console->displayMessage(print_r(error_get_last(), true));
            $this->console->displayBlankLine();

            $this->console->prompt('Press ENTER to continue.');

            return;
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $return_value = proc_close($process);

        if ($return_value != 0) {
            $this->console->displayBlankLine();
            $this->console->displayMessage('Failed to create initialize databases:');
            $this->console->displayBlankLine();
            $this->console->displayMessage('Unable execute command:');
            $this->console->displayBlankLine();
            $this->console->displayMessage($command);
            $this->console->displayBlankLine();
            $this->console->displayMessage('returned:' . $return_value);
            $this->console->displayMessage('stdout:');
            $this->console->displayMessage($out);
            $this->console->displayMessage('stderr:');
            $this->console->displayMessage($err);
            $this->console->displayBlankLine();
            $this->console->prompt('Press ENTER to continue.');
            throw new \Exception("$command returned $return_value, stdout:  stderr: $err");
        }

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

        /**
         *  ETLv2 database bootstrap end
         */

        $aclSetup = new AclSetup($this->console);
        $aclSetup->handle();

        $aclConfig = new AclConfig($this->console);
        $aclConfig->handle();

        $aclImport = new AclImportXdmod($this->console);
        $aclImport->handle();
    }
}
