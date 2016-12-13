<?php
/**
 * Generates and stores data about months.
 */
class MonthGenerator extends TimePeriodGenerator
{
    /**
     * @see TimePeriodGenerator::getDatabaseMainTableName
     */
    protected function getDatabaseMainTableName()
    {
        return 'months';
    }

    /**
     * @see TimePeriodGenerator::getDatabaseUnitName
     */
    protected function getDatabaseUnitName()
    {
        return 'month';
    }

    /**
     * @see TimePeriodGenerator::getNextTimePeriodStart
     */
    protected function getNextTimePeriodStart(DateTime $dt)
    {
        $next_month = intval($dt->format('m')) + 1;
        $next_year = $this->getYearFromDateTime($dt);

        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }

        return new DateTime("$next_year-$next_month-01");
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodEnd
     */
    protected function getTimePeriodEnd(DateTime $dt)
    {
        return new DateTime('last day of ' . $dt->format('Y-m') . ' 23:59:59');
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodInYear
     */
    protected function getTimePeriodInYear(DateTime $dt)
    {
        return intval($dt->format('m'));
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodStart
     */
    protected function getTimePeriodStart(DateTime $dt)
    {
        return new DateTime($dt->format('Y-m') . '-01');
    }
}
