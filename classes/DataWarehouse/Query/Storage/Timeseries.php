<?php
/**
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
        array $parameters = array()
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
            $parameters
        );
    }
}
