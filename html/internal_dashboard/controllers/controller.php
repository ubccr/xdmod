<?php

require_once dirname(__FILE__) . '/../../../configuration/linker.php';

use CCR\DB;
use Models\Services\Users;

@session_start();

$response = array();

$operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '';

if ($operation == 'logout') {

    unset($_SESSION['xdDashboardUser']);
    $response['success'] = true;

    if (isset($_REQUEST['splash_redirect'])) {
        print "<html><head><script language='JavaScript'>top.location.href='../index.php';</script></head></html>";
    } else {
        echo json_encode($response);
    }

    exit;
}


xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), 'xdDashboardUser');

// =====================================================

$pdo = DB::factory('database');

// =====================================================

switch ($operation) {

    case 'enum_account_requests':

        $results = $pdo->query("SELECT id, first_name, last_name, organization, title, email_address, field_of_science, additional_information, time_submitted, status, comments FROM AccountRequests");

        $response['success'] = true;
        $response['count'] = count($results);
        $response['response'] = $results;

        $response['md5'] = md5(json_encode($response));

        if (isset($_POST['md5only'])) {
            unset($response['count']);
            unset($response['response']);
        }

        break;

    case 'update_request':

        $id = \xd_security\assertParameterSet('id');
        $comments = \xd_security\assertParameterSet('comments');

        $results = $pdo->query("SELECT id FROM AccountRequests WHERE id=:id", array('id' => $id));

        if (count($results) == 1) {

            $pdo->execute("UPDATE AccountRequests SET comments=:comments WHERE id=:id", array('comments' => $comments, 'id' => $id));

            $response['success'] = true;

        } else {

            $response['success'] = false;
            $response['message'] = 'invalid id specified';

        }

        break;

    case 'delete_request':

        $id_parameter = \xd_security\assertParameterSet('id', '/^\d+(,\d+)*$/');

        $id_strings = explode(',', $id_parameter);
        $ids = array_map('intval', $id_strings);

        $id_placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $results = $pdo->execute("DELETE FROM AccountRequests WHERE id IN ($id_placeholders)", $ids);

        $response['success'] = true;

        break;

    case 'enum_existing_users':

        $group_filter = \xd_security\assertParameterSet('group_filter');
        $role_filter = \xd_security\assertParameterSet('role_filter');

        $context_filter = isset($_REQUEST['context_filter']) ? $_REQUEST['context_filter'] : '';

        $results = Users::getUsers($group_filter, $role_filter, $context_filter);
        $filtered = array();
        foreach ($results as $user) {
            if ($user['username'] !== 'Public User') {
                $filtered[] = $user;
            }
        }

        $response['success'] = true;
        $response['count'] = count($filtered);
        $response['response'] = $filtered;

        break;

    case 'enum_user_types_and_roles':

        $query = "SELECT id, type, color FROM moddb.UserTypes";

        $results = $pdo->query($query);

        $response['user_types'] = $results;

        $query = "SELECT display AS description, acl_id AS role_id FROM moddb.acls WHERE name != 'pub' ORDER BY description";

        $results = $pdo->query($query);

        $response['user_roles'] = $results;

        $response['success'] = true;

        break;

    case 'enum_user_visits':
    case 'enum_user_visits_export':

        $timeframe = strtolower(\xd_security\assertParameterSet('timeframe'));
        $user_types = explode(',', \xd_security\assertParameterSet('user_types'));

        if ($timeframe !== 'year' && $timeframe !== 'month') {

            $response['success'] = false;
            $response['message'] = 'invalid value specified for the timeframe';

            break;

        }

        $response['success'] = true;
        $response['stats'] = XDStatistics::getUserVisitStats($timeframe, $user_types);

        if ($operation == 'enum_user_visits_export') {

            header("Content-type: application/xls");
            header("Content-Disposition:attachment;filename=\"xdmod_visitation_stats_by_$timeframe.csv\"");

            if (isset($response['stats'][0])) {
                print implode(',', array_keys($response['stats'][0])) . "\n";
            }

            $previous_timeframe = '';

            foreach ($response['stats'] as $entry) {

                if ($previous_timeframe !== $entry['timeframe']) {

                    $previous_timeframe = $entry['timeframe'];
                    print "\n";

                }

                if ($entry['user_type'] == 700) {

                    $entry['user_type'] = 'XSEDE';

                    $u = explode(';', $entry['username']);

                    $entry['username'] = $u[1];

                }

                print implode(',', $entry) . "\n";

            }

            exit;

        }

        break;


    case 'ak_arr':

        $start_date = $_REQUEST['start_date'];
        $end_date = $_REQUEST['end_date'];

        $response['success'] = true;
        $resource['response'] = array(array('x' => array(1, 2, 3), 'y' => array(5, 2, 1)));
        $resource['count'] = count($response['response']);


        break;

    default:

        $response['success'] = false;
        $response['message'] = 'operation not recognized';

        break;

}//switch

// =====================================================

print json_encode($response);
