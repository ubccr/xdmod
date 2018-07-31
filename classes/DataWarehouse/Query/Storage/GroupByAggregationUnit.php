<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Query;

/**
 * Storage query GroupBy base class.
 */
abstract class GroupByAggregationUnit extends GroupBy
{

    /**
     * Order by ID column.
     */
    public function addOrder(
        Query &$query,
        $multiGroup = false,
        $dir = 'ASC',
        $prepend = false
    ) {
        $orderField = new OrderBy(
            new TableField(
                $query->getDataTable(),
                $this->getName() . '_id'
            ),
            $dir,
            $this->getName()
        );

        if ($prepend) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function addWhereJoin(
        Query &$query,
        Table $dataTable,
        $multiGroup,
        $operation,
        $whereConstraint
    ) {
    }

    public function pullQueryParameters(&$request)
    {
        return array();
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return array();
    }
}
