<?php

/**
 * @author: Amin Ghadersohi 7/1/2010
 */
class JobTimeseriesAggregator extends Aggregator
{
    public $_fields;
    public $_tablename;

    /**
     * @see Aggregator->$realmName
     */
    protected $realmName = 'Jobs';

    private $_time_period;

    function __construct($time_period)
    {
        $this->_time_period = $time_period;

        if ($time_period != 'day' && $time_period != 'week' && $time_period != 'month' && $time_period != 'quarter' && $time_period != 'year') {
            throw new Exception("Time period {$this->_time_period} is invalid.");
        }

        $this->_tablename = "jobfact_by_{$this->_time_period}";

		$wallduration_case_statement =  $this->getDistributionSQLCaseStatement('wallduration', ':seconds', 'start_time_ts', 'end_time_ts', ":{$this->_time_period}_start_ts",":{$this->_time_period}_end_ts");

		$local_charge_case_statement =  $this->getDistributionSQLCaseStatementWithDtype('local_charge', 'DECIMAL(18,3)', ':seconds', 'start_time_ts', 'end_time_ts', ":{$this->_time_period}_start_ts",":{$this->_time_period}_end_ts");


        $this->_fields = array(
            new TableColumn("{$this->_time_period}_id", 'int(11)', ":{$this->_time_period}_id", true, false, "The id related to modw.{$this->_time_period}s."),
            new TableColumn('year', 'int(4)', ':year', true, false, "The year of the {$this->_time_period}"),
            new TableColumn("{$this->_time_period}", 'int(3)', ":{$this->_time_period}", true, false, "The {$this->_time_period} of the year."),

            new TableColumn('person_id', 'int(7)', '', true, true, "The id of the person that ran the jobs.", true),
            new TableColumn('organization_id', 'int(6)', '', true, true, "The organization of the resource that the jobs ran on.", true),
            new TableColumn('person_organization_id', 'int(6)', '', true, true, "The organization of the person that ran the jobs.", true),
            new TableColumn('person_nsfstatuscode_id', 'int(3)', '', true, true, "The NSF status code of the person that ran the jobs.", true),



            new TableColumn('resource_id', 'int(5)', '', true, true, "The resource on which the jobs ran", true),
            new TableColumn('resourcetype_id', 'int(3)', '', true, true, "The type of the resource on which the jobs ran.", true),
            new TableColumn('queue_id', 'char(50)', '', true, true, "The queue of the resource on which the jobs ran.", true),
            new TableColumn('fos_id', 'int(4)', '', true, true, "The field of science of the project to which the jobs belong.", true),

            new TableColumn('account_id', 'int(6)', '', true, true, "The id of the account record from which one can get charge number", true),
            new TableColumn('systemaccount_id', 'int(8)', '', true, true, "The id of the system account record from which one can get username on the resource the job ran on.", true),
            new TableColumn('allocation_id', 'int(7)', '', true, true, "The id of allocation these jobs used to run", true),
            new TableColumn('principalinvestigator_person_id', 'int(7)', '', true, true, "The PI that owns the project that funds these jobs", true),
            new TableColumn('piperson_organization_id', 'int(7)', 'coalesce(piperson_organization_id, 0)', true, false, "The organization of the PI that owns the project that funds these jobs", true),
            new TableColumn('job_time_bucket_id', 'int(3)', '(select id from job_times jt where wallduration >= jt.min_duration and wallduration <= jt.max_duration)', true, false, "Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.", true),
            new TableColumn('node_count', 'int(8)', 'node_count', true, false, "Number of nodes each of the jobs used."),
		    new TableColumn('processors', 'int(8)', '(case when resource_id = 2020 then 1 else processors end)', true, false, "Number of processors each of the jobs used.", true),
            new TableColumn('processorbucket_id', 'int(3)', '(select id from processor_buckets pb where case when resource_id = 2020 then 1 else processors end between pb.min_processors and pb.max_processors)', false, true, "Processor bucket or job size buckets are prechosen in the modw.processor_buckets table.", true),
            new TableColumn('submitted_job_count', 'int(11)', "sum(case when submit_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that started during this {$this->_time_period}. "),
            new TableColumn('job_count', 'int(11)', "sum(case when end_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that ended during this {$this->_time_period}. "),
            new TableColumn('started_job_count', 'int(11)', "sum(case when start_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that started during this {$this->_time_period}. "),
            new TableColumn('running_job_count', 'int(11)', 'sum(case when resource_id = 2020 then processors else 1 end)', false, true, "The number of jobs that were running during this {$this->_time_period}."),

            new TableColumn('wallduration', 'decimal(18,0)', "coalesce(sum( $wallduration_case_statement),0)", false, true, "(seconds) The wallduration of the jobs that were running during this period. This will only count the walltime of the jobs that fell during this {$this->_time_period}. If a job started in the previous {$this->_time_period}(s) the wall time for that {$this->_time_period} will be added to that {$this->_time_period}. Same logic is true if a job ends not in this {$this->_time_period}, but upcoming {$this->_time_period}s. "),
            new TableColumn('sum_wallduration_squared', 'double', "coalesce(sum( pow($wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of wallduration of the jobs that were running during this period. This will only count the walltime of the jobs that fell during this {$this->_time_period}. If a job started in the previous {$this->_time_period}(s) the wall time for that {$this->_time_period} will be added to that {$this->_time_period}. Same logic is true if a job ends not in this {$this->_time_period}, but upcoming {$this->_time_period}s. "),
            new TableColumn('waitduration', 'decimal(18,0)', "sum(
                    case when (start_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts )
                                  then waitduration
                         else 0
                    end
                )", false, true, "(seconds)The amount of time jobs waited to execute during this {$this->_time_period}."),
            new TableColumn('sum_waitduration_squared', 'double', "sum(
                    case when  (start_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts )
                                  then pow(waitduration,2)
                         else 0
                    end
                )", false, true, "(seconds)The sum of the square of the amount of time jobs waited to execute during this {$this->_time_period}."),
            new TableColumn('local_charge', 'decimal(18,0)', "sum( $local_charge_case_statement)", false, true, "The amount of the local_charge charged to jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its local_charge is distributed linearly across the {$this->_time_period}s it used."),

            new TableColumn('sum_local_charge_squared', 'double', "sum( pow( $local_charge_case_statement, 2) )", false, true, "The sum of the square of local_charge of jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its local_charge is distributed linearly across the {$this->_time_period}s it used."),

            new TableColumn('cpu_time', 'decimal(18,0)', "coalesce(sum( processors*$wallduration_case_statement),0)", false, true, "(seconds) The amount of the cpu_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its cpu_time is distributed linearly across the {$this->_time_period}s it used."),
            new TableColumn('sum_cpu_time_squared', 'double', "coalesce(sum( pow(processors*$wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of the amount of the cpu_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its cpu_time is distributed linearly across the {$this->_time_period}s it used."),

			new TableColumn('node_time', 'decimal(18,0)', "coalesce(sum( nodecount*$wallduration_case_statement),0)", false, true, "(seconds) The amount of the node_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its node_time is distributed linearly across the {$this->_time_period}s it used."),
            new TableColumn('sum_node_time_squared', 'double', "coalesce(sum( pow(nodecount*$wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of the amount of the node_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its node_time is distributed linearly across the {$this->_time_period}s it used."),

            new TableColumn('sum_weighted_expansion_factor', 'decimal(18,0)',
                  "sum( ((wallduration + waitduration) / wallduration) * nodecount * coalesce($wallduration_case_statement,0))", false, true, " this is the sum of expansion factor per job multiplied by nodecount and the [adjusted] duration of jobs that ran in this {$this->_time_period}s. "),
            new TableColumn('sum_job_weights', 'decimal(18,0)',
                  "sum( nodecount * coalesce($wallduration_case_statement,0))", false, true, " this is the sum of (nodecount multipled by the [adjusted] duration) for jobs that ran in this {$this->_time_period}s. ")

        );

        if ($time_period == 'year') {
            unset($this->_fields[2]);
        }


    }

    private function checkResourceSpecs($modwdb, $start_date, $end_date)
    {
		if(!static::$__initialized)
		{
			//check to see all resources with jobs have processor info
			$resources_without_info_result = $modwdb->query("
				select distinct(resource_id) as resource_id
				from jobfact
				where
					start_time_ts between unix_timestamp('$start_date') and unix_timestamp('$end_date')
				  and resource_id not in (select distinct(resource_id) from resourcespecs where processors is not null)
			");

			if (count($resources_without_info_result) > 0) {
				$resources = array();
				foreach ($resources_without_info_result as $resource) {
					$resources[] = $resource['resource_id'];
				}
                $howToUpdateResource =
                    \xd_utilities\getConfiguration('features', 'xsede') == 'on'
                    ? 'update the resource config files in "' . CONFIG_DIR . '/ingestors/TGcDB"'
                    : 'update the resource definition file located at "' . CONFIG_DIR . '/resource_specs.json"'
                ;
				throw new Exception('New Resource(s) in resourcespecs table does not have processor and node information. Enum of resource_id(s): ' . implode(',', $resources) .
									"\n".
									'To fix this problem, figure out values for processors, ppn and nodes count for each resource and ' . $howToUpdateResource . '.');
			}

			static::$__initialized = true;
		}
    }

    private function getDateIds($modwdb, $dest_schema, $start_date, $end_date)
    {
        $query = "SELECT DISTINCT
                      p.id,
                      p.`year` as year_id,
                      p.`{$this->_time_period}`,
                      p.{$this->_time_period}_start,
                      p.{$this->_time_period}_end,
                      p.{$this->_time_period}_start_ts,
                      p.{$this->_time_period}_end_ts,
                      p.hours,
                      p.seconds
                  FROM {$this->_time_period}s p,
                      (SELECT
                          jf.submit_time_ts, jf.end_time_ts
                      FROM
                          modw.jobfactstatus js,
                          modw.jobfact jf
                      WHERE
                          jf.job_id = js.job_id
                          AND js.aggregated_{$this->_time_period} = 0) jf
                  WHERE
                      jf.end_time_ts between p.{$this->_time_period}_start_ts and p.{$this->_time_period}_end_ts or
                      p.{$this->_time_period}_end_ts between jf.submit_time_ts and jf.end_time_ts
                  ORDER BY 2 DESC, 3 DESC";

        return $modwdb->query( $query );
    }


    private function createTables($modwdb, $dest_schema, $append, $infinidb)
    {
        if ($append == true) {

            $altertable_statement = "alter table {$dest_schema}.{$this->_tablename}";
            foreach ($this->_fields as $field) {
                $altertable_statement .= " change {$field->getName()} {$field->getName()} {$field->getType()} " . ($field->isInGroupBy() ? "NOT NULL" : "NULL") . " COMMENT '" . ($field->isInGroupBy() ? "DIMENSION" : "FACT") . ": {$field->getComment()}', ";
            }
            $altertable_statement = trim($altertable_statement, ", ");

            // We are no longer modifying the table in this ingestor and it has been commented out
            // to prevent conflicts. Table management for the modw_aggregates.jobfact_by_* tables
            // are now handled by the new ETL process that aggregates the OSG data. -smg
            //
            // $modwdb->handle()->prepare($altertable_statement)->execute();

        } else {
            $createtable_statement = "create table if not exists {$dest_schema}." . $this->_tablename . " ( ";

            foreach ($this->_fields as $field) {
                $createtable_statement .= " {$field->getName()} {$field->getType()} " . ($field->isInGroupBy() ? "NOT NULL" : "NULL") . " COMMENT '" . ($field->isInGroupBy() ? "DIMENSION" : "FACT") . ": {$field->getComment()}', ";
            }
            $createtable_statement = trim($createtable_statement, ", ");

            $createtable_statement .= ") engine = " . ($infinidb ? 'infinidb' : 'myisam') . " COMMENT='Jobfacts aggregated by {$this->_time_period}.';";
            //echo $createtable_statement;

            $modwdb->handle()->prepare("drop table if exists {$dest_schema}." . $this->_tablename)->execute();
            $modwdb->handle()->prepare($createtable_statement)->execute();


            if ($infinidb !== true) {
                $index_fieldnames = array();
                foreach ($this->_fields as $field) {
                    if ($field->isInGroupBy()) {
                        $index_fieldnames[] = $field->getName();
                        $modwdb->handle()->prepare("create index index_{$this->_tablename}_{$field->getName()} using
                                                        hash on {$dest_schema}.{$this->_tablename} (" . $field->getName() . ")")->execute();
                    }
                }
            }
        }
    }

    private function buildSqlStatements($dest_schema)
    {
        $noncube_fields = array();
        $groupby_fields = array();
        $formula_fields = array();

        foreach ($this->_fields as $field) {
            if (!$field->isInCube()) {
                $noncube_fields[] = $field;
            } else if ($field->isInGroupBy()) {
                $groupby_fields[] = $field;
            } else {
                $formula_fields[] = $field;
            }
        }

        $insert_statement = "insert into {$dest_schema}." . $this->_tablename . " ( ";

        foreach ($noncube_fields as $field) {
            $insert_statement .= "{$field->getName()}, ";
        }
        foreach ($groupby_fields as $field) {
            $insert_statement .= "{$field->getName()}, ";
        }
        foreach ($formula_fields as $field) {
            $insert_statement .= "{$field->getName()}, ";
        }
        $insert_statement = trim($insert_statement, ", ");
        $insert_statement .= "  ) values (";
        foreach ($noncube_fields as $field) {
            $insert_statement .= ":{$field->getName()}, ";
        }
        foreach ($groupby_fields as $field) {
            $insert_statement .= ":{$field->getName()}, ";
        }
        foreach ($formula_fields as $field) {
            $insert_statement .= ":{$field->getName()}, ";
        }
        $insert_statement = trim($insert_statement, ", ");
        $insert_statement .= "  )";

        $this->_logger->debug($insert_statement);

        $select_statement = "
            select SQL_NO_CACHE distinct
            ";
        foreach ($noncube_fields as $field) {
            $select_statement .= "{$field->getFormula()} as {$field->getName()}, ";
        }
        foreach ($groupby_fields as $field) {
            $select_statement .= "{$field->getName()}, ";
        }
        foreach ($formula_fields as $field) {
            $select_statement .= "{$field->getFormula()} as {$field->getName()}, ";
        }
        $select_statement = trim($select_statement, ", "); //use index (index_jobfact_time_ts)

        $select_statement .= "
            from jobfact
            where
            (end_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts) or
            (:{$this->_time_period}_end_ts between start_time_ts and end_time_ts)
            group by ";
        foreach ($noncube_fields as $field) {
            $select_statement .= "{$field->getName()}, ";
        }
        foreach ($groupby_fields as $field) {
            $select_statement .= "{$field->getName()}, ";
        }
        $select_statement = trim($select_statement, ", ");

        $this->_logger->debug($select_statement);

        return array($insert_statement, $select_statement);
    }

    function execute($modwdb, $dest_schema, $start_date, $end_date, $append = true, $infinidb = false)
    {
        $this->checkResourceSpecs($modwdb , $start_date, $end_date);

        $this->_logger->info(  get_class($this) . ".execute(start_date: $start_date, end_date: $end_date, append: ". var_export($append, true) . ")" );

        $this->createTables($modwdb, $dest_schema, $append, $infinidb);

        if ($infinidb !== true) {

            list($insert_statement, $select_statement) = $this->buildSqlStatements($dest_schema);

            $prepared_insert_statement = $modwdb->handle()->prepare($insert_statement);

            $dates_results = $this->getDateIds($modwdb, $dest_schema, $start_date, $end_date);

            $statement = $modwdb->handle()->prepare($select_statement);

            foreach ($dates_results as $date_result) {
                $period_id       = $date_result['id'];
                $period_start    = $date_result["{$this->_time_period}_start"];
                $period_end      = $date_result["{$this->_time_period}_end"];
                $period_start_ts = $date_result["{$this->_time_period}_start_ts"];
                $period_end_ts   = $date_result["{$this->_time_period}_end_ts"];
                $year            = $date_result['year_id'];
                $time_period     = $date_result["{$this->_time_period}"];
                $period_hours    = $date_result["hours"];
                $period_seconds  = $date_result["seconds"];
                $this->_logger->debug(json_encode($date_result));

                $statement->execute(array(
                    "{$this->_time_period}_id" => $period_id,
                    "{$this->_time_period}" => $time_period,
                    'year' => $year,
                    "{$this->_time_period}_start_ts" => $period_start_ts,
                    "{$this->_time_period}_end_ts" => $period_end_ts,
                    'seconds' => $period_seconds
                ));

                if ($append) {

                  // If we are skipping resources, it may be that they are being handled during
                  // other ETL processes. Be sure not to delete them from the aggregates table or
                  // that may undo what those processes put in.

                  $skipHiddenResources = false;
                  $hiddenResourcesClause = "";
                  $deleteSql = "DELETE FROM {$dest_schema}.{$this->_tablename} WHERE {$this->_time_period}_id = $period_id";

                  try {
                    $skipHiddenResources = filter_var(\xd_utilities\getConfiguration('xsede_hidden_resources', 'skip_job_ingestion'), FILTER_VALIDATE_BOOLEAN);
                    $hiddenResourcesCsv = trim(\xd_utilities\getConfiguration('xsede_hidden_resources', 'resource_ids'));
                  } catch (\Exception $e) {}

                  if ( $skipHiddenResources && "" != $hiddenResourcesCsv ) {
                    $deleteSql .= " AND resource_id NOT IN ($hiddenResourcesCsv)";
                  }

                  $modwdb->handle()->prepare($deleteSql)->execute();

                }
                while ($row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                    $prepared_insert_statement->execute($row);
                }

            }
        }
        $this->_logger->debug('Optimizing table');
        $modwdb->handle()->prepare("optimize table {$dest_schema}.{$this->_tablename}")->execute();

        $modwdb->handle()->prepare("UPDATE modw.jobfactstatus SET aggregated_{$this->_time_period} = 1 WHERE 1")->execute();

        if( $this->_time_period == "year" ) {
            // Clean up entries that have been aggregated in all time periods
            // Only bother to do this for year aggregation because
            $modwdb->handle()->prepare("DELETE FROM modw.jobfactstatus WHERE aggregated_day = 1 AND aggregated_month = 1 AND aggregated_quarter = 1 AND aggregated_year = 1")->execute();
        }
    }
}
