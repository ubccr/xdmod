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

$filters = array(
    'type' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => RESTRICTION_REPORT_CHART_TYPE)
    ),
    'ref' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => RESTRICTION_REPORT_CHART_REF)
    ),
    'did' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => RESTRICTION_REPORT_CHART_DID)
    ),
    'start' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => RESTRICTION_REPORT_DATE)
    ),
    'end' => array(
        'filter' => FILTER_VALIDATE_REGEXP,
        'options' => array('regexp' => RESTRICTION_REPORT_DATE)
    ),
);

try {
    $request = Request::createFromGlobals();
    $user = Authentication::authenticateUser($request);

    $request = filter_var_array($_REQUEST, $filters, false);

    if ($user === null) {
        throw new AccessDeniedHttpException('User not authenticated');
    }

    if (!isset($request['type'])) {
        throw new Exception("Thumbnail type not set");
    }

    if (!isset($request['ref'])) {
        throw new Exception("Thumbnail reference not set");
    }

    switch ($request['type']) {
        case 'chart_pool':
        case 'volatile':
            $num_matches = preg_match('/^(\d+);(\d+)$/', $request['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            $user_id = $matches[1];

            if (isset($request['start']) && isset($request['end'])) {
                $insertion_rank = array(
                    'rank' => $matches[2],
                    'start_date' => $request['start'],
                    'end_date' => $request['end'],
                    'did' => isset($request['did']) ? $request['did'] : '',
                );
            } else {
                $insertion_rank = array(
                    'rank' => $matches[2],
                    'did' => isset($request['did']) ? $request['did'] : '',
                );
            }

            break;

        case 'report':
            $num_matches = preg_match('/^((\d+)-(.+));(\d+)$/', $request['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            $user_id = $matches[2];
            $insertion_rank =  array('report_id' => $matches[1], 'ordering' => $matches[4]);
            break;

        case 'cached':
            $num_matches = preg_match('/^((\d+)-(.+));(\d+)$/', $request['ref'], $matches);

            if ($num_matches == 0) {
                throw new Exception("Invalid thumbnail reference set");
            }

            if (!isset($request['start']) || !isset($request['end'])) {
                throw new Exception("Start and end dates not set");
            }

            $valid_start = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $request['start']);
            $valid_end = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $request['end']);

            if (($valid_start * $valid_end) == 0) {
                throw new Exception("Invalid start and/or end date supplied");
            }

            $user_id = $matches[2];

            $insertion_rank = array(
                'report_id' => $matches[1],
                'ordering' => $matches[4],
                'start_date' => $request['start'],
                'end_date' => $request['end'],
            );
            break;

        default:
            throw new Exception("Invalid thumbnail type value supplied: " . $request['type']);
            break;

    } // switch($request['type'])

    if ($user_id !== $user->getUserID()) {
        throw new AccessDeniedHttpException('Invalid user id');
    }

    $rm = new XDReportManager($user);

    header("Content-Type: image/png");

    $blob = $rm->fetchChartBlob($request['type'], $insertion_rank);

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
