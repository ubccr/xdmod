<?php
/**
 * Null database implementation.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 *
 * Changelog
 *
 * 2015-12-15 Steve Gallo <smgallo@buffalo.edu>
 * - Added prepare()
 */

namespace CCR\DB;

class NullDB implements iDatabase
{
    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function connect(): void
    {
    }

    public function disconnect(): void
    {
    }

    public function insert($statement, $params = [])
    {
        return 0;
    }

    public function handle(): void
    {
    }

    public function query(
        $query,
        array $params = [],
        $returnStatement = false
    ) {
        return [];
    }

    public function execute($query, array $params = [])
    {
        return 0;
    }

    public function getRowCount($schema, $table): void
    {
    }

    public function prepare($query)
    {
        return false;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollBack()
    {
        return true;
    }

    public function quote($string)
    {
        return "'" . str_replace("'", "''", $string) . "'";
    }
}
