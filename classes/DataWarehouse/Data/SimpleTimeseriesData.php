<?php

namespace DataWarehouse\Data;

/**
 * This class represents one timeseries data column.
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 */
class SimpleTimeseriesData extends SimpleData
{
    /**
     * Only available for timeseries data.
     */
    protected $_start_ts = array();
    protected $_group_name;
    protected $_group_id;

    public function __construct($name)
    {
        parent::__construct($name);
    }

    /**
     *  JMS June 2015
     */
    public function getStartTs()
    {
        return $this->_start_ts;
    } 

    /**
     *  JMS June 2015
     */
    public function setStartTs( $ts )
    {
        $this->_start_ts = $ts;
    } 

    /**
     *  JMS June 2015
     */
    public function getGroupName()
    {
        return $this->_group_name;
    }

    /**
     *  JMS June 2015
     */
    public function setGroupName( $g )
    {
        $this->_group_name= $g;
    }

    /**
     *  JMS June 2015
     */
    public function getGroupId()
    {
        return $this->_group_id;
    }

    /**
     *  JMS June 2015
     */
    public function setGroupId( $g )
    {
        $this->_group_id= $g;
    }

    public function getChartTimes()
    {
        $chartTimes = array();

        foreach ($this->_start_ts as $timestamp) {
            $chartTimes[] = chartTime2($timestamp);
        }

        return $chartTimes;
    } // function getChartTimes()

    public function makeUnique()
    {
        $uniqueValues = array();

        foreach ($this->_values as $index => $value) {
            $id = $this->_ids[$index];

            if (isset($uniqueValues[$id])) {
                unset($this->_values[$index]);
                unset($this->_errors[$index]);
                unset($this->_start_ts[$index]);
                unset($this->_ids[$index]);
                unset($this->_order_ids[$index]);
            } else {
                $uniqueValues[$id] = $value;
            }
        }

        $this->_values    = array_values($this->_values);
        $this->_errors    = array_values($this->_errors);
        $this->_ids       = array_values($this->_ids);
        $this->_order_ids = array_values($this->_order_ids);
        $this->_start_ts  = array_values($this->_start_ts);

        return $uniqueValues;
    } // function makeUnique()

    public function joinTo(SimpleTimeseriesData $left, $no_value = null)
    {
        $t_values    = $this->_values;
        $t_errors    = $this->_errors;
        $t_order_ids = $this->_order_ids;
        $t_ids       = $this->_ids;
        $t_start_ts  = $this->_start_ts;

        $ts_to_index = array();
        foreach ($t_start_ts as $index => $ts) {
            $ts_to_index[$ts] = $index;
        }

        $this->_values    = array();
        $this->_errors    = array();
        $this->_ids       = array();
        $this->_order_ids = array();
        $this->_start_ts  = array();

        foreach ($left->_start_ts as $index => $start_ts) {
            $this->_start_ts[] = $start_ts;

            if (isset($ts_to_index[$start_ts])) {
                $i = $ts_to_index[$start_ts];

                $this->_values[] = $t_values[$i];

                $this->_errors[]
                    = array_key_exists($i, $t_errors)
                    ? $t_errors[$i]
                    : null;
            } else {
                $this->_values[] = $no_value;
                $this->_errors[] = $no_value;
            }
        } // foreach
    } // function joinTo()


    // -----------------------------
    // summarize()
    // 
    // was known as add() in TimeseriesData
    // enables summarization of datasets
    // so that a set of timeseries datasets 
    // can be reported as a single column
    // 
    // Depending on the stat alias, we take
    // min, max, or sum of ts datasets.
    // Averaging is done elsewhere (see 
    // class @HighChartTimeseries2)
    //
    // NOTE that all errors get set == 0.
    // This is consistent with previous implementation.
    // 
    // JMS 24 July 15
    // -----------------------------
    public function summarize(SimpleTimeseriesData $d)
    {
        $values = $this->getValues();
        $sems = $this->getErrors();
        $stat = $this->getStatistic()->getAlias();

        $isMin = strpos($stat, 'min_') !== false ;
        $isMax = strpos($stat, 'max_') !== false ;

        $in_values  = $d->getValues();
        $in_sems    = $d->getErrors();

        foreach ($in_values as $key => $value) {
            $oldValue = \xd_utilities\array_get($values, $key, 0);

            if ($isMin) {
                if ($oldValue == 0 && $value != 0) {
                    $values[$key] = $value;
                } else {
                    $values[$key] = min($oldValue, $value);
                }

            } elseif ($isMax) {
                $values[$key] = max($oldValue, $value);

            } else {
                $values[$key] = $oldValue + $value;
            }

            $sems[$key] = 0;
        }

        $this->setValues($values);
        $this->setErrors($sems);
    } // summarize

    // Helper function for debugging 
    // JMS April 2015
    public function __toString() {

        $retval = parent::__toString();
        $retval .= "Start Timestamp:".  implode(',',$this->getStartTs())."\n"
            . "groupByObject: " . $this->getGroupBy(). "\n"
            . "groupName: " . $this->getGroupName() . "\n"
            . "groupId: " . $this->getGroupId() . "\n";
        return $retval;

    } // __toString() 

} // class SimpleTimeseriesData
