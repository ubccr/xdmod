<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

/**
 * Storage statistic base class.
 */
class Statistic extends \DataWarehouse\Query\Statistic
{

    // @codingStandardsIgnoreLine
    /**
     * @param string|null $info
     */
    public function __construct(
        $formula,
        $aliasname,
        $label,
        $unit,
        $decimals = 1,
        private $_info = null
    ) {
        parent::__construct($formula, $aliasname, $label, $unit, $decimals);
    }

    public function getWeightStatName()
    {
        return 'record_count';
    }

    public function getInfo()
    {
        if ($this->_info === null) {
            return parent::getInfo();
        } else {
            return $this->_info;
        }
    }
}
