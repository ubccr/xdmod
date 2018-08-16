<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

/**
 * Standard error of the mean statistic.
 */
class SemStatistic extends Statistic
{
    public function __construct(
        $baseStat,
        $label,
        $unit,
        $decimals = 1,
        $info = null
    ) {
        $weightStat = $this->getWeightStatName();

        $formula = <<<"EOF"
SQRT(
    SUM(jf.sum_squared_$baseStat) / SUM(jf.$weightStat)
    - POW(SUM(jf.sum_$baseStat) / SUM(jf.$weightStat), 2)
) / SQRT(SUM(jf.$weightStat))
EOF;

        parent::__construct(
            $formula,
            'sem_' . $baseStat,
            $label,
            $unit,
            $decimals,
            $info
        );
    }

    public function isVisible()
    {
        return false;
    }
}
