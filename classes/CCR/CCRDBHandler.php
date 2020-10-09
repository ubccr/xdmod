<?php

namespace CCR;

use CCR\DB\iDatabase;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class CCRDBHandler extends AbstractProcessingHandler
{
    const sql = "INSERT INTO %s.%s (id, logtime, ident, priority, message) VALUES(:id, NOW(), :ident, :priority, :message)";

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

    public function __construct(iDatabase $db = null, $schema = null, $table = null, $level = Logger::DEBUG, $bubble = true)
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


    protected function write(array $record)
    {
        $sql = sprintf(self::sql, $this->schema, $this->table, $this->schema);

        $this->db->execute($sql, array(
            ':id' => $this->getNextId(),
            ':ident' => $record['channel'],
            ':priority' =>$record['level'],
            ':message' => $record['formatted']
        ));
    }

    /**
     * Attempts to retrieve the next sequence value from the log_id_seq. The algorithm for accomplishing this is based
     * on MDB2's mysql driver ( https://github.com/pear/MDB2/blob/cb9d1d295e94fd1363adeedf9fabefb6a2cd23b2/MDB2/Driver/mysql.php#L1280 )
     *
     * @return int
     *
     * @throws \Exception if any of the sql statements fail to execute.
     */
    protected function getNextId()
    {
        $this->db->beginTransaction();

        $insertSQL = sprintf('INSERT INTO %s.log_id_seq (sequence) VALUES(NULL);', $this->schema);

        $results = $this->db->execute($insertSQL);
        if (count($results) <= 0) {
            $this->db->rollBack();
            throw new \Exception('Incrementing log id seq failed');
        }

        $results  = $this->db->query('SELECT LAST_INSERT_ID() as id');
        $id = $results[0]['id'];

        if (!is_numeric($id)) {
            $this->db->rollBack();
            throw new \Exception('Retrieving latest log id value failed.');
        }

        $results = $this->db->execute(sprintf("DELETE FROM %s.log_id_seq WHERE sequence < :id", $this->schema), array(':id' => $id));
        if (count($results) <= 0) {
            $this->db->rollBack();
            throw new \Exception('');
        }
        $this->db->commit();

        return (int)$id;
    }
}
