<?php
/**
 * Log summary class.
 *
 * Stores data summarizing the most recent invocation of a process that was logged using a specified
 * "ident".
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace Log;

use CCR\DB;

require_once 'Log.php';

class Summary
{

    /**
     * Database handle.
     *
     * @var DB
     */
    protected $dbh = null;

    /**
     * "ident" value used by the logger.
     *
     * @var string
     */
    protected $ident = null;

    /**
     * Time the process started (Y-m-d H:i:s).
     *
     * @var string
     */
    protected $processStartTime = null;

    /**
     * Time the process ended (Y-m-d H:i:s).
     *
     * @var string
     */
    protected $processEndTime = null;

    /**
     * Row "id" column value of the first log entry of the process.
     *
     * @var int
     */
    protected $processStartRowId = null;

    /**
     * Row "id" column value of the last log entry of the process.
     *
     * @var int
     */
    protected $processEndRowId = null;

    /**
     * Start time of the data the process is using. Used for ingestors
     * that process data in a specific date range.
     *
     * @var string
     */
    protected $dataStartTime = null;

    /**
     * End time of the data the process is using. Used for ingestors
     * that process data in a specific date range.
     *
     * @var string
     */
    protected $dataEndTime = null;

    /**
     * The number of warnings that were logged by the process.
     *
     * @var int
     */
    protected $warningCount = 0;

    /**
     * The number of errors that were logged by the process.
     *
     * @var int
     */
    protected $errorCount = 0;

    /**
     * The number of critical messages that were logged by the process.
     *
     * @var int
     */
    protected $criticalCount = 0;

    /**
     * The number of alerts that were logged by the process.
     *
     * @var int
     */
    protected $alertCount = 0;

    /**
     * The number of emergencies that were logged by the process.
     *
     * @var int
     */
    protected $emergencyCount = 0;

    /**
     * The number of records that were examined by the process.
     *
     * @var array
     */
    protected $recordCounts = array();

    /**
     * The array keys used by the logger to extract record counts from log messages. By default
     * there are no keys since this is an expensive operation and not all summaries have record
     * counts.
     *
     * @var array
     */
    protected $recordCountKeys = array();

    /**
     * Constructor.
     *
     * @param string $ident The logger "ident".
     * @param bool $queryRecordCounts Flag for querying record counts from the database,
     *   disable when returning the summary configuration for the internal dashboard tab
     *   panel.
     */

    protected function __construct($ident, $queryRecordCounts)
    {
        $this->ident = $ident;

        $this->dbh = DB::factory('logger');

        $row = $this->_getProcessStartRow();
        if ($row !== null) {
            $this->processStartRowId = $row['id'];
            $data = json_decode($row['message'], true);
            $this->processStartTime = $data['process_start_time'];
        }

        $row = $this->_getProcessEndRow();
        if ($row !== null) {
            $this->processEndRowId = $row['id'];
            $data = json_decode($row['message'], true);
            $this->processEndTime = $data['process_end_time'];
        }

        $data = $this->_getDataTimes();
        if ($data !== null) {
            $this->dataStartTime = $data['data_start_time'];
            $this->dataEndTime   = $data['data_end_time'];
        }

        $data = $this->_getPriorityCounts();
        if ($data !== null) {
            if (isset($data[PEAR_LOG_WARNING])) {
                $this->warningCount = $data[PEAR_LOG_WARNING];
            }
            if (isset($data[PEAR_LOG_ERR])) {
                $this->errorCount = $data[PEAR_LOG_ERR];
            }
            if (isset($data[PEAR_LOG_CRIT])) {
                $this->criticalCount = $data[PEAR_LOG_CRIT];
            }
            if (isset($data[PEAR_LOG_ALERT])) {
                $this->alertCount = $data[PEAR_LOG_ALERT];
            }
            if (isset($data[PEAR_LOG_EMERG])) {
                $this->emergencyCount = $data[PEAR_LOG_EMERG];
            }
        }

        // Allow disabling of the expensive database queries if we do not need them (e.g., when
        // querying the summaryu configuration).

        if ( $queryRecordCounts )
          $this->recordCounts = $this->_getRecordCounts();
    }

    /**
     *  Factory method for instantiating the Summary class. The default Summary class has an empty
     *  set of $recordCountKeys so an expensive database operation is not performed when it is not
     *  needed. Summary-specific classes that define these keys are created when this query is
     *  needed.
     *
     * @param string $ident The logger "ident".
     * @param bool $queryRecordCounts Flag indicating whether or not a query of records from the log
     *   tables should be made. Defaults to FALSE so we only do this expensive query when needed.
     *
     * @return The Summary class.
     */

    public function factory($ident, $queryRecordCounts = FALSE)
    {
      if ( empty($ident) ) {
        throw new \Exception('"ident" required');
      }

      // If a ident-string specific summary class exists load it, otherwise use the default.

      $summary = "Log\Summary\\$ident";
      if ( class_exists($summary) )
        return new $summary($ident, $queryRecordCounts);
      else
        return new Summary($ident, $queryRecordCounts);
    }

    public function getProcessStartRowId()
    {
        return $this->processStartRowId;
    }

    public function getProcessEndRowId()
    {
        return $this->processEndRowId;
    }

    public function getData()
    {
        return array_merge(
            array(
                'process_start_time'    => $this->processStartTime,
                'process_end_time'      => $this->processEndTime,
                'data_start_time'       => $this->dataStartTime,
                'data_end_time'         => $this->dataEndTime,
                'warning_count'         => $this->warningCount,
                'error_count'           => $this->errorCount,
                'critical_count'        => $this->criticalCount,
                'alert_count'           => $this->alertCount,
                'emergency_count'       => $this->emergencyCount,
            ),
            $this->recordCounts
        );
    }

    private function _getProcessStartRow()
    {
        return $this->_getRow(array(
            array('priority', '=',    PEAR_LOG_NOTICE),
            array('message',  'LIKE', '%"process_start_time":%'),
        ));
    }

    private function _getProcessEndRow()
    {
        if ($this->processStartRowId === null) {
            return null;
        }

        return $this->_getRow(array(
            array('priority', '=',    PEAR_LOG_NOTICE),
            array('message',  'LIKE', '%"process_end_time":%'),
            array('id',       '>',    $this->processStartRowId),
        ));
    }

    private function _getDataTimes()
    {
        $constraints = array(
            array('priority', '=',    PEAR_LOG_NOTICE),
            array('message',  'LIKE', '%"data_start_time":%'),
        );

        if ($this->processStartRowId !== null) {
            $constraints[] = array('id', '>', $this->processStartRowId);
        }

        if ($this->processEndRowId !== null) {
            $constraints[] = array('id', '<', $this->processEndRowId);
        }

        $row = $this->_getRow($constraints);

        if ($row === null) {
            return null;
        }

        return json_decode($row['message'], true);
    }

    private function _getPriorityCounts()
    {
        $sql = 'SELECT priority, COUNT(*) AS count FROM log_table';

        $clauses = array();
        $params = array();

        if ($this->ident !== null) {
            $clauses[] = 'ident = ?';
            $params[]  = $this->ident;
        }

        if ($this->processStartRowId !== null) {
            $clauses[] = 'id > ?';
            $params[]  = $this->processStartRowId;
        }

        if ($this->processEndRowId !== null) {
            $clauses[] = 'id < ?';
            $params[]  = $this->processEndRowId;
        }

        if (count($clauses) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' GROUP BY priority';

        $rows = $this->dbh->query($sql, $params);

        $countForPriority = array();

        foreach ($rows as $row) {
            $countForPriority[$row['priority']] = $row['count'];
        }

        return $countForPriority;
    }

    private function _getRecordCounts()
    {
        if ( count($this->recordCountKeys) == 0 ) return array();
        $counts = array();

        foreach ($this->recordCountKeys as $key) {
            $counts[$key . '_count'] = 0;

            $constraints = array(
                 array('priority', '=',    PEAR_LOG_NOTICE),
                 array('message',  'LIKE', '%"' . $key . '":%'),
            );

            if ($this->processStartRowId !== null) {
                $constraints[] = array('id', '>', $this->processStartRowId);
            }

            if ($this->processEndRowId !== null) {
                $constraints[] = array('id', '<', $this->processEndRowId);
            }

            list($sql, $params) = $this->_buildQuery(
                $constraints,
                true,
                false
            );

            $rows = $this->dbh->query($sql, $params);

            foreach ($rows as $row) {
                $data = json_decode($row['message'], true);
                $counts[$key . '_count'] += $data[$key];
            }
        }
        
        return $counts;
    }

    protected function _getRow(array $constraints = array())
    {
        list($sql, $params) = $this->_buildQuery($constraints);

        $rows = $this->dbh->query($sql, $params);

        if (count($rows) === 0) {
            return null;
        }

        return $rows[0];
    }

    protected function _buildQuery(
        array $constraints = array(),
        $order = true,
        $limit = true
    ) {
        $sql = 'SELECT id, logtime, priority, message FROM log_table';

        $clauses = array();
        $params = array();

        if ($this->ident !== null) {
            $clauses[] = 'ident = ?';
            $params[]  = $this->ident;
        }

        foreach ($constraints as $constraint) {
            list($column, $op, $value) = $constraint;
            $clauses[] = "$column $op ?";
            $params[]  = $value;
        }

        if (count($clauses) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if ($limit) {
            $sql .= ' ORDER BY id DESC';
        }

        if ($limit) {
            $sql .= ' LIMIT 1';
        }

        return array($sql, $params);
    }
}

