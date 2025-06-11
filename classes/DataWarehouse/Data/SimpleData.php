<?php

namespace DataWarehouse\Data;

/**
 * This class represents one data column as one returned
 * from a database query. This is an array of numbers or 
 * values, potentially with error bars and with labels.
 *
 * TODO: support statistic and group by? Perhaps just in timeseries?
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 */
class SimpleData extends \Common\Identity
{
    protected $values;
    // ----------- instance variables ------------- //

    // SimpleData type may be one of 'dim', 'met', or 'time'
    // JMS: not yet in use. TODO, maybe.
    protected $_type;

    protected $_values = array();
    protected $_errors = array();

    /**
     * order_ids and ids: 
     * only available in case the data is a dimension and 
     * not a stat (metric).
     */
    protected $_order_ids = array();
    protected $_ids = array();

    // JMS: knowledge of statistic and group by belongs with query
    // in the SimpleDataset class.
    //protected $statisticObject; 
    //protected $groupByObject;
    // TODO: consider that groupby should live in SimpleTimeseriesDataset
    // instead of SimpleTimeseriesData class?

    // Labels and label ids for (nontimeseries) y data series
    // JMS TODO: consider replacing this or renaming to 'label'
    // and why do we have an array of x_ids??
    //    x_ids: enable drilldown inside of pie charts. Please don't ask.
    protected $_x_ids = array();
    protected $_x_values = array();

    /**
     * _unit: A string indicating contents of the column
     * Q: Does it duplicate what we have already in _name??
     * A: Not entirely!
     * JMS April 2015
     */
    protected $_unit;
    protected $_errorsCount;
    protected $_valuesCount;

    // derived from Query class 
    protected $_statistic;
    protected $_group_by; 

    // ----------- public functions ------------- //

    public function __construct($name)
    {
        parent::__construct($name);
    }

    // Helper function for debugging 
    // JMS April 2015
    public function __toString() {
        $st = isset($this->_statistic) ? $this->getStatistic()->getId() : null;

        return "Data Name: {$this->getName()}\n"
            . "Statistic: {$st}\n"
            . "Group By: {$this->getGroupBy()}\n"
            . "Unit: " . $this->getUnit() . "\n"
            . "Ids: " . implode(',', $this->getIds()) . "\n"
            . "Order Ids: " . implode(',', $this->getOrderIds()) . "\n"
            . "Values: " . implode(',', $this->getValues()) . "\n"
            . "Values Count: " . $this->getCount() . "\n"
            . "Std Err: " . implode(',', $this->getErrors()) . "\n"
            . "Std Err Count: " . $this->getErrorCount() . "\n"
            . "X Values: " . implode(',', $this->getXValues()) . "\n"
            . "X Values Count: " . count( $this->getXValues() ). "\n"
            . "X Ids: " . implode(',', $this->getXIds()) . "\n"
            . "X Ids Count: " . count( $this->getXIds() ). "\n";
    } // __toString() 

    /**
     *  truncate() 
     *
     * Truncate the dataset in PHP. 
     * No computation is done with the errors beyond $limit (set to 0).
     * We have no short labels in SimpleData model as of now.
     * Weights are unused.
     *
     * @param int $limit
     *      index upper limit of data to display.
     * @param bool $showAverageOfOthers
     */
    public function truncate($limit, $showAverageOfOthers = false)
    {
        $stat = $this->getStatistic()->getId();

        $isMin = strpos($stat, 'min_') !== false;
        $isMax = strpos($stat, 'max_') !== false;

        // use the accessor to determine the count of values in the present
        // SimpleData object.
        $valuesCount = $this->getCount(false);

        if ($valuesCount > $limit) {
            $otherSum = 0;

            for ($i = $limit; $i < $valuesCount; $i++) {
                if ($isMin) {
                    if ($otherSum == 0) {
                        $otherSum = $this->_values[$i];
                    } else {
                        $otherSum = min($otherSum,  $this->_values[$i]);
                    }
                } elseif ($isMax) {
                    if ($otherSum == 0) {
                        $otherSum = $this->_values[$i];
                    } else {
                        $otherSum = max($otherSum,  $this->_values[$i]);
                    }
                } else {
                    $otherSum += $this->_values[$i];
                }
            } // for $i

            // slice arrays to discard all data values beyond stated limit index
            $sliceIdx = $limit + 1;
            $this->_values = array_slice( $this->_values, null, $sliceIdx);
            $this->_x_values = array_slice( $this->_x_values, null, $sliceIdx);
            $this->_x_ids = array_slice( $this->_x_ids, null, $sliceIdx);
            $this->_ids = array_slice( $this->_ids, null, $sliceIdx);
            $this->_errors = array_slice( $this->_errors, null, $sliceIdx);

            // Compute the limiting value, and the limiting value's label:
            if ($isMin) {
                $this->_values[$limit] = $otherSum ;
                $this->_x_values[$limit] = 'Min of ' . ($valuesCount - $limit) . ' others';
            } elseif ($isMax) {
                $this->_values[$limit] = $otherSum;
                $this->_x_values[$limit] = 'Max of ' . ($valuesCount - $limit) . ' others';
            } elseif ($showAverageOfOthers === true) {
                $this->_values[$limit] = ($otherSum) / ($valuesCount - $limit);
                $this->_x_values[$limit] = 'Avg of ' . ($valuesCount-$limit) . ' others';
            } else {
                $this->_values[$limit] = $otherSum ;
                $this->_x_values[$limit] = 'All ' . ($valuesCount - $limit) . ' others';
            }

            // 'Short labels' were never exactly very well implemented:
            //$this->_short_labels[$limit] = $this->_labels[$limit];

            // This prevents drilldown into summarized data series (see html/gui/js/DrillDownMenu.js
            // groupByIdParam check)
            $this->_ids[$limit]            = -9999 + (-1 * $limit);
            $this->_errors[$limit]         = 0;
            // Make label ids last array element same as ids
            $this->_x_ids[$limit]          = $this->_ids[$limit];
        }
        // update data counts for this object
        $this->getCount(true);
        $this->getErrorCount(true);
    } // truncate()


    /**
     * Not implemented, duh.
     * Appears to be implemented only in SimpleTimeseriesData class
     * JMS todo: So is there some reason we need it here??
     */
    public function makeUnique()
    {
    }

    // ----------- accessors ------------- //

    public function getCount($force_recount = false)
    {
        if (!isset($this->_valuesCount) || $force_recount === true) {
            $this->_valuesCount = count($this->_values);
        }

        return $this->_valuesCount;
    }

    public function getErrorCount($force_recount = false)
    {
        if (!isset($this->_errorsCount) || $force_recount === true) {
            $this->_errorsCount = count($this->_errors);
        }

        return $this->_errorsCount;
    }

    public function getMinMax()
    {
        if ($this->getCount() > 0) {
            $max = 0;
            $min = 100000;
        } else {
            $max = 100000;
            $min = 0;
        }

        foreach ($this->values as $value) {
            if ($value != NoValue) {
                $min = min($min,$value);
                $max = max($max,$value);
            }
        }

        return array($min, $max);
    }

    public function getValues()
    {
        return $this->_values;
    }

    /**
     *  JMS April 2015
     * Quickly test for existence of array element (null or otherwise):
     * http://www.zomeoff.com/php-fast-way-to-determine-a-key-elements-existance-in-an-array/
     * http://stackoverflow.com/questions/2473989/list-of-big-o-for-php-functions
     */
    public function getValue($idx)
    {
        if (isset( $this->_values[$idx] ) || array_key_exists($idx, $this->_values) ) {
            return $this->_values[$idx];
        }
        /*
        } else {
            return 0;
        }
        */

        throw new \Exception(
            get_class($this) . ":getValue( idx = $idx ) not found"
        );
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getError($idx)
    {
        if (isset( $this->_errors[$idx] ) || array_key_exists($idx, $this->_errors) ) {
            return $this->_errors[$idx];
        }

        throw new \Exception(
            get_class($this) . ":getError( idx = $idx ) not found"
        );
    }

    public function getXIds()
    {
        return $this->_x_ids;
    }


    public function getXId($idx)
    {
        if (isset( $this->_x_ids[$idx] ) || array_key_exists($idx, $this->_x_ids) ) {
            return $this->_x_ids[$idx];
        }

        throw new \Exception(
            get_class($this) . ":getXId( idx = $idx ) not found"
        );
    }

    public function getXValues()
    {
        return $this->_x_values;
    }

    public function getXValue($idx)
    {
        if (isset( $this->_x_values[$idx] ) || array_key_exists($idx, $this->_x_values) ) {
            return $this->_x_values[$idx];
        }

        throw new \Exception(
            get_class($this) . ":getXValue( idx = $idx ) not found"
        );
    }

    public function getUnit()
    {
        return $this->_unit;
    }

    public function getOrderIds()
    {
        return $this->_order_ids;
    }

    public function getOrderId($idx)
    {
        if (isset( $this->_order_ids[$idx] ) || array_key_exists($idx, $this->_order_ids) ) {
            return $this->_order_ids[$idx];
        }

        throw new \Exception(
            get_class($this) . ":getOrderId( idx = $idx ) not found"
        );
    }

    public function getIds()
    {
        return $this->_ids;
    }

    public function getId($idx)
    {
        if (isset( $this->_ids[$idx] ) || array_key_exists($idx, $this->_ids) ) {
            return $this->_ids[$idx];
        }

        throw new \Exception(
            get_class($this) . ":getId( idx = $idx ) not found"
        );
    }

    public function getStatistic()
    {
        return $this->_statistic;
    }

    public function getGroupBy()
    {
        return $this->_group_by; 
    }


    // ----------- mutators ------------- //

    public function setValues($values)
    {
        $this->_values = $values;
    }

    public function setErrors($errors)
    {
        $this->_errors = $errors;
    }

    public function setXIds($ids)
    {
        $this->_x_ids = $ids;
    }

    public function setXValues($v)
    {
        $this->_x_values = $v;
    }

    public function setUnit($unit)
    {
        $this->_unit = $unit;
    }

    public function setOrderIds($order_ids)
    {
        $this->_order_ids = $order_ids;
    }

    public function setIds($ids)
    {
        $this->_ids = $ids;
    }

    public function setStatistic($stat)
    {
        $this->_statistic= $stat;
    }

    public function setGroupBy($gp)
    {
        $this->_group_by= $gp;
    }

} // class SimpleData
