<?php

namespace DB;

/*
 * Helper class for managing the logs of the last_modified times
 * for the ETL process.
 */
class EtlJournalHelper
{

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

    /*
     * Return the timestamp of the latest record that
     * was sucessfully processed. Or null if there is no
     * recorded log entry.
     *
     * This function also stores the timestamp of the latest
     * record that exists in the source table.
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
     * Add a log entry with the timestamps information that was queried
     * by the previous call to getLastModified
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
