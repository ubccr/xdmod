<?php

namespace DataWarehouse\Query;

/*
 * The RawQuery class is the base class to be used for
 * queries that return rows from fact tables, rather than from aggregate tables.
 */
abstract class RawQuery extends \DataWarehouse\Query\Query
{
    public function __construct($realm, $schema, $facttable, $parameters)
    {
        /* We are being a bit cheeky here by inheriting from the Query class.
         * This provides the implementations for most of the stuff we want and
         * we disable the stuff we don't want by overloading the duration functions
         * and crafting the constructor arguments.
         */

        parent::__construct(
            $realm,
            $schema,
            $facttable,
            array(),
            "day",
            "2010-01-01",
            "2010-01-01",
            null,
            null,
            array()
        );

        $this->setDataTable($schema, $facttable);
        $this->setParameters($parameters);
    }

    protected function setDuration($ignore1, $ignore2)
    {
        // Overload the setDuration function to do nothing. This prevents the
        // time-aggregation-specific code from running
    }

    /* should return an array containing the documentation for the various
     * columns
     */
    abstract public function getColumnDocumentation();
}
