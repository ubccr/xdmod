<?php

namespace DB;

/*
 * Helper class for managing the logs of the last_modified times
 * for the ETL process.
 */
class EtlJournalHelper
{

    /**
     * Helper class for managing the logs of the last_modified times for ingestion
     * and aggregation (ETL) processes. Construct this object with the information
     * about the database table that is to be read from by the ETL process and then
     * you can use the getLastModified() to retrieve the value of the timestamp
     * that should be used to select new records. Once the
     * ETL process has successfully completed then call markAsDone() to record the
     * successful data load.
     *
     * PSEUDO code example:
     *
     * $journal = new EtlJournalHelper( [name of table to be written to]);
     * $last_modified = $journal->getLastModified();
     *
     * $process_start_time = date('Y-m-d H:i:s');
     * [ run ETL pipeline that selects rows newer than $last_modified ]
     * $process_end_time = date('Y-m-d H:i:s');
     *
     * $journal->markAsDone($process_start_time, $process_end_time);
     *
     * Theory of operations:
     * getLastModified() queries the source table to find the newest record. This
     * value is stored in memory and will be written to the database _if_ ingestion
     * completes without error (i.e. markAsDone is called). getLastModified
     * then retrieves the timestamp of the last successful ingest and returns it
     *
     * This is safe against race conditions as long as the 'last_modfied' column in
     * the source table contains non-decreasing timestamps. If the source table
     * changes between the call to getLastModified() and the actual ingest then the
     * changed rows will just be ingested again next time. So more compute cycles
     * used but no data loss.
     *
     * @param string $schema The name of the database that contains the table
     *                       that will be written to.
     * @param string $table  The name of the table that will be written to.
     * @param string $database The name of the configuration section that
     *                       contains the database credentials
     * @param string $last_modified_column The name of the column in the
     *                       target table that contains a timestamp of when
     *                       the row was updated
     */
    public function __construct($schema, $table, $database = 'datawarehouse', $last_modified_column = 'last_modified') {

        $this->schema = $schema;
        $this->table = $table;
        $this->lastModifiedColumn = $last_modified_column;

        $this->sourcedb = \CCR\DB::factory($database, false);
        $this->dwdb = \CCR\DB::factory('datawarehouse', false);

        // These are both POSIX timestamps
        $this->lastModifiedTs = null;
        $this->mostRecentTs = null;
    }

    /**
     * Return the timestamp of the latest record that
     * was sucessfully processed. Or null if there is no
     * recorded log entry.
     *
     * This function also stores the timestamp of the latest
     * record that exists in the source table.
     * @return string posix timestamp of the row in the source table that
     *          had been successfully ingested previously or null if there is
     *          no previous run logged.
     */
    public function getLastModified() {

        if (get_class($this->sourcedb) == 'CCR\DB\PostgresDB') {
            $srcQuery = 'SELECT FLOOR(EXTRACT(EPOCH FROM ' . $this->lastModifiedColumn . ')) AS most_recent FROM ' . $this->schema . '.' . $this->table . ' ORDER BY ' . $this->lastModifiedColumn . ' DESC LIMIT 1';
        } else {
            $srcQuery = 'SELECT UNIX_TIMESTAMP( MAX(' . $this->lastModifiedColumn . ')) + 1 AS most_recent FROM `' . $this->schema . '`.`' . $this->table . '`';
        }

        $mostRecent = $this->sourcedb->query($srcQuery);

        $this->sourcedb->disconnect();

        if (count($mostRecent) > 0) {
            $this->mostRecentTs = $mostRecent[0]['most_recent'];
        }

        $lastRunInfo = $this->dwdb->query(
            'SELECT FROM_UNIXTIME(max_index) AS last_modified, max_index AS last_modified_ts  FROM modw_etl.log WHERE etlProfileName = ? ORDER BY max_index DESC LIMIT 1',
            array($this->schema . '.' . $this->table)
        );

        $this->dwdb->disconnect();

        $lastModifiedStr = null;

        if (count($lastRunInfo) > 0) {
            $this->lastModifiedTs = $lastRunInfo[0]['last_modified_ts'];
            if (get_class($this->sourcedb) == 'CCR\DB\PostgresDB') {
                $dti = new \DateTimeImmutable('@' . $this->lastModifiedTs);
                $lastModifiedStr = $dti->format(\DateTimeInterface::RFC3339);
            } else {
                $lastModifiedStr = $lastRunInfo[0]['last_modified'];
            }
        }

        return $lastModifiedStr;
    }

    /*
     * Record the successful ingestion of the source table with timestamps
     * form a the previous call to getLastModified. You MUST only call this
     * function on a successful ETL run. DO NOT call in an exception handler
     * or finally clause or without checking for errors.
     * The timestamps are recorded in the database to allow analysis of ETL
     * process runtimes.
     *
     * @param string $process_start_time The time before the ETL process started in the format date('Y-m-d H:i:s')
     * @param string $process_end_time The time after the ETL process ended in the format date('Y-m-d H:i:s')
     */
    public function markAsDone($process_start_time, $process_end_time) {

        $markAsDone = $this->dwdb->prepare(
            'INSERT INTO modw_etl.log (etlProfileName, min_index, max_index, start_ts, end_ts) VALUES (?, ?, ?, UNIX_TIMESTAMP(?), UNIX_TIMESTAMP(?))'
        );

        $markAsDone->execute(
            array(
                $this->schema . '.' . $this->table,
                $this->lastModifiedTs,
                $this->mostRecentTs,
                $process_start_time,
                $process_end_time
            )
        );

        $this->dwdb->disconnect();
    }
}
