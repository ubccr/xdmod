<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

/**
 * Average statistic.
 */
class AvgStatistic extends Statistic
{
    public function __construct(
        $query,
        $baseStat,
        $label,
        $unit,
        $decimals = 1,
        $info = null
    ) {
        $formula
            = $query->getQueryType() === 'aggregate'
            ? 'COALESCE(SUM(jf.avg_' . $baseStat . ') / COUNT(DISTINCT jf.' . $query->getAggregationUnit() . '_id), 0)'
            : 'COALESCE(SUM(jf.avg_' . $baseStat . '), 0)';

        parent::__construct(
            $formula,
            'avg_' . $baseStat,
            $label,
            $unit,
            $decimals,
            $info
        );
    }
}
