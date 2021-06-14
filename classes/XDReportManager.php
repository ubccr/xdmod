<?php

use CCR\MailWrapper;
use CCR\DB;
use CCR\Log;
use DataWarehouse\Access\Usage;

/**
 * Used for keeping track of charts a user wishes to add to his / her
 * report.
 */
class XDReportManager
{

    private $_user = null;
    private $_user_id = null;
    private $_charts_per_page = 1;
    private $_report_name = null;
    private $_report_title = null;
    private $_report_header = null;
    private $_report_footer = null;
    private $_report_schedule = null;
    private $_report_delivery = null;
    private $_report_format = null;
    private $_report_id = null;
    private $_pdo = null;

    /**
     * List of acceptable output formats and their respective content
     * types.
     *
     * @var array
     */
    private static $_header_map = array(
        'doc' => 'application/vnd.ms-word',
        'pdf' => 'application/pdf',
    );

    const DEFAULT_FORMAT = 'pdf';

    /**
     * @param XDUser $user
     */
    public function __construct($user)
    {
        $this->_pdo = DB::factory('database');
        $this->_user = $user;
        $this->_user_id         = $user->getUserID();
    }

    public function emptyCache()
    {
        $this->_pdo->execute(
            '
                UPDATE ReportCharts
                SET image_data = NULL
                WHERE user_id = :user_id
            ',
            array('user_id' => $this->_user_id)
        );
    }

    public static function isValidFormat($format)
    {
        return array_key_exists($format, self::$_header_map);
    }

    public static function resolveContentType($format)
    {
        return self::$_header_map[$format];
    }

    public static function enumScheduledReports($schedule_frequency)
    {
        $pdo = DB::factory('database');

        $results = $pdo->query(
            '
                SELECT user_id, report_id
                FROM moddb.Reports
                WHERE schedule = :schedule
            ',
            array('schedule' => $schedule_frequency)
        );

        $scheduled_reports = array();

        foreach ($results as $report_data) {
            $scheduled_reports[] = array(
                'user_id'   => $report_data['user_id'],
                'report_id' => $report_data['report_id'],
            );
        }

        return $scheduled_reports;
    }

    public function configureSelectedReport(
        $report_id,
        $report_name,
        $report_title,
        $report_header,
        $report_footer,
        $report_format,
        $charts_per_page,
        $report_schedule,
        $report_delivery
    ) {
        $this->_report_id = $report_id;
        $this->_report_name = $report_name;
        $this->_report_title = $report_title;
        $this->_report_header = $report_header;
        $this->_report_footer = $report_footer;
        $this->_report_format = $report_format;
        $this->_charts_per_page = $charts_per_page;
        $this->_report_schedule = $report_schedule;
        $this->_report_delivery = $report_delivery;
    }

    public function saveThisReport()
    {
        if (!isset($this->_report_id)) {
            throw new \Exception(
                "configureSelectedReport() must be called first"
            );
        }

        $this->_pdo->execute(
            "
            UPDATE Reports SET
                name            = :report_name,
                title           = :report_title,
                header          = :report_header,
                footer          = :report_footer,
                charts_per_page = :charts_per_page,
                format          = :format,
                schedule        = :schedule,
                delivery        = :delivery
            WHERE report_id = :report_id
            ",
            array(
                'report_name'     => $this->_report_name,
                'report_title'    => $this->_report_title,
                'report_header'   => $this->_report_header,
                'report_footer'   => $this->_report_footer,
                'charts_per_page' => $this->_charts_per_page,
                'format'          => $this->_report_format,
                'schedule'        => $this->_report_schedule,
                'delivery'        => $this->_report_delivery,
                'report_id'       => $this->_report_id,
            )
        );
    }

    private function fontWrapper($text, $font_size = 12)
    {
        return sprintf(
            '<span style="font-family: arial, sans-serif; font-size: %dpx">%s</span>',
            $font_size,
            $text
        );
    }

    public static function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9-_\. ]/', '', $filename);
        $filename = strtolower(str_replace(" ", "_", $filename));

        return (empty($filename) == true) ? 'xdmod_report' : $filename;
    }

    public function getPreviewData($report_id, $token, $charts_per_page)
    {
        $report_data = $this->loadReportData($report_id, false);

        $rData = array();
        $chartSlot = array();

        $chartCount = 0;

        foreach ($report_data['queue'] as $report_chart) {
            $suffix = ($chartCount++ % $charts_per_page);
            if (strtolower($report_chart['timeframe_type']) == 'user defined') {
                list($start_date, $end_date)
                    = explode(' to ', $report_chart['chart_date_description']);
            }
            else {
                $e = \xd_date\getEndpoints($report_chart['timeframe_type']);

                $start_date = $e['start_date'];
                $end_date = $e['end_date'];
            }

            // Update comments and hyperlink so reporting engine can
            // work with the correct chart (image).
            $report_chart['chart_date_description']
                = $start_date . ' to ' . $end_date;

            $report_chart['chart_id'] = preg_replace(
                '/start_date=(\d){4}-(\d){2}-(\d){2}/',
                "start_date=$start_date",
                $report_chart['chart_id']
            );
            $report_chart['chart_id'] = preg_replace(
                '/end_date=(\d){4}-(\d){2}-(\d){2}/',
                "end_date=$end_date",
                $report_chart['chart_id']
            );

            // Titles are handled by the report template itself and do
            // not need to be repeated in the chart image.
            $report_chart['chart_id'] = preg_replace(
                '/&title=(.+)/',
                "&title=",
                $report_chart['chart_id']
            );

            if (empty($report_chart['chart_drill_details'])) {
                $report_chart['chart_drill_details'] = ORGANIZATION_NAME_ABBREV;
            }

            $chartSlot[$suffix] = array(
                'report_title'                   => (count($rData) == 0 && !empty($report_data['general']['title'])) ? $this->fontWrapper($report_data['general']['title'], 22) . '<br />' : '',
                'header_text'                    => $this->fontWrapper($report_data['general']['header'], 12),
                'footer_text'                    => $this->fontWrapper($report_data['general']['footer'], 12),
                'chart_title_' . $suffix         => $this->fontWrapper($report_chart['chart_title'], 16),
                'chart_drill_details_' . $suffix => $this->fontWrapper($report_chart['chart_drill_details'], 12),
                'chart_timeframe_' . $suffix     => $this->fontWrapper($report_chart['chart_date_description'], 14),
                'chart_id_' . $suffix            => '/report_image_renderer.php?type=report&ref=' . $report_id . ';' . $report_chart['ordering']
            );

            if (count($chartSlot) == $charts_per_page) {
                $combinedSlots = array();
                foreach ($chartSlot as $e) {
                    $combinedSlots += $e;
                }
                $rData[] = $combinedSlots;
                $chartSlot = array();
            }
        }

        if (count($chartSlot) > 0) {

            // Handle remainder of charts...

            $combinedSlots = array();

            foreach ($chartSlot as $e) {
                $combinedSlots += $e;
            }

            for ($i = count($chartSlot); $i < $charts_per_page; $i++) {
                $combinedSlots += array(
                    'chart_title_' . $i         => '',
                    'chart_drill_details_' . $i => '',
                    'chart_timeframe_' . $i     => '',
                    'chart_id_' . $i            => 'img_placeholder.php?'
                );
            }

            $rData[] = $combinedSlots;
        }

        return $rData;
    }

    public function insertThisReport($report_derivation_method = 'Manual')
    {
        if (!isset($this->_report_id)) {
            throw new \Exception(
                "configureSelectedReport() must be called first"
            );
        }

        $this->_pdo->execute(
            "
                INSERT INTO Reports (
                    report_id,
                    user_id,
                    name,
                    derived_from,
                    title,
                    header,
                    footer,
                    format,
                    schedule,
                    delivery,
                    selected,
                    charts_per_page
                ) VALUES (
                    :report_id,
                    :user_id,
                    :report_name,
                    :derived_from,
                    :report_title,
                    :report_header,
                    :report_footer,
                    :report_format,
                    :report_schedule,
                    :report_delivery,
                    :selected,
                    :charts_per_page
                )
            ",
            array(
                'report_id'       => $this->_report_id,
                'user_id'         => $this->_user_id,
                'report_name'     => $this->_report_name,
                'derived_from'    => $report_derivation_method,
                'report_title'    => $this->_report_title,
                'report_header'   => $this->_report_header,
                'report_footer'   => $this->_report_footer,
                'report_format'   => $this->_report_format,
                'report_schedule' => $this->_report_schedule,
                'report_delivery' => $this->_report_delivery,
                'selected'        => 0,
                'charts_per_page' => $this->_charts_per_page
            )
        );
    }

    public function generateUniqueName($base_name = 'TAS Report')
    {
        $pdo = DB::factory('database');

        $values = array();

        // If the existing $base_name has a numerical suffix, consider
        // that value when generating the new suffix.

        $name_frags = explode(' ', $base_name);
        $name_suffix = array_pop($name_frags);

        if (is_numeric($name_suffix)){
            $base_name = implode(' ', $name_frags).' ';
            $values[] = $name_suffix;
        }

        $results = $pdo->query(
            "
                SELECT name
                FROM Reports
                WHERE user_id = :user_id
                    AND name LIKE :base_name
            ",
            array(
                'user_id'   => $this->_user_id,
                'base_name' => "$base_name%"
            )
        );

        foreach ($results as $report_data) {
            $name = substr($report_data['name'], strlen($base_name));

            if (is_numeric($name)) {
                $values[] = $name;
            }
        }

        $id = (count($values) > 0) ? (max($values) + 1) : 1;

        $base_name = trim($base_name);

        return "$base_name $id";
    }

    public function isUniqueName($report_name, $report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT name
                FROM Reports
                WHERE user_id = :user_id
                    AND report_id != :report_id
                    AND name LIKE :report_name
            ",
            array(
                'user_id'     => $this->_user_id,
                'report_id'   => $report_id,
                'report_name' => $report_name,
            )
        );

        return (count($results) == 0);
    }

    public function emptyQueue()
    {
        $this->_pdo->execute(
            "DELETE FROM ChartPool WHERE user_id='{$this->_user_id}'"
        );
    }

    private function getParameterIn($param, $haystack)
    {
        $num_matches = preg_match("/$param=(.+)/", $haystack, $matches);

        $param_value = '';

        if ($num_matches > 0) {
            $frags = explode('&', str_replace('/', '&', $matches[1]));
            $param_value = $frags[0];
        }

        return $param_value;
    }

    public function fetchChartPool()
    {
        $query = "
            SELECT
                chart_id,
                user_id,
                insertion_rank,
                chart_title,
                chart_drill_details,
                chart_date_description,
                type
            FROM ChartPool
            WHERE user_id = :user_id
            ORDER BY insertion_rank ASC
        ";

        $results = $this->_pdo->query(
            $query,
            array('user_id' => $this->_user_id)
        );

        $chartEntries = array();

        foreach ($results as $entry) {
            $timeframe_type = $this->getParameterIn(
                'timeframe_label',
                $entry['chart_id']
            );
            $timeframe_type = urldecode($timeframe_type);

            $thumbnail_link
                = '/report_image_renderer.php?type=chart_pool&ref='
                . $entry['user_id']
                . ';'
                . $entry['insertion_rank']
                . '&token=';

            $chartEntries[] = array(
                'chart_id'               => $entry['chart_id'],
                'thumbnail_link'         => $thumbnail_link,
                'chart_title'            => $entry['chart_title'],
                'chart_drill_details'    => $entry['chart_drill_details'],
                'chart_date_description' => $entry['chart_date_description'],
                'type'                   => $entry['type'],
                'timeframe_type'         => $timeframe_type
            );
        }

        return $chartEntries;
    }

    public function fetchReportTable()
    {
        $query = "
            SELECT
                r.report_id,
                r.name,
                r.derived_from,
                r.title,
                r.charts_per_page,
                r.format,
                r.schedule,
                r.delivery,
                COUNT(rc.chart_id) AS chart_count,
                UNIX_TIMESTAMP(r.last_modified) as last_modified
            FROM Reports r
            LEFT JOIN ReportCharts rc ON rc.report_id = r.report_id
            WHERE r.user_id = :user_id
            GROUP BY
                r.report_id,
                r.name,
                r.derived_from,
                r.title,
                r.charts_per_page,
                r.format,
                r.schedule,
                r.delivery,
                r.last_modified
        ";

        $Entries = array();

        $results = $this->_pdo->query(
            $query,
            array('user_id' => $this->_user_id)
        );

        foreach ($results as $entry) {
            $Entries[] = array(
                'report_id'       => $entry['report_id'],
                'report_name'     => $entry['name'],
                'creation_method' => $entry['derived_from'],
                'report_title'    => $entry['title'],
                'charts_per_page' => $entry['charts_per_page'],
                'report_format'   => $entry['format'],
                'report_schedule' => $entry['schedule'],
                'report_delivery' => $entry['delivery'],
                'chart_count'     => $entry['chart_count'],
                'last_modified'   => $entry['last_modified']
            );
        }

        return $Entries;
    }

    private function generateUID()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function flushReportImageCache()
    {
        $cache_dir = sys_get_temp_dir() . '/';

        if ($dh = opendir($cache_dir)) {
            while (($file = readdir($dh)) !== false) {
                if (
                    preg_match(
                        '/^xd_report_volatile_' . $this->_user_id
                            . '_(.+).[png|xrc]/',
                        $file
                    )
                    ||
                    preg_match(
                        '/^' . $this->_user_id . '-(.+).png/',
                        $file
                    )
                ) {
                    unlink($cache_dir.$file);
                }
            }
            closedir($dh);
        }
    }

    public function loadReportData($report_id)
    {
        $return_data = array();

        $query = "
            SELECT
                name,
                title,
                header,
                footer,
                format,
                charts_per_page,
                schedule,
                delivery
            FROM Reports
            WHERE user_id = :user_id
                AND report_id = :report_id
        ";

        $return_data['general'] = array();

        $results = $this->_pdo->query(
            $query,
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        if (count($results) == 0) {
            $return_data['success'] = false;
            $return_data['message']
                = "Report with id $report_id could not be found.";
            return $return_data;
        }

        $return_data['general']['name']            = $results[0]['name'];
        $return_data['general']['title']           = $results[0]['title'];
        $return_data['general']['header']          = $results[0]['header'];
        $return_data['general']['footer']          = $results[0]['footer'];
        $return_data['general']['format']          = $results[0]['format'];
        $return_data['general']['charts_per_page'] = $results[0]['charts_per_page'];
        $return_data['general']['schedule']        = $results[0]['schedule'];
        $return_data['general']['delivery']        = $results[0]['delivery'];

        $query = "
            SELECT
                chart_id,
                report_id,
                ordering,
                chart_title,
                chart_drill_details,
                chart_date_description,
                chart_type,
                type,
                timeframe_type
            FROM ReportCharts
            WHERE user_id = :user_id AND report_id = :report_id
            ORDER BY ordering ASC
        ";

        $return_data['queue'] = array();

        $results = $this->_pdo->query(
            $query,
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        foreach ($results as $entry) {
            $chart_data = array();

            $chart_data['chart_id'] = $entry['chart_id'];

            $chart_data['thumbnail_link']
                = '/report_image_renderer.php?type=report&ref='
                . $entry['report_id']
                . ';'
                . $entry['ordering']
                . '&dc='
                . $this->generateUID()
                . '&token=';

            $chart_data['ordering'] = $entry['ordering'];

            $chart_data['chart_title'] = $entry['chart_title'];
            $chart_data['chart_drill_details'] = $entry['chart_drill_details'];
            $chart_data['chart_date_description'] = $entry['chart_date_description'];
            $chart_data['type'] = $entry['type'];
            $chart_data['timeframe_type'] = $entry['timeframe_type'];

            $return_data['queue'][] = $chart_data;
        }

        $return_data['success'] = true;

        return $return_data;
    }

    /**
     * Retrieve report data for use by the XML definition
     */
    public function fetchReportData($report_id)
    {
        $query = "
            SELECT
                ordering,
                chart_title,
                chart_id,
                chart_date_description,
                chart_drill_details,
                timeframe_type
            FROM ReportCharts
            WHERE report_id = :report_id AND user_id = :user_id
            ORDER BY ordering ASC
        ";

        $results = $this->_pdo->query(
            $query,
            array(
                'report_id' => $report_id,
                'user_id'   => $this->_user_id,
            )
        );

        $report_data = array();

        foreach ($results as $entry) {
            $chart_data = array();

            $chart_data['order']          = $entry['ordering'];
            $chart_data['title']          = $entry['chart_title'];
            $chart_data['comments']       = $entry['chart_date_description'];
            $chart_data['drill_details']  = $entry['chart_drill_details'];
            $chart_data['timeframe_type'] = $entry['timeframe_type'];

            $report_data[] = $chart_data;
        }

        return $report_data;
    }

    private function createElement(&$dom, &$node, $elementText, $text)
    {
        $elementNode = $dom->createElement($elementText);
        $node->appendChild($elementNode);

        $textNode = $dom->createTextNode(empty($text) ? ' ' : $text);
        $elementNode->appendChild($textNode);
    }

    public function removeReportbyID($report_id)
    {
        $this->_pdo->execute(
            "
                DELETE FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );
    }

    public function buildBlobMap($report_id, &$map)
    {
        $query = "
            SELECT chart_id, image_data
            FROM ReportCharts
            WHERE report_id = :report_id AND user_id = :user_id
        ";

        $map = $this->_pdo->query(
            $query,
            array(
                'report_id' => $report_id,
                'user_id'   => $this->_user_id,
            )
        );
    }

    public function resolveBlobFromChartId(&$map, $chart_id)
    {
        foreach ($map as $e) {
            if ($chart_id == $e['chart_id']) {
                return $e['image_data'];
            }
        }

        return null;
    }

    public function removeReportCharts($report_id)
    {
        $this->_pdo->execute(
            "
                DELETE FROM ReportCharts
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );
    }

    public function syncDatesBetweenIDAndBlobs($report_id)
    {
        $query = "
            SELECT
                chart_id,
                ordering,
                substr(image_data, 1, 21) AS blob_timestamp
            FROM ReportCharts
            WHERE report_id = :report_id
        ";

        $result = $this->_pdo->query($query, array('report_id' => $report_id));

        foreach ($result as $r) {
            if (is_null($r['blob_timestamp'])) {
                continue;
            }

            list($blob_start, $blob_end) = explode(',', $r['blob_timestamp']);

            print "order: " . $r['ordering'] . "\n";
            print "blob start: $blob_start\n";
            print "blob end: $blob_end\n";
            print "chart_id: " . $r['chart_id'] . "\n\n";

            $rep = preg_replace(
                '/start_date=(\d{4}-\d\{2}-\d{2})/',
                "start_date=$blob_start",
                $r['chart_id'],
                1
            );

            print "updated_cid: $rep\n";

            print "\n";
        }
    }

    public function saveCharttoReport(
        $report_id,
        $chart_id,
        $chart_title,
        $chart_drill_details,
        $chart_date_description,
        $position_in_report,
        $timeframe_type,
        $entry_type,
        &$map = array()
    ) {
        $this->_pdo->execute(
            "
                INSERT INTO ReportCharts (
                    chart_id,
                    report_id,
                    user_id,
                    chart_title,
                    chart_drill_details,
                    chart_type,
                    chart_date_description,
                    ordering,
                    timeframe_type,
                    image_data,
                    type,
                    selected
                ) VALUES (
                    :chart_id,
                    :report_id,
                    :user_id,
                    :chart_title,
                    :chart_drill_details,
                    :chart_type,
                    :chart_date_description,
                    :ordering,
                    :timeframe_type,
                    :image_data,
                    :type,
                    :selected
                )
            ",
            array(
                'chart_id'               => $chart_id,
                'report_id'              => $report_id,
                'user_id'                => $this->_user_id,
                'chart_title'            => $chart_title,
                'chart_drill_details'    => $chart_drill_details,
                'chart_type'             => '',
                'chart_date_description' => $chart_date_description,
                'ordering'               => $position_in_report,
                'timeframe_type'         => $timeframe_type,
                'image_data'             => $this->resolveBlobFromChartId($map, $chart_id),
                'type'                   => $entry_type,
                'selected'               => 0,
            )
        );
    }

    public function removeChartFromChartPoolByID($chart_id)
    {
        $this->_pdo->execute(
            "
                DELETE FROM ChartPool
                WHERE chart_id = :chart_id AND user_id = :user_id
            ",
            array(
                'chart_id' => $chart_id,
                'user_id'  => $this->_user_id,
            )
        );
    }

    public function getReportUserName($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT u.first_name, u.last_name
                FROM Users AS u,
                    Reports AS r
                WHERE r.user_id = :user_id
                    AND r.report_id = :report_id
                    AND r.user_id = u.id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['first_name'] . " " . $results[0]['last_name'];
    }

    public function getReportUserFirstName($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT u.first_name
                FROM Users AS u,
                    Reports AS r
                WHERE r.user_id = :user_id
                    AND r.report_id = :report_id
                    AND r.user_id = u.id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['first_name'];
    }

    public function getReportUserLastName($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT u.last_name
                FROM Users AS u,
                    Reports AS r
                WHERE r.user_id = :user_id
                    AND r.report_id = :report_id
                    AND r.user_id = u.id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['last_name'];
    }

    public function getReportUserEmailAddress($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT u.email_address
                FROM Users AS u,
                    Reports AS r
                WHERE r.user_id = :user_id
                    AND r.report_id = :report_id
                    AND r.user_id = u.id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['email_address'];
    }

    public function getReportFormat($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT format
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['format'];
    }

    public function getReportName($report_id, $sanitize = false)
    {
        $results = $this->_pdo->query(
            "
                SELECT name
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return ($sanitize == false)
            ? $results[0]['name']
            : self::sanitizeFilename($results[0]['name']);
    }

    public function getReportHeader($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT header
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['header'];
    }

    public function getReportFooter($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT footer
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['footer'];
    }

    public function getReportTitle($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT title
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['title'];
    }

    public function getReportDerivation($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT derived_from
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['derived_from'];
    }

    public function getReportChartsPerPage($report_id)
    {
        $results = $this->_pdo->query(
            "
                SELECT charts_per_page
                FROM Reports
                WHERE user_id = :user_id AND report_id = :report_id
            ",
            array(
                'user_id'   => $this->_user_id,
                'report_id' => $report_id,
            )
        );

        return $results[0]['charts_per_page'];
    }

    private function generateCachedFilename(
        $insertion_rank,
        $volatile = false,
        $base_name_only = false
    ) {
        if ($volatile == true) {
            $duplication_id
                = is_array($insertion_rank) && isset($insertion_rank['did'])
                ? $insertion_rank['did']
                : '';

            $this->ripTransform($insertion_rank, 'did');

            if (
                is_array($insertion_rank)
                && isset($insertion_rank['rank'])
                && isset($insertion_rank['start_date'])
                && isset($insertion_rank['end_date'])
            ) {
                if ($base_name_only == true) {
                    return sys_get_temp_dir()
                        . '/xd_report_volatile_'
                        . $this->_user_id
                        . '_'
                        . $insertion_rank['rank']
                        . $duplication_id
                        . '.png';
                }

                return sys_get_temp_dir()
                    . '/xd_report_volatile_'
                    . $this->_user_id
                    . '_'
                    . $insertion_rank['rank']
                    . $duplication_id
                    . '_'
                    . $insertion_rank['start_date']
                    . '_'
                    . $insertion_rank['end_date']
                    . '.png';

            }
            else {
                return sys_get_temp_dir()
                    . '/xd_report_volatile_'
                    . $this->_user_id
                    . '_'
                    . $insertion_rank
                    . $duplication_id
                    . '.png';
            }
        }
        else {
            return sys_get_temp_dir()
                . '/'
                . $insertion_rank['report_id']
                . '_'
                . $insertion_rank['ordering']
                . '_'
                . $insertion_rank['start_date']
                . '_'
                . $insertion_rank['end_date']
                . '.png';
        }
    }

    private function ripTransform(&$arr, $item)
    {
        if (is_array($arr) && isset($arr[$item])) {
            unset($arr[$item]);
            if (count($arr) == 1) {
                $arr = array_pop($arr);
            }
        }
    }

    public function fetchChartBlob(
        $type,
        $insertion_rank,
        $chart_id_cache_file = null
    ) {
        $pdo = DB::factory('database');
        $trace = "";

        switch ($type) {
            case 'volatile':
                $temp_file = $this->generateCachedFilename(
                    $insertion_rank,
                    true
                );

                if (file_exists($temp_file)) {
                    print file_get_contents($temp_file);
                }
                else {
                    if (
                        is_array($insertion_rank)
                        && isset($insertion_rank['rank'])
                        && isset($insertion_rank['start_date'])
                        && isset($insertion_rank['end_date'])
                    ) {
                        $blob = $this->generateChartBlob(
                            $type,
                            $insertion_rank,
                            $insertion_rank['start_date'],
                            $insertion_rank['end_date']
                        );
                    }
                    else {

                        // If no start or end dates are supplied, then,
                        // grab directly from chart pool

                        $chart_config_file = str_replace(
                            '.png',
                            '.xrc',
                            $temp_file
                        );

                        $blob = $this->fetchChartBlob(
                            'chart_pool',
                            $insertion_rank['rank'],
                            $chart_config_file
                        );

                        // The following 3 lines are in place as a
                        // performance enhancement.  Should the user
                        // change the timeframe of a 'volatile' chart,
                        // then reset the timeframe back to the default,
                        // the logic below ensures that the default
                        // cached data is presented.

                        $chart_id_config = file($chart_config_file);

                        file_put_contents($temp_file, $blob);

                        $temp_file = str_replace(
                            '.png',
                            '_' . $chart_id_config[1] . '.png',
                            $temp_file
                        );
                    }

                    file_put_contents($temp_file, $blob);

                    print $blob;
                }

                exit;
                break;
            case 'chart_pool':
                $this->ripTransform($insertion_rank, 'did');

                $iq = $pdo->query(
                    "
                        SELECT chart_id, image_data
                        FROM ChartPool
                        WHERE user_id = :user_id
                            AND insertion_rank = :insertion_rank
                    ",
                    array(
                        'user_id' => $this->_user_id,
                        'insertion_rank' => $insertion_rank
                    )
                );

                $trace = "user_id = {$this->_user_id},"
                    . " insertion_rank = $insertion_rank";
                break;
            case 'cached':
                $temp_file = $this->generateCachedFilename($insertion_rank);

                if (file_exists($temp_file)) {
                    print file_get_contents($temp_file);
                }
                else {
                    $blob = $this->generateChartBlob(
                        $type,
                        $insertion_rank,
                        $insertion_rank['start_date'],
                        $insertion_rank['end_date']
                    );
                    file_put_contents($temp_file, $blob);
                    print $blob;
                }

                exit;
                break;
            case 'report':
                $iq = $pdo->query(
                    "
                        SELECT
                            chart_id,
                            timeframe_type,
                            image_data,
                            chart_date_description
                        FROM ReportCharts
                        WHERE report_id = :report_id AND ordering = :ordering
                    ",
                    array(
                        'report_id' => $insertion_rank['report_id'],
                        'ordering'  => $insertion_rank['ordering'],
                    )
                );

                $trace = "report_id = {$insertion_rank['report_id']},"
                    . " ordering = {$insertion_rank['ordering']}";
                break;
        }

        if (count($iq) == 0) {
            throw new \Exception(
                "No ($type) chart entry could be located ($trace)"
            );
        }

        $image_data = $iq[0]['image_data'];
        $chart_id   = $iq[0]['chart_id'];

        $active_start = $this->getParameterIn('start_date', $chart_id);
        $active_end   = $this->getParameterIn('end_date', $chart_id);

        if (isset($iq[0]['chart_date_description'])) {
            list($active_start, $active_end)
                = explode(' to ', $iq[0]['chart_date_description']);
        }

        // Timeframe determination

        if ($type == 'chart_pool' || $type == 'volatile') {
            $timeframe_type = $this->getParameterIn(
                'timeframe_label',
                $chart_id
            );
        }

        if ($type == 'report') {
            $timeframe_type = $iq[0]['timeframe_type'];
        }

        if (strtolower($timeframe_type) == 'user defined') {
            $start_date = $active_start;
            $end_date = $active_end;
        }
        else {
            $e = \xd_date\getEndpoints($timeframe_type);

            $start_date = $e['start_date'];
            $end_date   = $e['end_date'];
        }

        if (!empty($chart_id_cache_file)) {
            file_put_contents(
                $chart_id_cache_file,
                $chart_id . "\n" . $start_date . '_' . $end_date
            );
        }

        if (empty($image_data)) {

            // No BLOB to begin with
            return $this->generateChartBlob(
                $type,
                $insertion_rank,
                $start_date,
                $end_date
            );
        }
        else {

            // BLOB exists. Parse out the date information prepended to
            // the actual image data then compare against $start_date
            // and $end_date to see if the image data needs to be
            // refreshed.

            $blob_elements = explode(';', $image_data, 2);
            list($blob_start, $blob_end) = explode(',', $blob_elements[0]);

            if (($blob_start == $start_date) && ($blob_end == $end_date)) {
                $image_data_header = substr($blob_elements[1], 0, 8);

                if ($image_data_header == "\x89PNG\x0d\x0a\x1a\x0a") {

                    // Cached blob is still usable (contains raw png data)
                    return $blob_elements[1];
                }
                else {

                    // Cached data is not considered 'valid'. Re-generate blob
                    return $this->generateChartBlob(
                        $type,
                        $insertion_rank,
                        $start_date,
                        $end_date
                    );
                }
            }
            else {

                // Cached data has gone stale. Re-generate blob
                return $this->generateChartBlob(
                    $type,
                    $insertion_rank,
                    $start_date,
                    $end_date
                );
            }
        }
    }

    private function getChartData($chart_id, $overrides)
    {
        $arg_set = explode("&", $chart_id);
        $query_params = array();

        foreach ($arg_set as $a) {
            list($arg_name, $arg_value) = explode('=', $a, 2);
            $query_params[$arg_name] = $arg_value;
        }

        $callargs = array_merge($query_params, $overrides);

        $supportedControllers = array(
            'metric_explorer' => array(
                'class' => '\DataWarehouse\Access\MetricExplorer',
                'function' => 'get_data' ),
            'usage_explorer' => array(
                'class' => '\DataWarehouse\Access\MetricExplorer',
                'function' => 'get_data' ),
            'data_explorer' => array(
                'class' => '\DataWarehouse\Access\DataExplorer',
                'function' => 'get_ak_plot'),
            'custom_query' => array(
                'class' => '\DataWarehouse\Access\CustomQuery',
                'function' => 'get_data'
            )
        );

        if( isset($query_params['controller_module']) && isset($query_params['operation']) )
        {
            $module = $query_params['controller_module'];
            $operation = $query_params['operation'];

            if( isset($supportedControllers[$module]) && $supportedControllers[$module]['function'] == $operation ) {
                $c = new $supportedControllers[$module]['class']($callargs);
                $response = $c->$operation($this->_user);
            }
            else
            {
                $usageAdapter = new Usage($callargs);
                $response = $usageAdapter->getCharts($this->_user);
            }
            return $response['results'];
        }
        else
        {
            // No controller specified - this must be a chart generated with an ealier verson
            // of the XDMoD software. Use the presence of format=hc_jsonstore to determine which
            // controller processes the chart
            if( isset($query_params['format']) && $query_params['format'] == 'hc_jsonstore') {
                $c = new \DataWarehouse\Access\MetricExplorer($callargs);
                $response = $c->get_data($this->_user);
            } else {
                $usageAdapter = new Usage($callargs);
                $response = $usageAdapter->getCharts($this->_user);
            }
            return $response['results'];
        }

        throw new \Exception("Unsupported controller in report generator");
    }

    public function generateChartBlob(
        $type,
        $insertion_rank,
        $start_date,
        $end_date
    ) {
        $pdo = DB::factory('database');

        switch ($type) {
            case 'volatile':
                $temp_file = $this->generateCachedFilename(
                    $insertion_rank,
                    true,
                    true
                );
                $temp_file = str_replace('.png', '.xrc', $temp_file);

                $iq = array();

                if (file_exists($temp_file) == true) {
                    $chart_id_config = file($temp_file);
                    $iq[] = array('chart_id' => $chart_id_config[0]);
                }
                else {
                    return $this->generateChartBlob(
                        'chart_pool',
                        $insertion_rank['rank'],
                        $start_date,
                        $end_date
                    );
                }
                break;

            case 'chart_pool':
                $iq = $pdo->query(
                    "
                        SELECT chart_id
                        FROM ChartPool
                        WHERE user_id = :user_id
                            AND insertion_rank = :insertion_rank
                    ",
                    array(
                        'user_id'        => $this->_user_id,
                        'insertion_rank' => $insertion_rank,
                    )
                );
                break;

            case 'cached':
            case 'report':
                $iq = $pdo->query(
                    "
                        SELECT chart_id
                        FROM ReportCharts
                        WHERE report_id = :report_id
                            AND ordering = :ordering
                    ",
                    array(
                        'report_id' => $insertion_rank['report_id'],
                        'ordering'  => $insertion_rank['ordering'],
                    )
                );
                break;
        }

        if (count($iq) == 0) {
            throw new \Exception("Unable to target chart entry");
        }

        $chart_id = $iq[0]['chart_id'];

        $chartoverrides = array(
            "render_for_report" => "y",
            "start_date" => "$start_date",
            "end_date" => "$end_date",
            "format" => 'png_inline',
            "scale" => 1,
            "width" => 800,
            "height" => 600,
            "show_title" => 'n',
            "title" => '',
            "subtitle" => '',
            "show_filters" => false,
            "font_size" => 3,
            "show_guide_lines" => 'y',
            "show_gradient" => 'n',
            "format" => 'png_inline'
        );

        $raw_png_data = $this->getChartData($chart_id, $chartoverrides);

        switch ($type) {
            case 'chart_pool':
                $pdo->execute(
                    "
                        UPDATE moddb.ChartPool
                        SET image_data = :image_data
                        WHERE user_id = :user_id
                            AND insertion_rank = :insertion_rank
                    ",
                    array(
                        'user_id'        => $this->_user_id,
                        'insertion_rank' => $insertion_rank,
                        'image_data'     => "$start_date,$end_date;"
                                            . $raw_png_data
                    )
                );
                break;

            case 'volatile':
            case 'cached':
                return $raw_png_data;
                break;

            case 'report':
                $pdo->execute(
                    "
                        UPDATE moddb.ReportCharts
                        SET image_data = :image_data
                        WHERE report_id = :report_id
                            AND ordering = :ordering
                    ",
                    array(
                        'report_id'  => $insertion_rank['report_id'],
                        'ordering'   => $insertion_rank['ordering'],
                        'image_data' => "$start_date,$end_date;"
                                        . $raw_png_data
                    )
                );
                break;
        }

        return $raw_png_data;
    }


    public function buildReport($report_id, $export_format)
    {

        if (
            $this->getReportDerivation($report_id)
                == 'Monthly Compliance Report'
        ) {

            $compliance_report = new XDComplianceReport();

            $data = $compliance_report->prepareComplianceData();

            //$data['content'] = array();   // <--- comment/uncomment to toggle the 2 compliance report variations which exist.

            $response = $compliance_report->generate(
                $data['start_date'] . ' to ' . $data['end_date'],
                $data
            );

            return $response;
        }

        $report_format = ($export_format != null) ? $export_format : $this->getReportFormat($report_id);

        // Initialize a temporary working directory for the report generation
        $template_path = tempnam(sys_get_temp_dir(), $report_id . '-');

        if ($template_path === false) {
            throw new \Exception("Failed to create temporary file");
        }

        if (!unlink($template_path)) {
            throw new \Exception("Failed to remove file '$template_path'");
        }

        if (!mkdir($template_path, 0777)) {
            throw new \Exception("Failed to create directory '$template_path'");
        }

        $report_output_file = $template_path . '/' . $report_id . '.' . strtolower($report_format);

        $settings = $this->gatherReportSettings($report_id);

        $rp = new \Reports\ClassicReport($settings);
        $rp->writeReport($template_path . '/' . $report_id . '.doc');

        if (strtolower($report_format) == 'pdf') {
            exec('HOME=' . $template_path . ' libreoffice --headless --convert-to pdf ' . $template_path . '/' .  $report_id . '.doc --outdir ' . $template_path);
        }

        return array(
            'template_path' => $template_path,
            'report_file'   => $report_output_file,
        );
    }

    public function mailReport(
        $report_id,
        $report_file,
        $frequency = '',
        $additional_config = array()
    ) {

        $frequency = (!empty($frequency)) ? ' '.$frequency : $frequency;

        $subject_suffix = (APPLICATION_ENV == 'dev') ? '[Dev]' : '';

        $destination_email_address = $this->getReportUserEmailAddress($report_id);

        $report_owner = $this->getReportUserName($report_id);

        $templateType = '';

        switch ($this->getReportDerivation($report_id)) {
            case 'Monthly Compliance Report':
                $include_attachment
                    = ($additional_config['failed_compliance'] > 0
                        || $additional_config['proposed_requirements'] > 0);

                $templateType = 'compliance_report';
                break;

            default:
                $include_attachment = true;

                $frequency = trim($frequency);
                $frequency
                    = !empty($frequency)
                    ? ' ' . $frequency
                    : $frequency;

                $templateType = 'custom_report';
                break;
        }

        try {
            $attachment_file_name = '';
            if($include_attachment) {
                    $report_format = pathinfo($report_file, PATHINFO_EXTENSION);
                    $attachment_file_name
                        = $this->getReportName($report_id, true)
                        . '.' . $report_format;
            }

            $properties = array(
                'recipient_name'       => $report_owner,
                'maintainer_signature' => MailWrapper::getMaintainerSignature(),
                'toAddress'            => $destination_email_address,
                'attachment' => array(
                    array('fileName'             => $report_file,
                          'attachment_file_name' => $attachment_file_name,
                          'encoding'             => 'base64',
                          'type'                 => self::$_header_map[$report_format],
                          'disposition'          => 'inline'
                    )
                )
            );

            if($templateType === 'custom_report') {
                $properties['frequency'] = $frequency;
                $properties['site_title'] = \xd_utilities\getConfiguration('general', 'title');
                $properties['subject'] = "Your$frequency " . 'XDMoD Report' . " $subject_suffix";
            } else {
                $properties['additional_information'] = $additional_config['custom_message'];
                $properties['subject'] = "Your$frequency " . 'XDMoD Compliance Report' . " $subject_suffix";
            }

            MailWrapper::sendTemplate($templateType, $properties);
        }
        catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * generate a php array containing the report configuration settings
     * and the chart images.
     * @param $report_id The report to generate
     * @return array $settings the report settings
     */
    private function gatherReportSettings($report_id)
    {
        $settings = array();

        $settings['header'] = $this->getReportHeader($report_id);
        $settings['footer'] = $this->getReportFooter($report_id);
        $settings['title'] = $this->getReportTitle($report_id);
        $settings['charts_per_page'] = (int) $this->getReportChartsPerPage($report_id);

        $settings['charts'] = array();

        foreach ($this->fetchReportData($report_id) as $entry) {

            $chart = $entry;

            if (empty($entry['drill_details'])) {
                $chart['drill_details'] = ORGANIZATION_NAME_ABBREV;
            }

            if (strtolower($entry['timeframe_type']) == 'user defined') {
                list($start_date, $end_date)
                    = explode(' to ', $entry['comments']);
            }
            else {
                $e = \xd_date\getEndpoints($entry['timeframe_type']);

                $start_date = $e['start_date'];
                $end_date   = $e['end_date'];
            }

            $chart['comments'] = $start_date . ' to ' . $end_date;
            $chart['imagedata'] = $this->fetchChartBlob("report", array("report_id" => $report_id, "ordering" => $entry['order'] ) );
            $settings['charts'][] = $chart;
        }

        return $settings;
    }

    /** retrieve information about the available report templates
     * for a given set of ACLs.
     * @param acls array() of acl names
     * @param templateName string an optional filter to only return templates that match the name
     */
    public static function enumerateReportTemplates(
        $acls = array(),
        $templateName = null
    ) {
        $pdo = DB::factory('database');
        $aclNames = implode(
            ',',
            array_map(
                function ($value) use ($pdo) {
                    return $pdo->quote($value);
                },
                $acls
            )
        );

        $query = <<<SQL
        SELECT
            rt.id,
            rt.name,
            rt.description,
            rt.use_submenu
        FROM ReportTemplates rt
            JOIN report_template_acls rta ON rt.id = rta.report_template_id
            JOIN acls a                   ON rta.acl_id = a.acl_id
        WHERE a.name IN ($aclNames)
SQL;
        $queryparams = array();
        if ($templateName !== null) {
            $query .= " AND rt.`name` = ?";
            $queryparams[] = $templateName;
        }
        $query .= " ORDER BY 1, 2, 3";

        return $pdo->query($query, $queryparams);
    }

    /**
     * Loads the report template from the persistence model (database)
     */
    public static function retrieveReportTemplate($user, $template_id)
    {
        $pdo = DB::factory('database');

        $results = $pdo->query(
            '
                SELECT
                    template,
                    name,
                    title,
                    header,
                    footer,
                    format,
                    schedule,
                    delivery,
                    charts_per_page
                FROM ReportTemplates
                WHERE id = :id
            ',
            array('id' => $template_id)
        );

        if (count($results) == 0) {
            throw new \Exception(
                'No report template could be found having the id you specified'
            );
        }

        $templateClass = '\\ReportTemplates\\' . $results[0]['template'];

        $template_definition_file
            = dirname(__FILE__)
            . '/ReportTemplates/'
            . $results[0]['template'] . '.php';

        if (!file_exists($template_definition_file)) {
            throw new \Exception(
                "Report template definition could not be located"
            );
        }

        $r = array('general' => $results[0]);

        $r['charts'] = $pdo->query(
            '
                SELECT
                    chart_id,
                    ordering,
                    chart_date_description,
                    chart_title,
                    chart_drill_details,
                    timeframe_type
                FROM ReportTemplateCharts
                WHERE template_id = :id
                ORDER BY ordering ASC
            ',
            array('id' => $template_id)
        );

        return new $templateClass($user, $r);
    }
}
