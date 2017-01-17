<?php

namespace DataWarehouse\Query\Model;

/**
 * This is the parent class for a query field that requires calculation
 * via an expression or formula
 *
 * @author Amin Ghadersohi
 */
class FormulaField extends Field
{

    /**
     * @param string $formula
     * @param string $aliasname
     */
    public function __construct($formula, $aliasname)
    {
        parent::__construct($formula, $aliasname);
    }
}
