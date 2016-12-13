<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * Update checker setup.
 */
class UpdateCheckSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->console->displaySectionHeader('Automatic Update Check Setup');

        $this->console->displayMessage(<<<"EOT"
When enabled, Open XDMoD will periodically connect to a remote server to
check if a new version of Open XDMoD is available.  After a new version
has been released you will recieve an email.

You may also subscribe to our mailing list at:
http://listserv.buffalo.edu/cgi-bin/wa?SUBED1=ccr-xdmod-list&A=1
EOT
        );
        $this->console->displayBlankLine();

        $answer = $this->console->prompt(
            'Enable automatic update check?',
            'yes',
            array('yes', 'no')
        );

        $config = $this->getUpdateCheckConfig();

        if ($answer === 'yes') {
            $config['enabled'] = true;

            $config['email'] = $this->console->prompt(
                'Email address:',
                $config['email']
            );

            $config['name'] = $this->console->prompt(
                'Name:',
                $config['name']
            );

            $config['organization'] = $this->console->prompt(
                'Organization:',
                $config['organization']
            );
        } else {
            $config['enabled'] = false;
        }

        $this->saveJsonConfig($config, 'update_check');
    }

    /**
     * Returns the current update check config or default values.
     *
     * @return array
     */
    protected function getUpdateCheckConfig()
    {
        $config = $this->loadJsonConfig('update_check');

        unset($config['enabled']);

        if (!isset($config['email'])) {
            $settings = $this->loadIniConfig('portal_settings');
            $config['email']
                = $settings['general_contact_page_recipient'];
        }

        if (!isset($config['name'])) {
            $config['name'] = '';
        }

        if (!isset($config['organization'])) {
            $org = $this->loadJsonConfig('organization');
            $config['organization'] = $org['name'];
        }

        return $config;
    }
}
