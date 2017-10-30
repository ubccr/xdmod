<?php

use CCR\DB\iDatabase;

/**
 * Base class for generating and storing data about time periods.
 */
abstract class TimePeriodGenerator
{
    /**
     * The DateTime format used by the database.
     */
    const DATABASE_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Gets an instance of a subclass matching the given unit of time.
     *
     * Supported units of time include:
     *     day
     *     month
     *     quarter
     *     year
     *
     * @param  string              $unit The unit of time to get a generator for.
     * @return TimePeriodGenerator       An instance of a subclass selected
     *                                   based on the given unit.
     * @throws Exception                 The given unit doesn't match a subclass.
     */
    public static function getGeneratorForUnit($unit)
    {
        switch ($unit) {
            case 'day':
                return new DayGenerator();
                break;
            case 'month':
                return new MonthGenerator();
                break;
            case 'quarter':
                return new QuarterGenerator();
                break;
            case 'year':
                return new YearGenerator();
                break;
            default:
                throw new Exception("No time period generator was found for unit of time \"$unit\".");
                break;
        }
    }

    /**
     * Gets the name of the main database table associated with this generator's unit.
     *
     * @return string The time unit's main table name.
     */
    abstract protected function getDatabaseMainTableName();

    /**
     * Gets the name of this generator's unit for use in determining database
     * column names.
     *
     * @return string The time unit's name as used in database table columns.
     */
    abstract protected function getDatabaseUnitName();

    /**
     * Calculates the start of the time period following the time period
     * the given datetime is in.
     *
     * @param  DateTime $dt A datetime used to find the start of the time
     *                      period following the time period the datetime is in.
     * @return DateTime     The start of the time period following the
     *                      datetime's time period.
     */
    abstract protected function getNextTimePeriodStart(DateTime $dt);

    /**
     * Calculates the end of the time period the given datetime is in.
     *
     * The end is inclusive, meaning that it represents the final second of the
     * time period.
     *
     * @param  DateTime $dt A datetime used to find the end of a time period.
     * @return DateTime     The inclusive end of the time period the datetime is in.
     */
    abstract protected function getTimePeriodEnd(DateTime $dt);

    /**
     * Calculates which numerical time period of the year a datetime is in.
     *
     * @param  DateTime $dt A datetime to find the time period of the year of.
     * @return int          A positive index if the unit of time can divide a
     *                      year into multiple time periods. Otherwise, 0.
     */
    abstract protected function getTimePeriodInYear(DateTime $dt);

    /**
     * Calculates the start of the time period the given datetime is in.
     *
     * @param  DateTime $dt A datetime used to find the start of a time period.
     * @return DateTime     The start of the time period the datetime is in.
     */
    abstract protected function getTimePeriodStart(DateTime $dt);

    /**
     * Converts a datetime string from the database to a datetime.
     *
     * @param  string $dt_str The string to convert.
     * @return DateTime       A datetime.
     */
    private function getDatabaseDateTime($dt_str)
    {
        return DateTime::createFromFormat(self::DATABASE_DATETIME_FORMAT, $dt_str);
    }

    /**
     * Converts a datetime to a string readable by the database.
     *
     * @param  DateTime $dt The datetime to convert.
     * @return string       A date string parsable by the database.
     */
    private function getDatabaseDateTimeString(DateTime $dt)
    {
        return $dt->format(self::DATABASE_DATETIME_FORMAT);
    }

    /**
     * Retrieves the year from a DateTime and returns it as an integer.
     *
     * @param  DateTime $dt The datetime to get the year from.
     * @return int          The year of the datetime.
     */
    protected function getYearFromDateTime(DateTime $dt)
    {
        return intval($dt->format('Y'));
    }

    /**
     * Get the ID for a time period from its year and time period in year.
     *
     * @param  int $year                The year the time period is in.
     * @param  int $time_period_in_year The index of the time period in the year.
     * @return int                      The ID for the time period.
     */
    private function getTimePeriodId($year, $time_period_in_year)
    {
        return ($year * 100000) + $time_period_in_year;
    }

    /**
     * Calculate timestamp and total time information for a range of time.
     *
     * This will calculate the Unix timestamps for the start and end times
     * given, as well as the midpoint between the two times. The total hours
     * and total seconds between the two times will also be calculated.
     *
     * Note that for the total calculations, the end time is treated
     * inclusively for the second it represents, whereas the other calulations
     * treat the given times as exact moments. This is based around how the
     * values calculated are stored in the database.
     *
     * @param  DateTime $start_dt The start of a time range.
     * @param  DateTime $end_dt   The end of a time range.
     * @return array              A set of information about the range, including:
     *                              start_ts:      The range start as a timestamp.
     *                              middle_ts:     The range midpoint as a timestamp.
     *                              end_ts:        The range end as a timestamp.
     *                              total_hours:   The timespan of the range in hours.
     *                              total_seconds: The timespan of the range in seconds.
     */
    private function getTimestampsAndTotals(DateTime $start_dt, DateTime $end_dt) {
        // Convert the datetimes to Unix timestamps.
        $start_ts = $start_dt->getTimestamp();
        $end_ts = $end_dt->getTimestamp();

        // Calculate the midpoint of the start and end as a Unix timestamp.
        $start_end_ts_diff = $end_ts - $start_ts;
        $middle_ts = $start_ts + ($start_end_ts_diff / 2);

        // Calculate the total hours and total seconds from the start
        // through the end.
        $seconds = $start_end_ts_diff + 1;
        $hours = $seconds / 3600;

        // Return the calculated data.
        return array(
            'start_ts' => $start_ts,
            'middle_ts' => $middle_ts,
            'end_ts' => $end_ts,
            'total_hours' => $hours,
            'total_seconds' => $seconds,
        );
    }

    /**
     * Get the minimum (inclusive) and maximum (exclusive) DateTimes based on
     * the min and max job times
     * @param  iDatabase $db The database the tables are being generated for.
     *                       A schema should be in use by this connection.
     *
     * @return array min and mad date time.
     */
    private function getMinMaxFromDatabase(iDatabase $db){
        // Get the minimum (inclusive) and maximum (exclusive) DateTimes for
        // the range of time to generate time periods for.
        $datetime_query_results = $db->query("
            SELECT
                DATE_SUB(min_job_date, INTERVAL 1 DAY) AS min_datetime,
                max_job_date AS max_datetime
            FROM
                modw.minmaxdate
        ");
        $min_datetime = $this->getDatabaseDateTime($datetime_query_results[0]['min_datetime']);
        $max_datetime = $this->getDatabaseDateTime($datetime_query_results[0]['max_datetime']);

        return array($min_datetime, $man_datetime);
    }

    /**
     * Generate the main database table for this unit.
     *
     * @param  iDatabase $db The database the tables are being generated for.
     *                       A schema should be in use by this connection.
     */
    public function generateMainTable(iDatabase $db, $min_datetime = null, $max_datetime = null)
    {
        if(null === $min_datetime || null === $max_datetime){
            list($min_datetime, $max_datetime) = $this->getMinMaxFromDatabase($db);
        }
        // Get the target database table and parameter names.
        // (Parameter names are the table column names prefixed with a colon.)
        $db_table = $this->getDatabaseMainTableName();
        $db_unit = $this->getDatabaseUnitName();
        $db_time_period_of_year_param = ":{$db_unit}";
        $db_start_param = ":{$db_unit}_start";
        $db_end_param = ":{$db_unit}_end";
        $db_start_ts_param = ":{$db_unit}_start_ts";
        $db_end_ts_param = ":{$db_unit}_end_ts";
        $db_middle_ts_param = ":{$db_unit}_middle_ts";

        // Delete any table entries before the start of the time range.
        $current_start_datetime = $this->getTimePeriodStart($min_datetime);
        $db_start_column = substr($db_start_param, 1);
        $db->execute("
            TRUNCATE
                $db_table
        ", array(
            $db_start_param => $this->getDatabaseDateTimeString($current_start_datetime),
        ));

        // Create a database entry for each time period in the range.
        $insert_statement = null;
        while ($current_start_datetime < $max_datetime) {
            $current_end_datetime = $this->getTimePeriodEnd($current_start_datetime);
            if ($current_end_datetime > $max_datetime) {
                $current_end_datetime = $max_datetime;
            }

            // Calculate the current time period's year, index in year, and ID.
            $current_year = $this->getYearFromDateTime($current_start_datetime);
            $current_time_period_in_year = $this->getTimePeriodInYear($current_start_datetime);
            $current_id = $this->getTimePeriodId($current_year, $current_time_period_in_year);

            // Generate the datetime strings readable by the database.
            $current_start_datetime_str = $this->getDatabaseDateTimeString($current_start_datetime);
            $current_end_datetime_str = $this->getDatabaseDateTimeString($current_end_datetime);

            // Calculate timestamp and total time information for the time period.
            $current_info = $this->getTimestampsAndTotals($current_start_datetime, $current_end_datetime);

            // Insert the calculated values into the database.
            // If the statement string has not been created yet, create it.
            $insert_param_values = array(
                ':id' => $current_id,
                ':year' => $current_year,
                $db_start_param => $current_start_datetime_str,
                $db_end_param => $current_end_datetime_str,
                ':hours' => $current_info['total_hours'],
                ':seconds' => $current_info['total_seconds'],
                $db_start_ts_param => $current_info['start_ts'],
                $db_end_ts_param => $current_info['end_ts'],
                $db_middle_ts_param => $current_info['middle_ts'],
            );
            if ($current_time_period_in_year > 0) {
                $insert_param_values[$db_time_period_of_year_param] = $current_time_period_in_year;
            }

            if ($insert_statement === null) {
                $insert_params = array_keys($insert_param_values);
                $insert_columns = array_map(function ($insert_param) {
                    return substr($insert_param, 1);
                }, $insert_params);

                $insert_columns_str = implode(', ', $insert_columns);
                $insert_params_str = implode(', ', $insert_params);

                $insert_statement = "
                    INSERT INTO $db_table ($insert_columns_str)
                    VALUES ($insert_params_str)
                ";
            }

            $db->execute($insert_statement, $insert_param_values);

            // Get the start of the next time period for the next entry.
            $current_start_datetime = $this->getNextTimePeriodStart($current_start_datetime);
        } // while

    }
}
