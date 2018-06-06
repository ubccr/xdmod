<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

use Log;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;

/*
* @author Greg Dean
* @date 05/17/2018
*
* Group By Submission Venue
*/

class GroupBySubmissionVenue extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Submission Venue';
    }

    public function getInfo()
    {
        return "The venue that a job or cloud instance was initiated from.";
    }

    public function __construct()
    {

        parent::__construct(
            "submission_venue",
            array(),
            "SELECT DISTINCT
                sv.submission_venue_id AS id,
                sv.submission_venue AS short_name,
                sv.display AS long_name
             FROM
                submission_venue sv"
        );

        $this->_id_field_name = 'submission_venue_id';
        $this->_long_name_field_name = 'display';
        $this->_short_name_field_name = 'submission_venue';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new Schema('modw');
        $this->submission_venue_table = new Table($this->modw_schema, 'submission_venue', 'sv');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $id_field = new TableField($this->submission_venue_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $submission_venue_name_field = new TableField($this->submission_venue_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $submission_venue_shortname_field = new TableField($this->submission_venue_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->submission_venue_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        $datatable_submission_venue_id_field = new TableField($data_table, 'submission_venue_id');

        // Table to join to to get submission venue names
        $query->addTable($this->submission_venue_table);

        // Add fields to be returned from query
        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($submission_venue_name_field);
        $query->addField($submission_venue_shortname_field);

        // Add field that you are going to group by
        $query->addGroup($id_field);

        $query->addWhereCondition(new WhereCondition($id_field, '=', $datatable_submission_venue_id_field));
        $this->addOrder($query, $multi_group);

    }

    public function addWhereJoin(Query &$query, Table $data_table, $multi_group, $operation, $whereConstraint)
    {
        $submission_venue_id_field = new TableField($this->submission_venue_table, $this->_id_field_name);
        $datatable_submission_venue_id_field = new TableField($data_table, 'submission_venue_id');

        // construct the join between the main data_table and this group by table
        $query->addTable($this->submission_venue_table);

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new WhereCondition($submission_venue_id_field, '=', $datatable_submission_venue_id_field));

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(new WhereCondition($submission_venue_id_field, $operation, $whereConstraint));
    }

    public function addOrder(Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new OrderBy(new TableField($this->submission_venue_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'submission_venue_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select display as field_label from modw.submission_venue where id in (_filter_) order by submission_venue_id"
        );
    }
}
