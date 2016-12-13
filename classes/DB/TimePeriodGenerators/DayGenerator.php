<?php
/**
 * Generates and stores data about days.
 */
class DayGenerator extends TimePeriodGenerator
{
    /**
     * @see TimePeriodGenerator::getDatabaseMainTableName
     */
    protected function getDatabaseMainTableName()
    {
        return 'days';
    }

    /**
     * @see TimePeriodGenerator::getDatabaseUnitName
     */
    protected function getDatabaseUnitName()
    {
        return 'day';
    }

    /**
     * @see TimePeriodGenerator::getNextTimePeriodStart
     */
    protected function getNextTimePeriodStart(DateTime $dt)
    {
        return new DateTime($dt->format('Y-m-d') . ' +1 day');
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodEnd
     */
    protected function getTimePeriodEnd(DateTime $dt)
    {
        return new DateTime($dt->format('Y-m-d') . 'T23:59:59');
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodInYear
     */
    protected function getTimePeriodInYear(DateTime $dt)
    {
        return intval($dt->format('z')) + 1;
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodStart
     */
    protected function getTimePeriodStart(DateTime $dt)
    {
        return new DateTime($dt->format('Y-m-d'));
    }
}
