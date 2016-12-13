<?php
/**
 * A database table constructed by a subquery.
 */
namespace DataWarehouse\Query\Model;

use Exception;

class SubqueryTable extends Table
{
    /**
     * Constructor.
     *
     * @param string $subquery  The subquery to use for this table.
     *                          Parentheses are added automatically.
     * @param string $aliasName The alias to use for the table.
     * @param string $joinIndex The index the table should use for joining.
     *                          (Defaults to none.)
     */
    public function __construct(
        $subquery,
        $aliasName,
        $joinIndex = ''
    ) {
        if (empty($aliasName)) {
            throw new Exception('Subquery tables must be given an alias.');
        }

        parent::__construct(
            new Schema(''),
            "($subquery)",
            $aliasName,
            $joinIndex
        );
    }
}
