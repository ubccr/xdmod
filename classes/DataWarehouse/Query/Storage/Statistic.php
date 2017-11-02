<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

/**
 * Storage statistic base class.
 */
class Statistic extends \DataWarehouse\Query\Statistic
{

    /**
     * @var string|null
     */
    private $_info; // @codingStandardsIgnoreLine

    public function __construct(
        $formula,
        $aliasname,
        $label,
        $unit,
        $decimals = 1,
        $info = null
    ) {
        parent::__construct($formula, $aliasname, $label, $unit, $decimals);
        $this->_info = $info;
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
