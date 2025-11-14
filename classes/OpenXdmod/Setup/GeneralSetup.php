<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Setup;

use Xdmod\Template;

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
The template Apache configuration file uses a virtual host
listening on HTTPS port 443. The Site Address specified
here should match the settings in the Apache configuration.
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
Chromium is required by the report generator for constructing reports.  Setup
will attempt to detect the presence of chromium on your system.
EOT
        );
        $this->console->displayBlankLine();

        if ($settings['reporting_chromium_path'] == '') {
            $chromiumPath = '/usr/lib64/chromium-browser/headless_shell';
            $settings['reporting_chromium_path'] = is_executable($chromiumPath) ? $chromiumPath : '';
        }

        $settings['reporting_chromium_path'] = $this->console->prompt(
            'Chromium Path:',
            $settings['reporting_chromium_path']
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

        $this->console->displayBlankLine();
        $this->console->displayMessage(<<<"EOT"
This release of XDMoD features an optional replacement for the summary
tab that is intended to provide easier access to XDMoD's many features.
Detailed information is available at https://open.xdmod.org/dashboard.html
EOT
        );
        $this->console->displayBlankLine();
        $settings['features_user_dashboard'] = $this->console->prompt(
            'Enable Dashboard Tab?',
            $settings['features_user_dashboard'],
            array('on', 'off')
        );

        $this->saveIniConfig($settings, 'portal_settings');

        $envTemplate = new Template('env');
        $envTemplate->apply([
            'app_secret' => hash('sha512', time())
        ]);
        $this->saveTemplate($envTemplate, BASE_DIR . '/.env');

        $cmdBase = 'APP_ENV=prod APP_DEBUG=0';
        $console = BIN_DIR .'/console';

        // Make sure to clear the cache before dumping the dotenv so we start clean.
        $this->executeCommand("$cmdBase $console cache:clear");

        // Dump dotenv data so we don't read .env each time in prod.
        // Note: this means that if you want to start debugging stuff you'll need to delete the generated .env.
        $this->executeCommand("$cmdBase $console dotenv:dump");
    }
}
