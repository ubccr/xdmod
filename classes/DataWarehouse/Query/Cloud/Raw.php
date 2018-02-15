<?php
namespace DataWarehouse\Query\Cloud;

use CCR\DB;

/*
* @author Rudra Chakraborty
* @date 2018-02-15
*/
class Raw extends \DataWarehouse\Query\Query
{
    public function getQueryType()
    {
        return 'raw';
    }
    public function __construct(
        $start_date,
        $end_date,
        $db_profile = 'datawarehouse',
        $db_tablename = 'cm_euca_fact',
        array $parameters = array(),
        array $parameterDescriptions = array(),
        $single_stat = false
    ) {
        $this->_db_profile = $db_profile;
        $this->setRealmName('Cloud');
        $this->setDuration($start_date, $end_date, $db_tablename);
        $this->setParameters($parameters);
        $this->parameterDescriptions = $parameterDescriptions;
    }

    protected function setDuration($start_date, $end_date, $db_tablename)
    {
        $this->setDataTable("modw", $db_tablename);

        if(strtotime($start_date) == false) {
            throw new \Exception("start_date must be a date");
        }
        if(strtotime($end_date) == false) {
            throw new \Exception("end_date must be a date");
        }

        $this->_start_date = $start_date;
        $this->_end_date = $end_date;

        $dataTable = $this->getDataTable();

        $resourcefactTable = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), 'resourcefact3', 'rf');
        $this->addTable($resourcefactTable);

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($dataTable, "resource_id"),
                '=',
                new \DataWarehouse\Query\Model\TableField($resourcefactTable, "id")
            )
        );

        $personTable = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), 'person2', 'p');
        $this->addTable($personTable);

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($dataTable, "person_id"),
                '=',
                new \DataWarehouse\Query\Model\TableField($personTable, "id")
            )
        );

        $fosTable = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), 'fieldofscience2', 'fos');
        $this->addTable($fosTable);

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($dataTable, "fos_id"),
                '=',
                new \DataWarehouse\Query\Model\TableField($fosTable, "id")
            )
        );

        $accountTable = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), 'account2', 'acc');
        $this->addTable($accountTable);

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($dataTable, "account_id"),
                '=',
                new \DataWarehouse\Query\Model\TableField($accountTable, "id")
            )
        );

        $personPITable = new \DataWarehouse\Query\Model\Table(new \DataWarehouse\Query\Model\Schema('modw'), 'person2', 'ppi');
        $this->addTable($personPITable);

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($dataTable, "principalinvestigator_person_id"),
                '=',
                new \DataWarehouse\Query\Model\TableField($personPITable, "id")
            )
        );

        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "job_id"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "name"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($accountTable, "charge_number"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "submit_time"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "start_time"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "end_time"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($resourcefactTable, "code", 'resource'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($personTable, "first_name"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($personTable, "middle_name"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($personTable, "last_name"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "queue_id", 'queue'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($fosTable, "description", 'field_of_science'));

        $this->addField(new \DataWarehouse\Query\Model\TableField($personPITable, "first_name", 'PI_first_name'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($personPITable, "middle_name", 'PI_middle_name'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($personPITable, "last_name", 'PI_last_name'));

        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "wallduration", 'wall_time_seconds'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "waitduration", 'wait_time_seconds'));

        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "nodecount", "node_count"));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "processors", 'core_count'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "local_charge", 'local_charge_su'));
        $this->addField(new \DataWarehouse\Query\Model\TableField($dataTable, "adjusted_charge", 'adjusted_charge_su'));



        $this->addOrder(new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\Field("job_id"), 'desc', 'job_id'));

        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\Field("end_time_ts"),
                '>=',
                new \DataWarehouse\Query\Model\Field("unix_timestamp('{$this->_start_date}')")
            )
        );
        $this->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\Field("end_time_ts"),
                '<=',
                new \DataWarehouse\Query\Model\Field("unix_timestamp('{$this->_end_date}')+86399")
            )
        );
    }

    public function exportJsonStore($limit = 20, $offset = 0)
    {
        $fields = array();
        $records = array();
        $columns = array();
        $subnotes = array();
        $message = '';

        $count_result = DB::factory($this->_db_profile)->query($this->getCountQueryString());

        $count = $count_result[0]['row_count'];


        $statement = DB::factory($this->_db_profile)->handle()->prepare($this->getQueryString($limit, $offset));
        $statement->execute();
        $columnCount = $statement->columnCount();


        for($i = 0; $i < $columnCount; $i++)
        {
            $columnMeta = $statement->getColumnMeta($i);
            $fields[] =  array("name" => $columnMeta['name'], "type" => $columnMeta['native_type']);

            $columns[] = array("header" => ucwords(str_replace('_', ' ', $columnMeta['name'])), "width" => 100, "dataIndex" => $columnMeta['name'],
              "sortable" => true, 'editable' => false, 'align' => 'left','xtype' => 'gridcolumn', 'locked' => false);
        }

        while($result = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT))
        {
            $records[] = $result;
        }

        $returnData = array(
            "metaData" => array(
                "totalProperty" => "total",
                'messageProperty' => 'message',
                "root" => "records",
                "id" => "id",
                "fields" => $fields
            ),
            "success" => true,
            'message' =>$message,
            "total" => $count,
            "records" => $records,
            "columns" => $columns,
            'start_date' => $this->_start_date,
            'end_date' => $this->_end_date
        );

        return $returnData;
    }

    public function execute($limit = 20, $offset = 0)
    {
        throw new \Exception('Raw::getDataset() - Not Implemented');
    }

    public function getDataset($limit = 20, $offset = 1)
    {
        throw new \Exception('Raw::getDataset() - Not Implemented');
    }
}
