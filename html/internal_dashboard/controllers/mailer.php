<?php

require_once __DIR__ . '/../../../configuration/linker.php';

use CCR\DB;
use Xdmod\EmailTemplate;

@session_start();

xd_security\enforceUserRequirements(
    array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE),
    'xdDashboardUser'
);

$pdo = DB::factory('database');

$operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '';

$response = array();

switch ($operation) {
    case 'enum_presets':
        $response['presets'] = array(
            'Maintenance',
            'New Release',
        );

        $response['success'] = true;
        $response['count'] = count($response['presets']);

        break;
    case 'fetch_preset_message':
        $preset = \xd_security\assertParameterSet('preset');

        // Convert human-friendly name to template name (lower case with
        // spaces replaced by underscores).
        $preset = strtolower($preset);
        $preset = str_replace(' ', '_', $preset);

        $template = new EmailTemplate($preset);

        $version       = xd_versioning\getPortalVersion(true);
        $contact_email = xd_utilities\getConfiguration('general', 'contact_page_recipient');
        $site_address  = xd_utilities\getConfigurationUrlBase('general', 'site_address');

        $template->apply(array(
            'version'              => $version,
            'contact_email'        => $contact_email,
            'organization'         => ORGANIZATION_NAME,
            'maintainer_signature' => MailTemplates::getMaintainerSignature(),
            'date'                 => date('l, j F'),
            'site_title'           => MailTemplates::getSiteTitle(),
            'site_address'         => $site_address,
            'product_name'         => MailTemplates::getProductName(),
        ));

        $response['success'] = true;
        $response['content'] = $template->getContents();

        break;
    case 'enum_target_addresses':
        $group_filter = \xd_security\assertParameterSet('group_filter');
        $role_filter = \xd_security\assertParameterSet('role_filter');

        $query = \xd_dashboard\deriveUserEnumerationQuery($group_filter, $role_filter, '', true);

        $results = $pdo->query($query);

        $addresses = array();

        foreach ($results as $r) {
            $addresses[] = $r['email_address'];
        }

        $addresses = array_unique($addresses);

        sort($addresses);

        $response['success'] = true;
        $response['count'] = count($addresses);
        $response['response'] = $addresses;

        break;
    case 'send_plain_mail':
        $target_addresses = \xd_security\assertParameterSet('target_addresses');
        $message = \xd_security\assertParameterSet('message', '/.*/', false);
        $subject = \xd_security\assertParameterSet('subject');

        $response['success'] = true;

        $sender = strtolower(\xd_utilities\getConfiguration('mailer', 'sender_email'));

        $mail = MailWrapper::initPHPMailer($sender);
        $title = \xd_utilities\getConfiguration('general', 'title');
        $mail->Subject = "[$title] $subject";

        // Send a copy of the email to the contact page recipient.
        $contact = \xd_utilities\getConfiguration('general', 'contact_page_recipient');
        $mail->addAddress($contact, 'Undisclosed Recipients');
        $mail->setFrom($contact, $title);

        $bcc_emails = explode(',', $target_addresses);

        foreach ($bcc_emails as $b) {
            $mail->addBCC($b);
        }

        $mail->Body = $message;

        $response['status'] = $mail->send();

        break;
    default:
        $response['success'] = false;
        $response['message'] = "Operation '$operation' not recognized";
        break;
}

print json_encode($response);

