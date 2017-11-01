<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

/**
 * Perform a timeseries query on aggregate storage data.
 */
class Timeseries extends \DataWarehouse\Query\Timeseries
{
    public function __construct(
        $aggregationUnitName,
        $startDate,
        $endDate,
        $groupBy,
        $stat = '',
        array $parameters = array(),
        $queryGroupname = 'query_groupname',
        array $parameterDescription = array(),
        $singleStat = false
    ) {
        parent::__construct(
            'Storage',
            'modw_aggregates',
            'storage_user_usage',
            array(),
            $aggregationUnitName,
            $startDate,
            $endDate,
            $groupBy,
            $stat,
            $parameters,
            $queryGroupname,
            $parameterDescription,
            $singleStat
        );
    }
}
