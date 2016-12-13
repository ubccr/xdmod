<?php
/**
 * Generates and stores data about quarters.
 */
class QuarterGenerator extends TimePeriodGenerator
{
    /**
     * @see TimePeriodGenerator::getDatabaseMainTableName
     */
    protected function getDatabaseMainTableName()
    {
        return 'quarters';
    }

    /**
     * @see TimePeriodGenerator::getDatabaseUnitName
     */
    protected function getDatabaseUnitName()
    {
        return 'quarter';
    }

    /**
     * @see TimePeriodGenerator::getNextTimePeriodStart
     */
    protected function getNextTimePeriodStart(DateTime $dt)
    {
        $current_month = intval($dt->format('m'));
        $current_year = $this->getYearFromDateTime($dt);

        if ($current_month < 4) {
            $next_month = 4;
            $next_year = $current_year;
        } else if ($current_month < 7) {
            $next_month = 7;
            $next_year = $current_year;
        } else if ($current_month < 10) {
            $next_month = 10;
            $next_year = $current_year;
        } else {
            $next_month = 1;
            $next_year = $current_year + 1;
        }

        return new DateTime("$next_year-$next_month-01");
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodEnd
     */
    protected function getTimePeriodEnd(DateTime $dt)
    {
        $current_month = intval($dt->format('m'));
        $current_year = $this->getYearFromDateTime($dt);

        if ($current_month < 4) {
            $quarter_end_month = 3;
        } else if ($current_month < 7) {
            $quarter_end_month = 6;
        } else if ($current_month < 10) {
            $quarter_end_month = 9;
        } else {
            $quarter_end_month = 12;
        }

        return new DateTime("last day of $current_year-$quarter_end_month 23:59:59");
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodInYear
     */
    protected function getTimePeriodInYear(DateTime $dt)
    {
        $current_month = intval($dt->format('m'));

        if ($current_month < 4) {
            $quarter_index = 1;
        } else if ($current_month < 7) {
            $quarter_index = 2;
        } else if ($current_month < 10) {
            $quarter_index = 3;
        } else {
            $quarter_index = 4;
        }

        return $quarter_index;
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodStart
     */
    protected function getTimePeriodStart(DateTime $dt)
    {
        $current_month = intval($dt->format('m'));
        $current_year = $this->getYearFromDateTime($dt);

        if ($current_month < 4) {
            $quarter_start_month = 1;
        } else if ($current_month < 7) {
            $quarter_start_month = 4;
        } else if ($current_month < 10) {
            $quarter_start_month = 7;
        } else {
            $quarter_start_month = 10;
        }

        return new DateTime("$current_year-$quarter_start_month-01");
    }
}
