<?php

namespace DataWarehouse\Query\Model;

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
