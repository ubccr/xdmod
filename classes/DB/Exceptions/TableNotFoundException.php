<?php

namespace DB\Exceptions;

use Exception;

/**
 * Exception thrown when a database table is not found.
 */
class TableNotFoundException extends Exception
{
    /**
     * Name of the table that wasn't found.
     *
     * @var string
     */
    private $table;

    /**
     * Create exception.
     *
     * @param string $messge Error message.
     * @param int $code Error code.
     * @param Exception|null $previous Previous exception.
     * @param string $table Name of table that wasn't found.
     */
    public function __construct(
        $message,
        $code,
        $previous,
        $table
    ) {
        $this->table = $table;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the name of the table that wasn't found.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
