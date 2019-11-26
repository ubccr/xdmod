<?php

namespace ReportTemplates;

use Models\Services\Parameters;
use DataWarehouse\Access\MetricExplorer;
use XDReportManager;

/**
 * Generates a report that includes macro expansion of variables defined in the
 * template. The substitution variables are generated from the users acls.
 * The internal identifier for a dimension has an _id postfix and the human
 * readable string has a _name postfix. For example, to get the service provider
 * name for a center staff user you would include the string ${provider_name} in
 * the template and ${provider_id} gives the numerical identifier for the provider.
 */
class VariableReportTemplate extends \ReportTemplates\aReportTemplate
{
    protected $variables = array();
    protected $replacements = array();

    public function __construct($user, $report_skeleton) {
        parent::__construct($user, $report_skeleton);


        $dims = array();
        foreach ($user->getAcls(true) as $acl) {
            $parameters = Parameters::getParameters($user, $acl);
            foreach ($parameters as $dimensionId => $valueId) {
                $valueName = MetricExplorer::getDimensionValueName($user, $dimensionId, $valueId);

                $dims[ '${' . $dimensionId . '_id}'] = $valueId;
                $dims[ '${' . $dimensionId . '_name}'] = $valueName;
            }
        }

        $this->variables = array_keys($dims);
        $this->replacements = array_values($dims);
    }

    public function buildReportFromTemplate(array &$params = array(), $report_id_suffix = null)
    {
        $rm = new XDReportManager($this->_user);

        if (!(is_null($report_id_suffix))){
            $id = $this->_user->getUserID() . '-' . $report_id_suffix;
        } else {
            $id = $this->_user->getUserID() . '-' . microtime(true);
        }

        $report_title = str_replace($this->variables, $this->replacements, $this->_report_skeleton['general']['title']);
        $report_header = str_replace($this->variables, $this->replacements, $this->_report_skeleton['general']['header']);
        $report_footer = str_replace($this->variables, $this->replacements, $this->_report_skeleton['general']['footer']);

        $rm->configureSelectedReport(
            $id,
            $rm->generateUniqueName($this->_report_skeleton['general']['name']),
            $report_title,
            $report_header,
            $report_footer,
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
                str_replace($this->variables, $this->replacements, $chart['chart_id']),
                str_replace($this->variables, $this->replacements, $chart['chart_title']),
                str_replace($this->variables, $this->replacements, $chart['chart_drill_details']),
                $chart['chart_date_description'],
                $chart['ordering'],
                $chart['timeframe_type'],
                'image'
            );
        }
    }
}
