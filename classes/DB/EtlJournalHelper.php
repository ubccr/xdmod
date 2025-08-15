<?php

namespace DB;

/*
 * Helper class for managing the logs of the last_modified times
 * for the ETL process.
 */
class EtlJournalHelper
{

    public function __construct($schema, $table) {

        $this->schema = $schema;
        $this->table = $table;

        $this->dwdb = \CCR\DB::factory('datawarehouse', false);

        $this->lastModified = null;
        $this->mostRecent = null;
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

        $mostRecent = $this->dwdb->query(
            'SELECT UNIX_TIMESTAMP(last_modified) + 1 AS most_recent FROM `' . $this->schema . '`.`' . $this->table . '` ORDER BY last_modified DESC LIMIT 1'
        );

        if (count($mostRecent) > 0) {
            $this->mostRecent = $mostRecent[0]['most_recent'];
        }

        $lastRunInfo = $this->dwdb->query(
            'SELECT FROM_UNIXTIME(max_index) AS last_modified FROM modw_etl.log WHERE etlProfileName = ? ORDER BY max_index DESC LIMIT 1',
            array($this->schema . '.' . $this->table)
        );

        $this->dwdb->disconnect();

        if (count($lastRunInfo) > 0) {
            $this->lastModified = $lastRunInfo[0]['last_modified'];
        }

        return $this->lastModified;
    }

    /*
     * Add a log entry with the timestamps information that was queried
     * by the previous call to getLastModified
     */
    public function markAsDone($process_start_time, $process_end_time) {

        $markAsDone = $this->dwdb->prepare(
            'INSERT INTO modw_etl.log (etlProfileName, min_index, max_index, start_ts, end_ts) VALUES (?, UNIX_TIMESTAMP(?), ?, UNIX_TIMESTAMP(?), UNIX_TIMESTAMP(?))'
        );

        $markAsDone->execute(
            array(
                $this->schema . '.' . $this->table,
                $this->lastModified,
                $this->mostRecent,
                $process_start_time,
                $process_end_time
            )
        );

        $this->dwdb->disconnect();
    }
}
