<?php

namespace CCR;

use CCR\DB\iDatabase;
use Exception;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * This class is meant to provide a means of writing log entries to a database within the Monolog framework.
 * Specifically, it utilizes a concrete implementation of CCR\DB\iDatabase to persist log entries to a database. It was
 * initially developed to aid in migration of XDMoD's logging infrastructure from PEAR Log to Monolog.
 *
 * @package CCR
 *
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
class CCRDBHandler extends AbstractProcessingHandler
{
    /**
     * @var iDatabase
     */
    private $db;

    /**
     * @var string the schema in which the log table is located.
     */
    private $schema;

    /**
     * @var string the table that is to be used for logging.
     */
    private $table;

    /**
     * CCRDBHandler constructor.
     *
     * @param iDatabase|null $db  A CCR\DB object to be used when writing log entries. If null, the 'logger' db will be used.
     * @param string|null $schema The schema that the log table will be located in. If null, this defaults to the
     *                            'database' property of the logger section in portal_settings.ini.
     * @param string|null $table  The table that is to be used when writing log entries. If null, this defaults to the
     *                            'table' property of the logger section in portal_settings.ini
     * @param int $level          The level at which this handler will write log entries. Defaults to DEBUG.
     * @param bool $bubble        Whether the messages that are handled can bubble up the stack or not
     *
     * @throws Exception If there is a problem retrieving the logger DB object.
     * @throws Exception If the 'database' property of the logger section in portal_settings.ini is not present or if its value is empty.
     * @throws Exception If the 'table' property of the logger section in portal_settings.ini is not present or if its value is empty.
     */
    public function __construct(iDatabase $db = null, $schema = null, $table = null, $level = Log::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        if (!isset($db)) {
            $db = DB::factory('logger');
        }

        if (!isset($schema)) {
            $schema = \xd_utilities\getConfiguration('logger', 'database');
        }

        if (!isset($table)) {
            $table = \xd_utilities\getConfiguration('logger', 'table');
        }

        $this->db = $db;
        $this->schema = $schema;
        $this->table = $table;
    }

    /**
     * @see AbstractProcessingHandler::write()
     */
    protected function write(array $record)
    {
        $sql = sprintf("INSERT INTO %s.%s (id, logtime, ident, priority, message) VALUES(:id, NOW(), :ident, :priority, :message)", $this->schema, $this->table);

        $this->db->execute($sql, array(
            ':id' => $this->getNextId(),
            ':ident' => $record['channel'],
            ':priority' => Log::convertToCCRLevel($record['level']),
            ':message' => $record['message']
        ));
    }

    /**
     * Attempts to retrieve the next sequence value from the log_id_seq. The algorithm for accomplishing this is based
     * on MDB2's mysql driver ( https://github.com/pear/MDB2/blob/cb9d1d295e94fd1363adeedf9fabefb6a2cd23b2/MDB2/Driver/mysql.php#L1280 )
     *
     * @return int the next valid id to be used when writing to the log table.
     *
     * @throws Exception if any of the sql statements fail to execute.
     */
    protected function getNextId()
    {
        $query = sprintf('UPDATE %s.log_id_seq SET sequence = LAST_INSERT_ID(sequence+1);', $this->schema);

        try {
            // Attempt to update the log_id_seq.sequence value
            $this->db->execute($query);
        } catch (\PDOException $e) {
            if ($e->errorInfo[0] === 'HY000' && $e->errorInfo[1] === 2006) {
                // This is the MySQL server gone away error, which is seen when
                // there is a long delay between log messages and the
                // connection times out. It occurs here since this is the first DB
                // call for a log message.
                $this->db->disconnect();
                $this->db->execute($query);
            } else {
                throw $e;
            }
        }

        $stmt = $this->db->query('SELECT LAST_INSERT_ID() as id', array(), true);
        $stmt->execute();
        $id = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0)[0];

        return (int) $id;
    }
}
