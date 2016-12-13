<?php

namespace DataWarehouse\Query;

/**
 * Abstract class for defining classes pertaining to a query field that
 * calculates some statistic.
 *
 * @author Amin Ghadersohi
 */
abstract class Statistic extends \DataWarehouse\Query\Model\FormulaField
{
    /**
     * This affects how the query will sort by a stat based on this
     * group by.
     *
     * Valid values: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC,
     *    SORT_STRING.
     *
     * Alternatively a null value would mean no sorting.
     * Refer to: http://php.net/manual/en/function.array-multisort.php
     *
     * @var int
     */
    private $_order_by_stat_option = null;

    /**
     * The statistic's label.
     *
     * @var string
     */
    private $_label = null;

    /**
     * The statistic's unit.
     *
     * @var string
     */
    private $_unit = null;

    /**
     * The number of decimal places used when displaying the statistic.
     *
     * @var int
     */
    private $_decimals;

    /**
     * Statistic constructor.
     *
     * @param string $formula The formula used to calculate the
     *    statistic.
     * @param string $aliasname The alias used when calculating the
     *    statistic.
     * @param string $label The label applied to the statistic.
     * @param string $unit The unit of the statistic.
     * @param int $decimals The number of decimal places to use when
     *    displaying the statistic.
     */
    public function __construct(
        $formula,
        $aliasname,
        $label,
        $unit,
        $decimals = 1
    ) {
        parent::__construct($formula, $aliasname);

        $this->setOrderByStat(SORT_DESC);
        $this->setLabel($label);
        $this->setUnit($unit);
        $this->setDecimals($decimals);
    }

    /**
     * Returns the weight statistic name.
     *
     * Returns "weight" unless overriden.
     *
     * @return string
     */
    public function getWeightStatName()
    {
        return 'weight';
    }

    /**
     * Returns the label with the units optionally included.
     *
     * @param bool $units True if the units should be included.
     *
     * @return string
     */
    public function getLabel($units = true)
    {
        return
               $units
            && $this->_label != $this->_unit
            && strpos($this->_label, $this->_unit) === false
            ? $this->_label . ' (' . $this->_unit . ')'
            : $this->_label;
    }

    /**
     * Label mutator.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->_label = $label;
    }

    /**
     * Unit accessor.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->_unit;
    }

    /**
     * Unit mutator.
     *
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->_unit = $unit;
    }

    /**
     * Returns the number of decimals that are ued when displaying the
     * statistic.
     *
     * @param float $data_min Minimum value that will be displayed.
     * @param float $data_max Maximum value that will be displayed.
     *
     * @return int
     */
    public function getDecimals($data_min = null, $data_max = null)
    {
        $decimals =  $this->_decimals;

        if ($this->_decimals > 0 && $data_min !== null && $data_max !== null) {
            if ($data_min != 0 && $data_max != 0) {
                $min = $data_min;
            } elseif ($data_min == 0) {
                $min = $data_max;
            } else {
                $min = $data_min;
            }

            if ($min != 0 && $data_max / $min < 1000) {
                if ($min < 0.000001) {
                    $decimals = 8;
                } elseif ($min < 0.00001) {
                    $decimals = 7;
                } elseif ($min < 0.0001) {
                    $decimals = 6;
                } elseif ($min < 0.001) {
                    $decimals = 5;
                } elseif ($min < 0.01) {
                    $decimals = 4;
                } elseif ($min < 0.1) {
                    $decimals = 3;
                }
            }
        }

        return $decimals;
    }

    /**
     * Decimals mutator.
     *
     * @param int $decimals
     */
    public function setDecimals($decimals)
    {
        $this->_decimals = $decimals;
    }

    /**
     * Sets the method by which the query would be sorted based on the
     * stat, if any.
     *
     * @param int $sort_option SORT_ASC, SORT_DESC, SORT_REGULAR,
     *    SORT_NUMERIC, SORT_STRING, null (default: SORT_DESC).
     *
     * Refer to: http://php.net/manual/en/function.array-multisort.php
     */
    public function setOrderByStat($sort_option = SORT_DESC)
    {
        if (
            isset($sort_option)
            && $sort_option != SORT_ASC
            && $sort_option != SORT_DESC
            && $sort_option != SORT_REGULAR
            && $sort_option != SORT_NUMERIC
            && $sort_option != SORT_STRING
        ) {
            throw new Exception(
                "GroupBy::setOrderByStat(sort_option = $sort_option): error"
                . " - invalid sort_option"
            );
        }

        $this->_order_by_stat_option = $sort_option;
    }

    /**
     * @return int The value of the _order_by_stat_option variable.
     */
    public function getOrderByStatOption()
    {
        return $this->_order_by_stat_option;
    }

    /**
     * Is this statistic visible in the list of statistics.
     *
     * True unless overriden.
     *
     * @return bool
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * Returns the label without the unit.
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->getLabel(false);
    }

    /**
     * Returns the definition of the statistic.
     *
     * Returns the empty string unless overriden.
     *
     * @return string
     */
    public function getDefinition()
    {
        return '';
    }

    /**
     * Returns an HTML fragment describing the statistic.
     *
     * @param \DataWarehouse\Query\GroupBy $group_by
     *
     * @return string
     */
    public function getDescription(\DataWarehouse\Query\GroupBy &$group_by)
    {
        return '<b>' . $this->getLabel() . '</b>: ' . $this->getInfo();
    }

    /**
     * Get any additional where conditions associated with the statistic
     * or null if there are none.
     *
     * @return mixed
     */
    public function getAdditionalWhereCondition()
    {
        return null;
    }
}
