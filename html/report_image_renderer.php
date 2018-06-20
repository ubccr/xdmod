<?php
/**
 * Report Image Renderer
 * Entrypoint for acquiring static representations of charts in XDMoD
 * (Cache support enabled)
 *
 * @author Ryan J. Gentner
 */

require_once dirname(__FILE__) . '/../configuration/linker.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Rest\Utilities\Authentication;

$emptyBlobs = array('fa0a056630132658467089d779e0e177', '02477ed21bfccd97c1dc2b18d5f1916a');

try {
    $request = Request::createFromGlobals();
    $user = Authentication::authenticateUser($request);

    if ($user === null) {
        throw new AccessDeniedHttpException('User not authenticated');
    }

    if (!isset($_REQUEST['type'])) {
        throw new Exception("Thumbnail type not set");
    }

    if (!isset($_REQUEST['ref'])) {
        throw new Exception("Thumbnail reference not set");
    }

    switch ($_REQUEST['type']) {
        case 'chart_pool':
        case 'volatile':
            $num_matches = preg_match('/^(\d+);(\d+)$/', $_REQUEST['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            $user_id = $matches[1];

            if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
                $insertion_rank = array(
                    'rank' => $matches[2],
                    'start_date' => $_REQUEST['start'],
                    'end_date' => $_REQUEST['end'],
                    'did' => isset($_REQUEST['did']) ? $_REQUEST['did'] : '',
                );
            } else {
                $insertion_rank = array(
                    'rank' => $matches[2],
                    'did' => isset($_REQUEST['did']) ? $_REQUEST['did'] : '',
                );
            }

            break;

        case 'report':
            $num_matches = preg_match('/^((\d+)-(.+));(\d+)$/', $_REQUEST['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            $user_id = $matches[2];
            $insertion_rank =  array('report_id' => $matches[1], 'ordering' => $matches[4]);
            break;

        case 'cached':
            $num_matches = preg_match('/^((\d+)-(.+));(\d+)$/', $_REQUEST['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            if (!isset($_REQUEST['start']) || !isset($_REQUEST['end'])) {
                throw new Exception("Start and end dates not set");
            }

            $valid_start = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $_REQUEST['start']);
            $valid_end = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $_REQUEST['end']);

            if (($valid_start * $valid_end) == 0) {
                throw new Exception("Invalid start and/or end date supplied");
            }

            $user_id = $matches[2];

            $insertion_rank = array(
                'report_id' => $matches[1],
                'ordering' => $matches[4],
                'start_date' => $_REQUEST['start'],
                'end_date' => $_REQUEST['end'],
            );
            break;

        default:
            throw new Exception("Invalid thumbnail type value supplied: " . $_REQUEST['type']);
            break;

    } // switch($_REQUEST['type'])

    if ($user_id !== $user->getUserID()) {
        throw new AccessDeniedHttpException('Invalid user id');
    }

    $rm = new XDReportManager($user);

    header("Content-Type: image/png");

    $blob = $rm->fetchChartBlob($_REQUEST['type'], $insertion_rank);

    $image_data_header = substr($blob, 0, 8);

    if ($image_data_header != "\x89PNG\x0d\x0a\x1a\x0a") {
        throw new Exception($blob);
    }

    if (in_array(md5($blob), $emptyBlobs)) {
        readfile(dirname(__FILE__) . '/gui/images/report_thumbnail_no_data.png');
        exit;
    }

    print $blob;

} catch (Exception $e) {
    header("Content-Type: image/png");
    $unique_id = uniqid();
    $im = imagecreatefrompng(dirname(__FILE__) . '/gui/images/report_thumbnail_error.png');
    imagestring($im, 5, 20, 505, 'Error Code: ' . $unique_id, imagecolorallocate($im, 100, 100, 100));
    imagepng($im);

    // RE-throwing this exception will allow exceptions.log to record the exception message
    throw new UniqueException($unique_id, $e);
}
