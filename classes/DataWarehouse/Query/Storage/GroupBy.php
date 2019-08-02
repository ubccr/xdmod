<?php
/**
 * @author Amin Ghadersohi
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage;

use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;

/**
 * Class for adding group bys to a storage query.
 */
abstract class GroupBy extends \DataWarehouse\Query\GroupBy
{

    /**
     * Dimension database schema.
     *
     * @var \DataWarehouse\Query\Model\Schema;
     */
    protected $schema;

    /**
     * Dimension database table.
     *
     * @var \DataWarehouse\Query\Model\Table
     */
    protected $table;

    /**
     * Dimension text description.
     *
     * @var string
     */
    protected $info;

    /**
     * Primary key field name.
     *
     * The column in the dimension table that is referenced by the foreign key
     * column in the fact table.
     *
     * @var string
     */
    protected $pk_field_name;

    /**
     * Foreign key field name.
     *
     * The column in the fact table that references the primary key column in
     * the dimension table.
     *
     * @var string
     */
    protected $fk_field_name;

    public function __construct(
        $name,
        array $additional_permitted_parameters = array(),
        $possible_values_query = null
    ) {
        $permitted_parameters = array_unique(
            array_merge(
                array_keys(Aggregate::getRegisteredStatistics()),
                $additional_permitted_parameters
            )
        );

        parent::__construct(
            $name,
            $permitted_parameters,
            $possible_values_query
        );

        $this->schema = new Schema('modw');
    }

    /**
     * Get info.
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Order by "_order_id_field_name" column.
     */
    public function addOrder(
        Query &$query,
        $multiGroup = false,
        $dir = 'ASC',
        $prepend = false
    ) {
        $orderField = new OrderBy(
            new TableField(
                $this->table,
                $this->_order_id_field_name
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

    public function applyTo(
        Query &$query,
        Table $data_table,
        $multi_group = false
    ) {
        $query->addTable($this->table);

        $id_field = new TableField(
            $this->table,
            $this->_id_field_name,
            $this->getIdColumnName($multi_group)
        );
        $query->addField($id_field);
        $query->addGroup($id_field);

        $pk_field = new TableField(
            $this->table,
            $this->pk_field_name,
            $this->getIdColumnName($multi_group)
        );
        $query->addField($pk_field);

        $name_field = new TableField(
            $this->table,
            $this->_long_name_field_name,
            $this->getLongNameColumnName($multi_group)
        );
        $query->addField($name_field);

        $shortname_field = new TableField(
            $this->table,
            $this->_short_name_field_name,
            $this->getShortNameColumnName($multi_group)
        );
        $query->addField($shortname_field);

        $order_id_field = new TableField(
            $this->table,
            $this->_order_id_field_name,
            $this->getOrderIdColumnName($multi_group)
        );
        $query->addField($order_id_field);

        $fact_table_fk_field = new TableField(
            $data_table,
            $this->fk_field_name
        );
        $query->addWhereCondition(
            new WhereCondition(
                $pk_field,
                '=',
                $fact_table_fk_field
            )
        );

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(
        Query &$query,
        Table $data_table,
        $multi_group,
        $operation,
        $whereConstraint
    ) {
        $query->addTable($this->table);

        $dimension_table_pk_field = new TableField(
            $this->table,
            $this->pk_field_name
        );

        $fact_table_fk_field = new TableField(
            $data_table,
            $this->fk_field_name
        );

        $query->addWhereCondition(
            new WhereCondition(
                $dimension_table_pk_field,
                '=',
                $fact_table_fk_field
            )
        );

        if (is_array($whereConstraint)) {
            $whereConstraint = "('" . implode("','", $whereConstraint) . "')";
        }

        $query->addWhereCondition(
            new WhereCondition(
                $dimension_table_pk_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            '_filter_',
            $this->fk_field_name
        );
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            sprintf(
                'SELECT %s AS field_label FROM %s WHERE %s IN (_filter_) ORDER BY %s',
                $this->_long_name_field_name,
                $this->table->getQualifiedName(),
                $this->pk_field_name,
                $this->_long_name_field_name
            )
        );
    }

    public function getDefaultDatasetType()
    {
        return 'timeseries';
    }
}
