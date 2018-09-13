<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

/**
 * General setup.
 */
class GeneralSetup extends SetupItem
{

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $settings = $this->loadIniConfig('portal_settings');

        $this->console->displaySectionHeader('General Setup');

        $this->console->displayMessage(<<<"EOT"
The default Open XDMoD configuration creates an Apache virtual host on
port 8080.  If you change or remove the port number (and use port 80 or
443) you will need to change the Apache configuration as well.
EOT
        );
        $this->console->displayBlankLine();

        $settings['general_site_address'] = $this->console->prompt(
            'Site Address:',
            $settings['general_site_address']
        );

        $settings['general_site_address'] = \xd_utilities\ensure_string_ends_with(
            $settings['general_site_address'],
            '/'
        );

        $settings['general_user_manual']
            = $settings['general_site_address']
            . 'user_manual/';

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
The email address you specify will be used as the destination for any
messages sent via the portal contact page as well as account requests.  In
addition, any log messages configured for delivery via e-mail will be sent to
this address.
EOT
        );
        $this->console->displayBlankLine();

        $settings['general_contact_page_recipient'] = $this->console->prompt(
            'Email Address:',
            $settings['general_contact_page_recipient']
        );

        // Copy the email address.
        $settings['general_tech_support_recipient']
            = $settings['general_debug_recipient']
            = $settings['mailer_sender_email']
            = $settings['logger_email_from']
            = $settings['logger_email_to']
            = $settings['general_contact_page_recipient'];


        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
Java and PhantomJS are required by the report generator for constructing
reports.  Setup will attempt to detect the presence of java, and
phantomjs on your system.
EOT
        );
        $this->console->displayBlankLine();

        if ($settings['reporting_java_path'] == '') {
            $settings['reporting_java_path']
                = exec('which java 2>/dev/null');
        }

        $settings['reporting_java_path'] = $this->console->prompt(
            'Java Path:',
            $settings['reporting_java_path']
        );

        if ($settings['reporting_javac_path'] == '') {
            $settings['reporting_javac_path']
                = exec('which javac 2>/dev/null');
        }

        $settings['reporting_javac_path'] = $this->console->prompt(
            'Javac Path:',
            $settings['reporting_javac_path']
        );

        if ($settings['reporting_phantomjs_path'] == '') {
            $settings['reporting_phantomjs_path']
                = exec('which phantomjs 2>/dev/null');
        }

        $settings['reporting_phantomjs_path'] = $this->console->prompt(
            'PhantomJS Path:',
            $settings['reporting_phantomjs_path']
        );

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
You have the ability to specify a logo which will appear on the upper-right
hand region of the portal.  It is advised that the height of the logo be at
most 32 pixels.

The logo is referenced by its absolute path on the file system and must
be readable by the user/group your web server is running as.
EOT
        );
        $this->console->displayBlankLine();

        $settings['general_center_logo'] = $this->console->prompt(
            'Center Logo Path:',
            $settings['general_center_logo']
        );

        if (!empty($settings['general_center_logo'])) {
            $settings['general_center_logo_width'] = $this->console->prompt(
                'Center Logo Width:',
                $settings['general_center_logo_width']
            );
        }

        if (empty($settings['general_application_secret'])) {
            $settings['general_application_secret'] = bin2hex(\random_bytes(256));
            $settings['general_email_token_expiration'] = '600';
        }

        $this->saveIniConfig($settings, 'portal_settings');
    }
}
