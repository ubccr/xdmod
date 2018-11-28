<?php
/**
* @author Joe White
* @date 2015-03-25
*/
namespace DataWarehouse\Query\Jobs;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\FormulaField;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\Schema;

/*
 * @see DataWarehouse::Query::RawQuery
 */
class JobDataset extends \DataWarehouse\Query\RawQuery
{
    private $documentation = array();

    public function __construct(
        array $parameters,
        $stat = "all"
    ) {

        parent::__construct('Jobs', 'modw_aggregates', 'jobfact_by_day', array());

        $config = \Xdmod\Config::factory();

        $dataTable = $this->getDataTable();
        $joblistTable = new Table($dataTable->getSchema(), $dataTable->getName() . "_joblist", "jl");
        $factTable = new Table(new Schema('modw'), 'job_tasks', 'jt');

        $this->addTable($joblistTable );
        $this->addTable($factTable );

        $this->addWhereCondition(new WhereCondition(
            new TableField($joblistTable, "agg_id"),
            "=",
            new TableField($dataTable, "id")
        ));
        $this->addWhereCondition(new WhereCondition(
            new TableField($joblistTable, "jobid"),
            "=",
            new TableField($factTable, "job_id")
        ));

        if (isset($parameters['primary_key'])) {
            $pdostr = $this->nextPdoIndex($parameters['primary_key']);
            $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'job_id'), "=", $pdostr));
        } else {
            $matches = array();
            if (preg_match('/^(\d+)(?:[\[_](\d+)\]?)?$/', $parameters['job_identifier'], $matches)) {
                $pdostr = $this->nextPdoIndex($parameters['resource_id']);
                $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'resource_id'), '=', $pdostr));
                if (isset($matches[2])) {
                    $pdostr = $this->nextPdoIndex($matches[1]);
                    $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'local_jobid'), '=', $pdostr));

                    $pdostr = $this->nextPdoIndex($matches[2]);
                    $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'local_job_array_index'), '=', $pdostr));
                } else {
                    $pdostr = $this->nextPdoIndex($matches[1]);
                    $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'local_job_id_raw'), '=', $pdostr));
                }
            } else {
                throw new \Exception('invalid query parameters');
            }
        }

        if ($stat == "accounting") {
            $i = 0;
            foreach ($config['rawstatistics']['modw.job_tasks'] as $sdata) {
                $sfield = $sdata['key'];
                if ($sdata['dtype'] == 'accounting') {
                    $this->addField(new TableField($factTable, $sfield));
                    $this->documentation[$sfield] = $sdata;
                } elseif ($sdata['dtype'] == 'foreignkey') {
                    if (isset($sdata['join'])) {
                        $info = $sdata['join'];
                        $i += 1;
                        $tmptable = new Table(new Schema($info['schema']), $info['table'], "ft$i");
                        $this->addTable($tmptable);
                        $this->addWhereCondition(new WhereCondition(new TableField($factTable, $sfield), '=', new TableField($tmptable, "id")));
                        $fcol = isset($info['column']) ? $info['column'] : 'name';
                        $this->addField(new TableField($tmptable, $fcol, $sdata['name']));

                        $this->documentation[ $sdata['name'] ] = $sdata;
                    }
                }
            }
            $rf = new Table(new Schema('modw'), 'resourcefact', 'rf');
            $this->addTable($rf);
            $this->addWhereCondition(new WhereCondition(new TableField($factTable, 'resource_id'), '=', new TableField($rf, 'id')));
            $this->addField(new TableField($rf, 'timezone'));
            $this->documentation['timezone'] = array(
                "name" => "Timezone",
                "documentation" => "The timezone of the resource.",
                "group" => "Administration",
                'visibility' => 'public',
                "per" => "resource");
        }
        else
        {
            $this->addField(new TableField($factTable, "job_id", "jobid"));
            $this->addField(new TableField($factTable, "local_jobid", "local_job_id"));

            $rt = new Table(new Schema("modw"), "resourcefact", "rf");
            $this->joinTo($rt, "task_resource_id", "code", "resource");

            $pt = new Table(new Schema('modw'), 'person', 'p');
            $this->joinTo($pt, "person_id", "long_name", "name");

            $st = new Table(new Schema('modw'), 'systemaccount', 'sa');
            $this->joinTo($st, "systemaccount_id", "username", "username");
        }
    }

    /**
     * helper function to join the data table to another table
     */
    private function joinTo($othertable, $joinkey, $otherkey, $colalias, $idcol = "id")
    {
        $this->addTable($othertable);
        $this->addWhereCondition(new WhereCondition(new TableField($this->getDataTable(), $joinkey), '=', new TableField($othertable, $idcol)));
        $this->addField(new TableField($othertable, $otherkey, $colalias));
    }

    public function getColumnDocumentation()
    {
        return $this->documentation;
    }
}
