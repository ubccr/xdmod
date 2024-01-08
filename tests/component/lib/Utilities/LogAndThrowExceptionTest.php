<?php

namespace ComponentTests\Utilities;

use CCR\DB;
use CCR\Loggable;
use Exception;
use PDOException;
use \PHPUnit\Framework\TestCase;

/**
 * Test various cases for exceptions thrown by Loggable::logAndThrowException()
 */

class LogAndThrowExceptionTest extends \PHPUnit\Framework\TestCase
{
    private $db;
    private $loggable;

    public function setup(): void
    {
        $this->db = DB::factory('datawarehouse');
        $this->loggable = new Loggable();
    }

    /**
     * Test #1: Provide only a log/exception message.
     */

    public function testNoExceptionCode()
    {
        $msg = "No Code";
        $expectedMsg = (string) $this->loggable . ": $msg";
        try {
            $this->loggable->logAndThrowException($msg);
        } catch ( Exception $e ) {
            $this->assertEquals($expectedMsg, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }
    }

    /**
     * Test #2: Provide a log/exception message and an exception with the same message and a code.
     */

    public function testExceptionCode()
    {
        $msg = "Code = 10";
        $expectedMsg = (string) $this->loggable . ": $msg Exception: '$msg'";
        try {
            $this->loggable->logAndThrowException(
                $msg,
                array('exception' => new Exception($msg, 10))
            );
        } catch ( Exception $e ) {
            $this->assertEquals($expectedMsg, $e->getMessage());
            $this->assertEquals(10, $e->getCode());
        }
    }

    /**
     * Test #3: Query the database for a table that doesn't exist. Ensure the SQLSTATE 42S02
     *   is returned as the PDOException error code and the MySQL error number is returned by
     *   the exception thrown via Loggable::logAndThrowException().
     *   See https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
     */

    public function testPdoException()
    {
        $sql = sprintf("SELECT count(*) FROM %s;", uniqid('modw.table_does_not_exist_'));
        try {
            try {
                $this->db->query($sql);
            } catch ( PDOException $p ) {
                $this->assertEquals('42S02', $p->getCode(), 'Inner testPdoException');
                $this->loggable->logAndThrowException(
                    $p->getMessage(),
                    array(
                        'exception' => $p,
                        'sql' => $sql
                    )
                );
            }
        } catch ( Exception $e ) {
            $this->assertEquals(1146, $e->getCode(), 'Outer testPdoException');
        }
    }
}
