<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * Default GroupBy used for aggregate storage queries.
 */
class GroupByNone extends GroupBy
{
    public static function getLabel()
    {
        return ORGANIZATION_NAME;
    }

    public function __construct()
    {
        parent::__construct('none', array());
    }

    public function getDefaultDatasetType()
    {
        return 'timeseries';
    }

    public function getInfo()
    {
        return 'Summarizes all Storage data.';
    }

    public function getDefaultDisplayType($datasetType = null)
    {
        return $datasetType === 'timeseries' ? 'line' : 'h_bar';
    }

    public function getDefaultCombineMethod()
    {
        return 'stack';
    }

    public function addOrder(
        Query &$query,
        $multiGroup = false,
        $dir = 'ASC',
        $prepend = false
    ) {
        // NO-OP
    }

    public function addWhereJoin(
        Query &$query,
        Table $dataTable,
        $multiGroup = false,
        $operation = '=',
        $whereConstraint = 'null'
    ) {
        // NO-OP
    }

    public function applyTo(
        Query &$query,
        Table $dataTable,
        $multiGroup = false
    ) {
        $query->addField(new FormulaField('-9999', $this->getIdColumnName($multiGroup)));

        $fieldLabel = "'" . ORGANIZATION_NAME . "'";

        $query->addField(new FormulaField($fieldLabel, $this->getLongNameColumnName($multiGroup)));
        $query->addField(new FormulaField($fieldLabel, $this->getShortNameColumnName($multiGroup)));
        $query->addField(new FormulaField($fieldLabel, $this->getOrderIdColumnName($multiGroup)));
    }

    public function pullQueryParameters(&$request)
    {
        return array();
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return array();
    }
}
