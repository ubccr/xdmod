<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * Class for adding group by nsf directorate to a query.
 */
class GroupByNSFDirectorate extends GroupBy
{
    public static function getLabel()
    {
        return HIERARCHY_TOP_LEVEL_LABEL;
    }

    public function __construct()
    {
        parent::__construct(
            'nsfdirectorate',
            array(),
            '
                SELECT DISTINCT
                    gt.directorate_id AS id,
                    gt.directorate_abbrev AS short_name,
                    gt.directorate_description AS long_name
                FROM fieldofscience_hierarchy gt
                WHERE 1
                ORDER BY gt.directorate_description
            '
        );
        $this->_id_field_name = 'directorate_id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'fos_id';
        $this->_long_name_field_name = 'directorate_description';
        $this->_short_name_field_name = 'directorate_abbrev';
        $this->_order_id_field_name = 'directorate_description';
        $this->table = new Table($this->schema, 'fieldofscience_hierarchy', 'fos');
        $this->info = HIERARCHY_TOP_LEVEL_INFO;
    }

    public function addWhereJoin(
        Query &$query,
        Table $data_table,
        $multi_group = false,
        $operation = '=',
        $whereConstraint = 'NULL'
    ) {
        $query->addTable($this->table);

        $fostable_id_field = new TableField($this->table, 'directorate_id');
        $datatable_fos_id_field = new TableField($data_table, 'fos_id');

        $query->addWhereCondition(
            new WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );

        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new WhereCondition(
                $fostable_id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            'SELECT id FROM modw.fieldofscience_hierarchy WHERE directorate_id IN (_filter_)',
            'fos_id'
        );
    }

    public function getDefaultDatasetType()
    {
        return 'aggregate';
    }

    public function getDefaultDisplayType($dataset_type = null)
    {
        if ($dataset_type == 'timeseries') {
            return 'area';
        } else {
            return 'pie';
        }
    }

    public function getDefaultShowAggregateLabels()
    {
        return 'y';
    }

    public function getDefaultShowGuideLines()
    {
        return 'n';
    }

    public function getDefaultShowErrorLabels()
    {
        return 'n';
    }

    public function getDefaultEnableErrors()
    {
        return 'n';
    }

    public function getDefaultEnableTrendLine()
    {
        return 'n';
    }
}
