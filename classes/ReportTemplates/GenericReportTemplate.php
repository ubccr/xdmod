<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace ReportTemplates;

use CCR\DB;
use Exception;
use XDReportManager;

/**
 * Generic report template.
 *
 * Copies the report template without making any changes.
 */
class GenericReportTemplate extends aReportTemplate
{

    /**
     * Build a report from a template.
     *
     * @param array $params Additional parameters.
     */
    public function buildReportFromTemplate(array &$params = array(), $report_id_suffix=null)
    {
        $rm = new XDReportManager($this->_user);

        if (!(is_null($report_id_suffix))){
            $id = $this->_user->getUserID() . '-' . $report_id_suffix;
        } else {
            $id = $this->_user->getUserID() . '-' . microtime(true);
        }

        $rm->configureSelectedReport(
            $id,
            $rm->generateUniqueName($this->_report_skeleton['general']['name']),
            $this->_report_skeleton['general']['title'],
            $this->_report_skeleton['general']['header'],
            $this->_report_skeleton['general']['footer'],
            $this->_report_skeleton['general']['font'],
            $this->_report_skeleton['general']['format'],
            $this->_report_skeleton['general']['charts_per_page'],
            $this->_report_skeleton['general']['schedule'],
            $this->_report_skeleton['general']['delivery']
        );

        $rm->insertThisReport($this->_report_skeleton['general']['name']);

        foreach ($this->_report_skeleton['charts'] as $chart) {
            $rm->saveCharttoReport(
                $id,
                $chart['chart_id'],
                $chart['chart_title'],
                $chart['chart_drill_details'],
                $chart['chart_date_description'],
                $chart['ordering'],
                $chart['timeframe_type'],
                'image'
            );
        }
    }
}
