<?php
namespace DataWarehouse\Query\Cloud;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\FormulaField;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\Schema;
use CCR\DB;

/**
 * The RawData class is reponsible for generating a query that returns
 * the set of fact table rows given the where conditions on the aggregate
 * table.
 */
class RawData extends \DataWarehouse\Query\Query implements \DataWarehouse\Query\iQuery
{
    public function __construct(
        $realmId,
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupById = null,
        $statisticId = null,
        array $parameters = array(),
        Log $logger = null
    ) {
        $realmId = 'Cloud';
        $schema = 'modw_cloud';

        parent::__construct(
            $realmId,
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupById,
            null,
            $parameters
        );

        // The same fact table row may correspond to multiple rows in the
        // aggregate table (e.g. a job that runs over two days).
        $this->setDistinct(true);

        // Override values set in Query::__construct() to use the fact table rather than the
        // aggregation table prefix from the Realm configuration.

        $this->setDataTable($schema, 'session_records');

        $dataTable = $this->getDataTable();
        $factTable = new Table(new Schema('modw_cloud'), "instance", "i");

        $resourcefactTable = new Table(new Schema('modw'), 'resourcefact', 'rf');
        $this->addTable($resourcefactTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "resource_id"),
            '=',
            new TableField($resourcefactTable, "id")
        ));

        $personTable = new Table(new Schema('modw'), 'person', 'p');

        $this->addTable($personTable);
        $this->addWhereCondition(new WhereCondition(
            new TableField($dataTable, "person_id"),
            '=',
            new TableField($personTable, "id")
        ));

        $this->addField(new TableField($resourcefactTable, "code", 'resource'));
        $this->addField(new TableField($personTable, "long_name", "name"));

        $this->addField(new TableField($factTable, "provider_identifier", "local_job_id"));
        $this->addField(new TableField($factTable, "instance_id", "jobid"));
        $this->addTable($factTable);

        $this->addWhereCondition(new WhereCondition(
            new TableField($factTable, "instance_id"),
            "=",
            new TableField($dataTable, "instance_id")
        ));

        $this->prependOrder(
            new \DataWarehouse\Query\Model\OrderBy(
                new TableField($factTable, 'provider_identifier'),
                'DESC',
                'end_time_ts'
            )
        );
    }

    protected function setDuration($start_date, $end_date) {
      $start_date_given = $start_date !== null;
      $end_date_given = $end_date !== null;

      if ($start_date_given && strtotime($start_date) == false) {
          throw new \Exception("start_date must be a date");
      }
      if ($end_date_given && strtotime($end_date) == false) {
          throw new \Exception("end_date must be a date");
      }

      $this->_start_date = $start_date_given ? $start_date : '0000-01-01';
      $this->_end_date = $end_date_given ? $end_date : '9999-12-31';

      $start_date_parsed = date_parse_from_format('Y-m-d', $this->_start_date);
      $end_date_parsed = date_parse_from_format('Y-m-d', $this->_end_date);

      $this->_start_date_ts = mktime(
          $start_date_parsed['hour'],
          $start_date_parsed['minute'],
          $start_date_parsed['second'],
          $start_date_parsed['month'],
          $start_date_parsed['day'],
          $start_date_parsed['year']
      );
      $this->_end_date_ts = mktime(
          23,
          59,
          59,
          $end_date_parsed['month'],
          $end_date_parsed['day'],
          $end_date_parsed['year']
      );

      list($this->_min_date_id, $this->_max_date_id) = $this->_aggregation_unit->getDateRangeIds($this->_start_date, $this->_end_date);

      if (!$start_date_given && !$end_date_given) {
          return;
      }

      $this->_date_table = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), $this->_aggregation_unit.'s', 'duration');

      $this->addTable($this->_date_table);

      $date_id_field = new \DataWarehouse\Query\Model\TableField($this->_date_table, 'id');
      $data_table_date_id_field = new \DataWarehouse\Query\Model\TableField($this->_data_table, "start_day_id");

      $this->addWhereCondition(
          new \DataWarehouse\Query\Model\WhereCondition(
              $date_id_field,
              '=',
              $data_table_date_id_field
          )
      );
      $this->addWhereCondition(
          new \DataWarehouse\Query\Model\WhereCondition(
              $data_table_date_id_field,
              'between',
              new \DataWarehouse\Query\Model\Field(
                  sprintf("%s and %s", $this->_min_date_id, $this->_max_date_id)
              )
          )
      );

      $duration_query = sprintf(
          "select sum(dd.hours) as duration from modw.%ss dd where dd.id between %s and %s",
          $this->aggregationUnitName,
          $this->_min_date_id,
          $this->_max_date_id
      );

      $duration_result = DB::factory($this->_db_profile)->query($duration_query);

      $this->setDurationFormula(
          new \DataWarehouse\Query\Model\Field(
              "(" . ( $duration_result[0]['duration'] == '' ? 1 : $duration_result[0]['duration'] ) . ")"
          )
      );
    }

    public function getQueryType(){
        return 'timeseries';
    }
}
