<?php
/**
 * Generates and stores data about years.
 */
class YearGenerator extends TimePeriodGenerator
{
    /**
     * @see TimePeriodGenerator::getDatabaseMainTableName
     */
    protected function getDatabaseMainTableName()
    {
        return 'years';
    }

    /**
     * @see TimePeriodGenerator::getDatabaseUnitName
     */
    protected function getDatabaseUnitName()
    {
        return 'year';
    }

    /**
     * @see TimePeriodGenerator::getNextTimePeriodStart
     */
    protected function getNextTimePeriodStart(DateTime $dt)
    {
        return new DateTime($dt->format('Y') . '-01-01 +1 year');
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodEnd
     */
    protected function getTimePeriodEnd(DateTime $dt)
    {
        return new DateTime($dt->format('Y') . '-12-31T23:59:59');
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodInYear
     */
    protected function getTimePeriodInYear(DateTime $dt)
    {
        return 0;
    }

    /**
     * @see TimePeriodGenerator::getTimePeriodStart
     */
    protected function getTimePeriodStart(DateTime $dt)
    {
        return new DateTime($dt->format('Y') . '-01-01');
    }
}
