<?php
namespace DataWarehouse\Query;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* Abstract class for defining classes pertaining to grouping data over time.
* 
*/
abstract class TimeAggregationUnit
{
    //The unit name is passed to this class via the constructor by extending subclasses
    private $_unit_name;

    //protected constructor can only be called from extending classes.
    protected function __construct($unit_name)
    {
        $this->_unit_name = $unit_name;
    } //__construct

    /* 
    * Get this name of this unit.
    * 
    * @returns the unit name of this aggregation unit
    * 
    */
    public function getUnitName()
    {
        return $this->_unit_name;
    } //getUnitName

    /* 
    * 
    * @returns minimum index of the time aggregation unit. example quarter min = 1 
    * 
    */		
    abstract public function getMinPeriodPerYear();

    /* 
    * 
    * @returns maximum index of the time aggregation unit. example quarter  max = 4
    * 
    */			
    abstract public function getMaxPeriodPerYear();


	abstract public function getTimeLabel($timestamp);
    /* 
    * getMinDateId
    *
    * @returns the minimum id from the corresponding time period table based on the 
    *          value of start_date. Returns 1, if start_date is less than all possible 
    *		   values in the table.
    * 
    */	
    public function getMinDateId($start_date)
    {
		$q = "select min(id) as id from ".$this->getUnitName()."s 
        where '$start_date' 
            between 
            ".$this->getUnitName()."_start 
            and ".$this->getUnitName()."_end";
		
        $startDateResult = \DataWarehouse::connect()->query($q);
			
        $start_date_id = 1;
        if(count($startDateResult) > 0)
        {
            $start_date_id = $startDateResult[0]['id'];
            if(!isset($start_date_id) || $start_date_id == '' )
            {
				$q = "select case when '$start_date' < min(".$this->getUnitName()."_start) then 1 else ".self::$_max_date_id." end as id  from ".$this->getUnitName()."s";
                $startDateResult = \DataWarehouse::connect()->query($q);
                if(count($startDateResult) > 0)
                {
                    $start_date_id = $startDateResult[0]['id'];
                }
            }
        }


        return $start_date_id;
    } //getMinDateId

    /* 
    * 
    * @returns the maximum id from the corresponding time period table based on the 
    *          value of end_date. Returns -1 if end_date is greater than all possible 
    *		   values in the table
    * 
    */	
    public function getMaxDateId($end_date)
    {
        $endDateResult = \DataWarehouse::connect()->query(
            "select max(id) as id from ".$this->getUnitName()."s 
        where '$end_date' 
            between 
            ".$this->getUnitName()."_start 
            and ".$this->getUnitName()."_end");

            $end_date_id = self::$_max_date_id;
        if(count($endDateResult) > 0)
        {
            $end_date_id = $endDateResult[0]['id'];
            if(!isset($end_date_id))
            {
                $endDateResult = \DataWarehouse::connect()->query(
                    "select case when '$end_date' < min(".$this->getUnitName()."_start) then 0 else ".self::$_max_date_id." end as id  from ".$this->getUnitName()."s");
                if(count($endDateResult) > 0)
                {
                    $end_date_id = $endDateResult[0]['id'];
                }
            }
        }
        return $end_date_id;
    } //getMaxDateId

    /* 
    * @returns this object as a string
    * 
    */
    public function __toString()
    {
        return $this->getUnitName();
    } //__toString

    //////////////Static Members////////////////////////
    /*
    * This variable keeps track of all the time units being registered or not. See @registerUnit
    */
    private static $_initialized = false;

    /*
    * This is the max date id that will be used if end_date is out of range and greater than the last
    * date id avaiable in the data table.
    */
    public static $_max_date_id = 999999999999999;
    /*
    *  This array keeps track of the TimeAggregationUnit subclasses that have registed
    *   using RegisterUnit
    */
    public static $_unit_name_to_class_name = array();

    /**
     * This provides the approximate length of each aggregation unit in days
     * to allow for comparision of units by length.
     * 
     * @var array
     */
    private static $unit_sizes_in_days = array(
        'day' => 1,
        'month' => 30,
        'quarter' => 90,
        'year' => 365,
    );

    /* 
    * Registers an TimeAggregationUnit subclass
    *
    * @param $unit_name for example 'week', 'day', 'month', 'quarter'
    * @param $unit_class_name for example 'DayAggregationUnit'
    * 
    */
    public static function registerUnit($unit_name, $unit_class_name)
    {
        self::$_unit_name_to_class_name[$unit_name] = $unit_class_name; 
    } //registerUnit

    /* 
    * Registers all TimeAggregationUnit subclasses.
    * 
    */
    public static function registerAggregationUnits()
    {
        if(!self::$_initialized)
        {
            //TODO: automate this by search directory
            self::registerUnit('day', '\\DataWarehouse\\Query\\TimeAggregationUnits\\DayAggregationUnit');
            // self::registerUnit('week', '\\DataWarehouse\\Query\\TimeAggregationUnits\\WeekAggregationUnit');
            self::registerUnit('month', '\\DataWarehouse\\Query\\TimeAggregationUnits\\MonthAggregationUnit');
            self::registerUnit('quarter', '\\DataWarehouse\\Query\\TimeAggregationUnits\\QuarterAggregationUnit');
			self::registerUnit('year', '\\DataWarehouse\\Query\\TimeAggregationUnits\\YearAggregationUnit');
			
            self::$_initialized = true;
        }
    } //registerAggregationUnits

    /* 
    * @param $time_period: the name of the time aggregation unit. ie: day, week, month, quarter.
    * @param $start_date: if time_period is auto this is used to figure out aggregation unit
    * @param $end_date: if time_period is auto this is used to figure out aggregation unit
    * @returns a subclass of TimeAggregationUnit based on $time_period requested.
    * @throws Exception if $time_period is not registered
    *
    * TimeAggregationUnit subclasses must be registed using TimeAggregationUnit::registerUnit first
    * 
    */		
    public static function factory($time_period, $start_date, $end_date)
    {
        self::registerAggregationUnits();

        $time_period = self::deriveAggregationUnitName($time_period,$start_date,$end_date);

        if(isset(self::$_unit_name_to_class_name[$time_period]))
        {
            $class_name = self::$_unit_name_to_class_name[$time_period];

            return new $class_name;	
        }
        else
        {
            throw new Exception("TimeAggregationUnit: Time period {$time_period} is invalid.");
        }
    } //factory	

	/*
	* This function returns a copy of the array that maps the aggregation unit  names to class names
	*/
	public static function getRegsiteredAggregationUnits()
	{
		self::registerAggregationUnits();
		return self::$_unit_name_to_class_name;
	}//getRegsiteredAggregationUnits

    /**
     * Generate the concrete, proper aggregation unit name for the input unit.
     * If the unit given is 'auto', this function will use the given start and
     * end dates to figure out the concrete aggregation unit to use. 
     * 
     * @param  string $time_period The aggregation unit to get the proper name
     *                             of or 'auto' to automatically determine the
     *                             unit from the given dates.
     * @param  string $start_date A string representing the start date
     *                            in 'Y-m-d' format.
     * @param  string $end_date A string representing the end date
     *                          in 'Y-m-d' format.
     * @param  mixed $min_aggregation_unit The smallest aggregation unit
     *                                     allowed when automatically
     *                                     determining the unit or null if
     *                                     no limit. (Default: null)
     * @return string A properly-formatted name for a concrete aggregation unit.
     */
    public static function deriveAggregationUnitName($time_period, $start_date, $end_date, $min_aggregation_unit = null)
    {
        $time_period = strtolower($time_period);

        if ($time_period === 'auto')
        {
            $dt_format = '!Y-m-d';
            $utc_tz = new \DateTimeZone('UTC');
            $start_date_dt = \DateTime::createFromFormat($dt_format, $start_date, $utc_tz);
            $end_date_dt = \DateTime::createFromFormat($dt_format, $end_date, $utc_tz);

            $date_difference = date_diff($start_date_dt, $end_date_dt);

            if ($date_difference->y >= 10) // >= 10 years
            {
                $time_period = 'quarter';
            }
            else if ((($date_difference->y * 12) + $date_difference->m) >= 6) // >= 6 months
            {
                $time_period = 'month';
            }
            else
            {
                $time_period = 'day';
            }

            if ($min_aggregation_unit !== null)
            {
                $min_aggregation_unit = self::deriveAggregationUnitName($min_aggregation_unit, $start_date, $end_date);

                $time_period_length = self::$unit_sizes_in_days[$time_period];
                $min_aggregation_unit_length = self::$unit_sizes_in_days[$min_aggregation_unit];

                if ($time_period_length < $min_aggregation_unit_length) {
                    $time_period = $min_aggregation_unit;
                }
            }
        }

        return $time_period;
    }

    /**
     * Determine which of two aggregation units is larger.
     *
     * @param  string $unit_1 The first unit to compare.
     * @param  string $unit_2 The second unit to compare.
     * @return string         The name of the larger unit. This may differ from
     *                        the input unit in capitalization. If one input
     *                        is not a valid unit, the other is returned
     *                        unchanged.
     */
    public static function getMaxUnit($unit_1, $unit_2)
    {
        // Convert input units to the expected unit name format.
        $unit_1_name = strtolower($unit_1);
        $unit_2_name = strtolower($unit_2);

        // If one unit is unknown, return the other unit.
        if (!array_key_exists($unit_1_name, self::$unit_sizes_in_days))
        {
            return $unit_2;
        }
        if (!array_key_exists($unit_2_name, self::$unit_sizes_in_days))
        {
            return $unit_1;
        }

        // Return the name of the unit that is larger.
        $unit_1_length = self::$unit_sizes_in_days[$unit_1_name];
        $unit_2_length = self::$unit_sizes_in_days[$unit_2_name];

        return $unit_1_length > $unit_2_length ? $unit_1_name : $unit_2_name;
    }

    /**
     * Find the smallest aggregation unit available for a given realm.
     * 
     * @param  string $realm The realm to find the smallest aggregation unit for.
     * @return mixed         The name of the smallest aggregation unit, or null
     *                       if it couldn't be found.
     */
    public static function getMinUnitForRealm($realm)
    {
        // Open the datawarehouse config.
        $config = \Xdmod\Config::factory();
        $dw_config = $config['datawarehouse']['realms'];

        // Find the config for the given realm.
        $this_realm_config = null;
        foreach ($dw_config as $key => $realm_config)
        {
            if ($key === $realm)
            {
                $this_realm_config = $realm_config;
                break;
            }
        }

        // If the given realm could not be found, return null.
        if ($this_realm_config === null)
        {
            return null;
        }

        // Search through the realm's group bys for the smallest aggregation unit.
        $realm_group_bys = $this_realm_config['group_bys'];
        $min_unit = null;
        $min_unit_length = PHP_INT_MAX;
        foreach ($realm_group_bys as $realm_group_by) {
            $realm_group_by_name = $realm_group_by['name'];
            if (!array_key_exists($realm_group_by_name, self::$unit_sizes_in_days))
            {
                continue;
            }

            $realm_group_by_length = self::$unit_sizes_in_days[$realm_group_by_name];
            if ($realm_group_by_length < $min_unit_length)
            {
                $min_unit = $realm_group_by_name;
                $min_unit_length = $realm_group_by_length;
            }
        }

        // Return the name of the smallest aggregation unit found (or null).
        return $min_unit;
    }

    /**
     * Check if the given string corresponds to the name of a time
     * aggregation unit.
     * 
     * @param  string  $name The string to check.
     * @return boolean       True if the string is the name of a time
     *                       aggregation unit, otherwise false.
     */
    public static function isTimeAggregationUnitName($name) {
        return array_key_exists(strtolower($name), self::$unit_sizes_in_days);
    }

    /**
     * Compute the time period covered by a given datapoint.
     *
     * @param unixtimestamp of start of period (seconds since the epoch).
     * @param period to cover (day, month, quarter, year).
     * @return the start and end dates coverted by the time point
     * @throws DomainException if specified time is invalid or period is invalid
     */
    public static function getRawTimePeriod($time_point, $period)
    {
        $start_dt = \DateTime::createFromFormat('U', "$time_point");
        $end_dt = \DateTime::createFromFormat('U', "$time_point");

        if($start_dt === False) {
            throw new \DomainException("Invalid value for time point");
        }

        if(!static::isTimeAggregationUnitName($period)) {
            throw new \DomainException("Invalid time period");
        }

        switch($period) {
            case 'day':
                // do nothing
                break;
            case 'month':
                $end_dt->add(new \DateInterval('P1M'));
                $end_dt->sub(new \DateInterval('P1D'));
                break;
            case 'quarter':
                $end_dt->add(new \DateInterval('P3M'));
                $end_dt->sub(new \DateInterval('P1D'));
                break;
            case 'year':
                $end_dt->add(new \DateInterval('P1Y'));
                $end_dt->sub(new \DateInterval('P1D'));
            break;
        }

        return array($start_dt->format('Y-m-d'), $end_dt->format('Y-m-d'));
    }
}

?>
