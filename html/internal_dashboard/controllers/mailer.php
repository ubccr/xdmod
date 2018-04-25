<?php

require_once __DIR__ . '/../../../configuration/linker.php';

use CCR\MailWrapper;
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
            'maintainer_signature' => MailWrapper::getMaintainerSignature(),
            'date'                 => date('l, j F'),
            'site_title'           => \xd_utilities\getConfiguration('general', 'title'),
            'site_address'         => $site_address,
            'product_name'         => MailWrapper::getProductName(),
        ));

        $response['success'] = true;
        $response['content'] = $template->getContents();

        break;
    case 'enum_target_addresses':
        $group_filter = \xd_security\assertParameterSet('group_filter');
        $acl_filter = \xd_security\assertParameterSet('role_filter');

        list($query, $params) = \xd_dashboard\listUserEmailsByGroupAndAcl($group_filter, $acl_filter);

        $results = $pdo->query($query, $params);

        $addresses = array();

        foreach ($results as $r) {
            $addresses[] = $r['email_address'];
        }

        sort($addresses);

        $response['success'] = true;
        $response['count'] = count($addresses);
        $response['response'] = $addresses;

        break;
    case 'send_plain_mail':
        $response['success'] = true;

        $title = \xd_utilities\getConfiguration('general', 'title');

        // Send a copy of the email to the contact page recipient.
        $response['status'] = MailWrapper::sendMail(array(
                                  'body'        => \xd_security\assertParameterSet('message', '/.*/', false),
                                  'subject'     => "[$title] " . \xd_security\assertParameterSet('subject'),
                                  'toAddress'   => \xd_utilities\getConfiguration('general', 'contact_page_recipient'),
                                  'toName'      => 'Undisclosed Recipients',
                                  'fromAddress' => \xd_utilities\getConfiguration('general', 'contact_page_recipient'),
                                  'fromName'    => $title,
                                  'bcc'         => \xd_security\assertParameterSet('target_addresses')
                              ));
        break;
    default:
        $response['success'] = false;
        $response['message'] = "Operation '$operation' not recognized";
        break;
}

print json_encode($response);

