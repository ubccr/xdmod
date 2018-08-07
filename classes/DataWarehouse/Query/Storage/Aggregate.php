<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

use DataWarehouse\Query\Query;

/**
 * Perform a query on aggregate storage data.
 */
class Aggregate extends Query
{
    public function __construct(
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupBy,
        $stat = '',
        array $parameters = array(),
        $queryGroupname = 'query_groupname',
        array $parameterDescriptions = array(),
        $singleStat = false
    ) {
        parent::__construct(
            'Storage',
            'modw_aggregates',
            'storagefact',
            array(),
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupBy,
            $stat,
            $parameters,
            $queryGroupname,
            $parameterDescriptions,
            $singleStat
        );
    }
}
