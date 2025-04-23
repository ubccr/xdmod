<?php

declare(strict_types=1);

namespace Access\Controller;

use DataWarehouse\Access\ReportGenerator;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use XDReportManager;
use XDUser;
use function xd_response\buildError;

/**
 * This class implements the functionality contained in the html/controllers/report_builder.php file and supports
 * the features required by the "Reports" tab.
 *
 */
class ReportBuilderController extends BaseController
{
    private static $emptyBlobs = ['fa0a056630132658467089d779e0e177', '02477ed21bfccd97c1dc2b18d5f1916a'];

    /**
     * @Route("/controllers/report_builder.php", methods={"POST", "GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation');

        switch ($operation) {
            case 'build_from_template':
                $templateId = $this->getStringParam($request, 'template_id');
                return $this->getReportFromTemplate($request, $templateId);
            case 'download_report':
                return $this->downloadReport($request);
            case 'enum_available_charts':
                return $this->getAvailableCharts($request);
            case 'enum_reports':
                return $this->getReports($request);
            case 'enum_templates':
                return $this->getTemplates($request);
            case 'fetch_report_data':
                $reportId = $this->getStringParam($request, 'selected_report', true);
                return $this->getReportData($request, $reportId);
            case 'get_new_report_name':
                return $this->getNewReportName($request);
            case 'get_preview_data':
                return $this->getPreviewData($request);
            case 'remove_chart_from_pool':
                return $this->removeChartFromPool($request);
            case 'remove_report_by_id':
                return $this->removeReportsById($request);
            case 'save_report':
                return $this->saveReport($request);
            case 'send_report':
                return $this->sendReport($request);
        }

        return $this->json([]);
    }

    /**
     * @Route("/reports/builder/list", methods={"GET"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getReports(Request $request): Response
    {
        try {
            $user = \xd_security\detectUser([XDUser::PUBLIC_USER]);
        } catch(Exception $e) {
            return $this->json(buildError($e), 401);
        }

        $reportManager = new \XDReportManager($user);

        return $this->json([
            'status' => 'success',
            'queue'  => $reportManager->fetchReportTable()
        ]);
    }

    /**
     * @Route("/reports/builder/charts", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getAvailableCharts(Request $request): Response
    {
        try {
            $user = \xd_security\detectUser([XDUser::PUBLIC_USER]);
        } catch(Exception $e) {
            return $this->json(buildError($e), 401);
        }

        $reportManager = new \XDReportManager($user);
        return $this->json([
            'status' => 'success',
            'queue'  => $reportManager->fetchChartPool()
        ]);
    }

    /**
     * @Route("/reports/builder/templates/{templateId}", methods={"POST"})
     * @param Request $request
     * @param string $templateId
     * @return Response
     * @throws Exception
     */
    public function getReportFromTemplate(Request $request, string $templateId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $template = \XDReportManager::retrieveReportTemplate($user, $templateId);
        $parameters = $request->request->all();
        $template->buildReportFromTemplate($parameters);
        return $this->json(['success' => true]);
    }

    /**
     * @Route("/reports/builder/send", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function sendReport(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);

        $buildOnly = $this->getBooleanParam($request, 'build_only');
        $reportId = $this->getStringParam($request, 'report_id', false, null, ReportGenerator::REPORT_ID_REGEX);
        $exportFormat = $this->getStringParam($request, 'export_format', false, \XDReportManager::DEFAULT_FORMAT);

        $buildResponse = $reportManager->buildReport($reportId, $exportFormat);
        $workingDir = $buildResponse['template_path'];
        $reportFileName = $buildResponse['report_file'];
        $responseData = [
            'action'     => 'send_report',
            'build_only' => $buildOnly
        ];

        if ($buildOnly) {
            $responseData['report_loc'] = basename($workingDir);
            $responseData['message'] = 'Report built successfully<br />';
            $responseData['success'] = true;
            $responseData['report_name'] = sprintf('%s.%s', $reportManager->getReportName($reportId, true), $exportFormat);
            return $this->json($responseData);
        }

        $mailStatus = $reportManager->mailReport($reportId, $reportFileName, '', $buildResponse);
        $destinationAddress = $reportManager->getReportUserEmailAddress($reportId);
        $message = $mailStatus ? sprintf('Report built and sent to <br /><b>%s</b>', $destinationAddress) : 'Problem mailing the report';

        return $this->json([
            'message' => $message,
            'success' => $mailStatus
        ]);
    }

    /**
     * @Route("/reports/builder/download", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function downloadReport(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $reportLoc = $this->getStringParam($request, 'report_loc');
        if (empty($reportLoc)) {
            return $this->json([
                'success' => false,
                'message' => 'report_loc is a required parameter.'

            ]);
        }
        $format = $this->getStringParam($request, 'format');
        if (empty($format)) {
            return $this->json([
                'success' => false,
                'message' => 'format is a required parameter.'
            ]);
        }

        $reportLoc = $this->getStringParam($request, 'report_loc', true, null, ReportGenerator::REPORT_TMPDIR_REGEX);
        $format = $this->getStringParam($request, 'format', true, null, ReportGenerator::REPORT_FORMATS_REGEX);

        if (!\XDReportManager::isValidFormat($format)) {
            throw new BadRequestHttpException('Invalid format specified');
        }

        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);

        $reportId = preg_replace('/(.+)-(.+)-(.+)/', '$1-$2', $reportLoc);
        $workingDirectory = sys_get_temp_dir() . '/' . $reportLoc;

        $reportFile = $workingDirectory . '/' . $reportId . '.' . $format;
        if (!file_exists($reportFile)) {
            throw new BadRequestHttpException('The report you are referring to does not exist.');
        }

        $reportName = $reportManager->getReportName($reportId, true) . '.' . $format;
        $headers = [
            'Content-Type'        => \XDReportManager::resolveContentType($format),
            'Content-Disposition' => sprintf('inline;filename="%s"', $reportName)
        ];
        $contents = file_get_contents($reportFile);
        return new Response($contents, 200, $headers);
    }

    /**
     * @Route("/reports/builder/preview", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getPreviewData(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());

        $reportId = $this->getStringParam($request, 'report_id', true);
        $token = $this->getStringParam($request, 'token', true);
        $chartsPerPage = $this->getIntParam($request, 'charts_per_page', true);

        $reportManager = new \XDReportManager($user);
        $charts = $reportManager->getPreviewData($reportId, $token, $chartsPerPage);

        return $this->json([
            'report_id' => $reportId,
            'success'   => true,
            'charts'    => $charts
        ]);
    }

    /**
     * @Route("/reports/builder/name", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getNewReportName(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);
        return $this->json([
            'success'     => true,
            'report_name' => $reportManager->generateUniqueName()
        ]);
    }

    /**
     * @Route("/reports/builder/save", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function saveReport(Request $request): Response
    {
        $phase = $this->getStringParam($request, 'phase', true, null, '/^create|update$/');
        $map = [];

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);
        switch ($phase) {
            case 'create':
                $reportId = sprintf('%s-%s', $user->getUserID(), time());
                break;
            case 'update':
                $reportId = $this->getStringParam($request, 'report_id', false, null, ReportGenerator::REPORT_ID_REGEX);
                $reportManager->buildBlobMap($reportId, $map);
                $reportManager->removeReportCharts($reportId);
                break;
        }

        $reportName = mb_convert_encoding($this->getStringParam($request, 'report_name', true), ReportGenerator::REPORT_CHAR_ENCODING);
        $reportTitle = mb_convert_encoding($this->getStringParam($request, 'report_title', true), ReportGenerator::REPORT_CHAR_ENCODING);
        $reportHeader = mb_convert_encoding($this->getStringParam($request, 'report_header', true), ReportGenerator::REPORT_CHAR_ENCODING);
        $reportFooter = mb_convert_encoding($this->getStringParam($request, 'report_footer', true), ReportGenerator::REPORT_CHAR_ENCODING);
        $reportFormat = $this->getStringParam($request, 'report_format', false, null, ReportGenerator::REPORT_FORMATS_REGEX . 'i');
        $chartsPerPage = max(1, $this->getIntParam($request, 'charts_per_page'));
        $reportSchedule = $this->getStringParam($request, 'report_schedule', false, null, ReportGenerator::REPORT_SCHEDULE_REGEX);
        $reportDelivery = $this->getStringParam($request, 'report_delivery', false, '', ReportGenerator::REPORT_DELIVERY_REGEX . 'i');

        $reportManager->configureSelectedReport(
            $reportId,
            $reportName,
            $reportTitle,
            $reportHeader,
            $reportFooter,
            $reportFormat,
            $chartsPerPage,
            $reportSchedule,
            $reportDelivery
        );

        if ($reportManager->isUniqueName($reportName, $reportId) === false) {
            throw new BadRequestHttpException('Another report you have created is already using this name.');
        }

        switch ($phase) {
            case 'create':
                $reportManager->insertThisReport();
                break;
            case 'update':
                $reportManager->saveThisReport();
                break;
        }

        foreach ($request->request->all() as $k => $v) {
            if (preg_match('/chart_data_(\d+)/', $k, $m) > 0) {
                $order = $m[1];

                list($chart_id, $chart_title, $chart_drill_details, $chart_date_description, $timeframe_type, $entry_type) = explode(';', $v);

                $chart_title = str_replace('%3B', ';', $chart_title);
                $chart_drill_details = str_replace('%3B', ';', $chart_drill_details);

                $cache_ref_variable = 'chart_cacheref_' . $order;

                // Transfer blobs residing in the directory used for temporary
                // files into the database as necessary for each chart which
                // comprises the report.
                $cache_ref = $request->get($cache_ref_variable);
                if (isset($cache_ref)) {
                    $cache_ref = filter_var(
                        $cache_ref,
                        FILTER_VALIDATE_REGEXP,
                        ['options' => ['regexp' => ReportGenerator::CHART_CACHEREF_REGEX]]
                    );

                    list($start_date, $end_date, $ref, $rank) = explode(';', $cache_ref);

                    $location = sys_get_temp_dir() . "/{$ref}_{$rank}_{$start_date}_{$end_date}.png";

                    // Generate chart blob if it doesn't exist. This file should have already been create.
                    if (!is_file($location)) {
                        $insertion_rank = [
                            'rank' => $rank,
                            'did'  => '',
                        ];
                        $cached_blob = $start_date . ',' . $end_date . ';'
                            . $reportManager->generateChartBlob('volatile', $insertion_rank, $start_date, $end_date, $this->logger);
                    } else {
                        $cached_blob = $start_date . ',' . $end_date . ';' . file_get_contents($location);
                    }

                    $chart_id_found = false;

                    foreach ($map as &$e) {
                        if ($e['chart_id'] == $chart_id) {
                            $e['image_data'] = $cached_blob;
                            $chart_id_found = true;
                        }
                    }

                    if ($chart_id_found === false) {
                        $map[] = [
                            'chart_id'   => $chart_id,
                            'image_data' => $cached_blob
                        ];
                    }
                }

                $reportManager->saveCharttoReport($reportId, $chart_id, $chart_title, $chart_drill_details, $chart_date_description, $order, $timeframe_type, $entry_type, $map);
            }

        }

        return $this->json([
            'action'    => 'save_report',
            'phase'     => $phase,
            'report_id' => $reportId,
            'success'   => true,
            'status'    => 'success'
        ]);
    }

    /**
     * @Route("/reports/builder/remove", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function removeReportsById(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);

        $reportIds = explode(';', $this->getStringParam($request, 'selected_report', true));
        foreach ($reportIds as $reportId) {
            $reportManager->removeReportCharts($reportId);
            $reportManager->removeReportbyID($reportId);
        }

        return $this->json([
            'action'  => 'remove_report_by_id',
            'success' => true
        ]);
    }

    /**
     * @Route("/reports/builder/remove/chart", methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function removeChartFromPool(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());
        $reportManager = new \XDReportManager($user);
        $responseData = [
            'action'          => 'remove',
            'success'         => true,
            'dropped_entries' => []
        ];

        foreach ($request->request->all() as $k => $v) {
            if (preg_match('/^selected_chart_/', $k) == 1) {

                $reportManager->removeChartFromChartPoolByID($v);
                if (preg_match('/controller_module=(.+?)&/', $v, $m)) {

                    $module_id = $m[1];
                    if (!isset($responseData['dropped_entries'][$module_id])) {
                        $responseData['dropped_entries'][$module_id] = [];
                    }
                    $responseData['dropped_entries'][$module_id][] = $v;
                }
            }
        }

        return $this->json($responseData);
    }

    /**
     * @Route("/reports/builder/templates", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function getTemplates(Request $request): Response
    {
        try {
            $user = \xd_security\getLoggedInUser();
        } catch (Exception $e) {
            return $this->json(buildError($e), 401);
        }


        $templates = \XDReportManager::enumerateReportTemplates($user->getRoles());
        // We do not want to show the "Dashboard Tab Reports"
        foreach ($templates as $key => $value) {
            if ($value['name'] === 'Dashboard Tab Report') {
                unset($templates[$key]);
            }
        }
        return $this->json([
            'status'    => 'success',
            'success'   => true,
            'templates' => $templates,
            'count'     => count($templates)
        ]);
    }

    /**
     * @Route("/reports/builder/image", methods={"GET"})
     * @Route("/report_image_renderer.php", methods={"GET"}, name="report_image_renderer_legacy")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function generateReportImage(Request $request): Response
    {
        $this->logger->warning('Generating a Report Image');

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $userId = null;
        try {
            $this->logger->warning('Report Image Authenticated');
            $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());

            $type = $this->getStringParam($request, 'type', true, null, ReportGenerator::REPORT_CHART_TYPE_REGEX);
            $ref = $this->getStringParam($request, 'ref', true, null, ReportGenerator::REPORT_CHART_REF_REGEX);
            $did = $this->getStringParam($request, 'did', false, '', ReportGenerator::REPORT_CHART_DID_REGEX);
            $start = $this->getStringParam($request, 'start', false, null, ReportGenerator::REPORT_DATE_REGEX);
            $end = $this->getStringParam($request, 'end', false, null, ReportGenerator::REPORT_DATE_REGEX);


            switch ($type) {
                case 'chart_pool':
                case 'volatile':
                    $this->logger->warning('Report Image Volatile / chart Pool');
                    $numMatches = preg_match('/^(\d+);(\d+)$/', $ref, $matches);

                    if ($numMatches === 0) {
                        throw new Exception('Invalid thumbnail reference set');
                    }

                    $userId = (int)$matches[1];

                    if (isset($start) && isset($end)) {
                        $insertionRank = [
                            'rank'       => $matches[2],
                            'start_date' => $start,
                            'end_date'   => $end,
                            'did'        => $did
                        ];
                    } else {
                        $insertionRank = [
                            'rank' => $matches[2],
                            'did'  => $did
                        ];
                    }
                    break;
                case 'report':
                    $numMatches = preg_match('/^((\d+)-(.+));(\d+)$/', $ref, $matches);

                    if ($numMatches == 0) {
                        throw new Exception('Invalid thumbnail reference set');
                    }

                    $userId = $matches[2];
                    $insertionRank = ['report_id' => $matches[1], 'ordering' => $matches[4]];
                    break;
                case 'cached':
                    $numMatches = preg_match('/^((\d+)-(.+));(\d+)$/', $ref, $matches);

                    if ($numMatches == 0) {
                        throw new Exception('Invalid thumbnail reference set');
                    }

                    if (!isset($start) || !isset($end)) {
                        throw new Exception('Start and end dates not set');
                    }

                    $validStart = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $start);
                    $validEnd = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $end);

                    if (($validStart * $validEnd) == 0) {
                        throw new Exception('Invalid start and/or end date supplied');
                    }

                    $userId = $matches[2];

                    $insertionRank = [
                        'report_id'  => $matches[1],
                        'ordering'   => $matches[4],
                        'start_date' => $start,
                        'end_date'   => $end,
                    ];
                    break;
                default:
                    throw new Exception('Invalid thumbnail type value supplied: ' . $request['type']);
            }

            if ($userId != $user->getUserID()) {
                throw new AccessDeniedHttpException(sprintf('Invalid User Request. Expected %s, Actual: %s', $user->getUserID(), $userId));
            }

            $this->logger->warning('Valid User Request');

            $reportManager = null;
            try {
                $this->logger->warning('Instantiating XDREportManager');
                $reportManager = new XDReportManager($user);
            } catch (Exception $exception) {
                $this->logger->error('Error instantiating Report Manager');
            }

            $this->logger->warning('After Report Manager.');

            if (!empty($reportManager)) {
                $this->logger->warning('Fetching Chart Blob');
                $blob = $reportManager->fetchChartBlob($type, $insertionRank, null, $this->logger);
                $this->logger->warning('Substringing Blob');
                $image_data_header = substr($blob, 0, 8);
                $this->logger->warning('Chart BLob Fetched!');

                if ($image_data_header != "\x89PNG\x0d\x0a\x1a\x0a") {
                    throw new Exception($blob);
                }
                $this->logger->warning('Blob is a png');
                // If the blob is empty, than substitute the image below to be returned to the user.
                if (in_array(md5($blob), self::$emptyBlobs)) {
                    $blob = file_get_contents(dirname(__FILE__) . '/gui/images/report_thumbnail_no_data.png');
                }

                $headers = ['Content-Type' => 'image/png'];
                $this->logger->warning('Returning PNG');
                $this->logger->warning('Headers: ', [$headers]);
                return new Response($blob, 200,  $headers);
            } else {
                $this->logger->error('Oops, we shouldnt be here.');
            }

            return $this->json(['message' => 'Unable to instantiate report manager'], 500);
        } catch (Exception $e) {
            /* There used to be some code here that generated a custom image but it didn't actually do anything with
             * that image, just threw the exception so I have elected to not include it here.
             */
            $uniqueId = uniqid();
            $this->logger->error('Image generation failed!');
            // The message format here is from classes/UniqueException.php
            throw new HttpException(500, sprintf('[Unique ID %s] --> %s', $uniqueId, $e->getMessage()));
        }
    }

    /**
     * @Route("/reports/builder/{reportId}", methods={"GET"})
     * @param Request $request
     * @param string $reportId
     * @return Response
     * @throws Exception
     */
    public function getReportData(Request $request, string $reportId): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = XDUser::getUserByUserName($this->getUser()->getUserIdentifier());

        $this->logger->warning('get Report Data Start');
        $reportManager = new \XDReportManager($user);

        $flushCache = $this->getBooleanParam($request, 'flush_cache');
        $basedOnAnother = $this->getBooleanParam($request, 'based_on_another');

        if ($flushCache) {
            $reportManager->flushReportImageCache();
        }

        $data = $reportManager->loadReportData($reportId);

        if ($basedOnAnother) {
            // The report to be retrieved is to be the basis for a new report.
            // In this case, overwrite the report_id and report name fields so when it comes time to save this
            // report, a new report will be created instead of the original being overwritten / updated.
            $data['report_id'] = '';
            $data['general']['name'] = $reportManager->generateUniqueName($data['general']['name']);
        } else {
            $data['report_id'] = $reportId;
        }

        return $this->json([
            'action'  => 'fetch_report_data',
            'success' => true,
            'results' => $data
        ]);
    }

    /**
     * @Route("/img_placeholder.php", methods={"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function imgPlaceholder(Request $request): Response
    {
        $filePath = tempnam(sys_get_temp_dir(), 'img-placeholder-');
        $src = imagecreatetruecolor(7, 12);
        $background = imagecolorallocate($src, 255, 255, 255);
        imagefill($src, 0, 0, $background);
        imagepng($src, $filePath);

        return new BinaryFileResponse($filePath, 200, ['Content-Type: image/png']);
    }
}
