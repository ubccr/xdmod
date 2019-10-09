<?php
namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Query;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;

class GroupByServiceProvider extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Service Provider';
    }

    public function getInfo()
    {
        return 'The location of the resource.';
    }

    public function __construct()
    {
        parent::__construct(
            'service_provider',
            array(),
            'SELECT DISTINCT
                gt.organization_id,
                gt.short_name AS short_name,
                gt.long_name AS long_name,
                gt.order_id AS order_id
            FROM modw.serviceprovider gt
            WHERE 1
            ORDER BY order_id'
        );
        $this->_id_field_name = 'organization_id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new Schema('modw');
        $this->configuration_table = new Table($this->modw_schema, 'serviceprovider', 'sp');
    }

    public function applyTo(Query &$query, Table $data_table, $multi_group = false)
    {
        $query->addTable($this->configuration_table);

        $configurationtable_id_field = new TableField($this->configuration_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $configuration_name_field = new TableField($this->configuration_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $configuration_shortname_field = new TableField($this->configuration_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->configuration_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($configurationtable_id_field);
        $query->addField($configuration_name_field);
        $query->addField($configuration_shortname_field);

        $query->addGroup($configuration_name_field);

        $datatable_configuration_id_field = new TableField($data_table, 'service_provider');
        $query->addWhereCondition(new WhereCondition($configurationtable_id_field, '=', $datatable_configuration_id_field));

        $this->addOrder($query, $multi_group);
    }

    public function addOrder(
        Query &$query,
        $multi_group = false,
        $dir = 'asc',
        $prepend = false
    ) {
        $orderField = new OrderBy(new TableField($this->configuration_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'service_provider');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT long_name AS field_label FROM modw.serviceprovider WHERE organization_id IN (_filter_) ORDER BY order_id'
        );
    }
}
