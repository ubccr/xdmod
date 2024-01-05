<?php

namespace DataWarehouse\Data;

/**
 * This class represents a set of data columns, characterized
 * by a result set and a query.
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 */
class SimpleDataset implements \Stringable
{
    protected $_columnTypes = [];

    protected $_results;

    // TODO: what say we:
    private $_count;

    public function __construct(public $_query)
    {
    }

    // TODO: may be unreliable since _query is public!!
    // JMS Oct 15
    public function getTotalPossibleCount()
    {
        return $this->_query->getCount();
    }

    // --------------------------------------------------------
    // __toString()
    //
    // Helper function for debugging
    // JMS Oct 2015
    // --------------------------------------------------------
    public function __toString(): string {

        return "SimpleDataset: \n"
            . "Total Possible Count: {$this->getTotalPossibleCount()}\n"
            . "Export: ". print_r( $this->export() ) . "\n"
            . "JsonStore:  {$this->exportJsonStore() }";
    } // __toString()

    // --------------------------------------------------------
    // getResults()
    //
    // Fetch result set of specified size ($limit) for specified
    // where clause ($where_column, $where_value).
    //
    // If $get_meta is true, also populate $this->_columnTypes
    // array from PDOStatement::getColumnMeta()
    // This allows us to assign the colnames straightforwardly
    // from the resultset!
    //
    // @return array
    // --------------------------------------------------------
    public function getResults(
        $limit = null,
        $offset = null,
        $force_reexec = false,
        $get_meta = true,
        $where_column = null,
        $where_value = null
    ) {
        if ($force_reexec === true || !isset($this->_results)) {

            $having
                = $where_column != NULL
                ? $where_column . " = " . $where_value
                : null;

            // Build and prepare the query, then call fetchAll().
            $stmt = $this->_query->getRawStatement($limit, $offset, $having);

            $this->_results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Populate _columnTypes array from PDO::getColumnMeta()
            // http://php.net/manual/en/pdostatement.getcolumnmeta.php
            if ($get_meta) {
                $this->_columnTypes = [];

                for ($end = $stmt->columnCount(), $i = 0; $i < $end; $i++) {
                    $raw_meta = $stmt->getColumnMeta($i);
                    $this->_columnTypes[$raw_meta['name']] = $raw_meta;
                }
            }
        }

        return $this->_results;
    } // function getResults()

    // --------------------------------------------------------
    // getColumnLabel()
    // Return either the group by label (if a dimension column)
    // or the statistic label (if a non-dimension column)
    //
    // @param string
    // @param boolean
    //
    // @return string
    // --------------------------------------------------------
    public function getColumnLabel($column_name, $is_dimension)
    {
        if ($is_dimension === true) {

            return $this->getColumnUnit($column_name, $is_dimension);

        } else {
            $statistic = $this->_query->_stats[$column_name];

            return $statistic->getName();
        }
    } // function getColumnLabel()

    // --------------------------------------------------------
    // getColumnUnit()
    // return either the group by label (if a dimension column)
    // or the statistic unit (if a non-dimension column)
    // --------------------------------------------------------
    public function getColumnUnit($column_name, $is_dimension)
    {
        if ($is_dimension === true) {
            $group_by = $this->_query->_group_bys[$column_name];

            return $group_by->getName();
        } else {
            $statistic = $this->_query->_stats[$column_name];

            return $statistic->getUnit();
        }
    } // function getColumnUnit()

    // --------------------------------------------------------
    // convertSQLtoPHP()
    //
    // Convert supplied value from SQL precision to PHP int or float
    //
    // @return value as int or float
    // --------------------------------------------------------
    public static function convertSQLtoPHP($value, $native_type, $precision)
    {
        switch ($native_type) {
            case 'LONGLONG':
            case 'LONG':
                if ($value == 0) { return null; }

                return (int) $value;

            case 'DOUBLE':
            case 'NEWDECIMAL':
                if ($value == 0) { return null; }
                if ($precision == 0) {
                    return (int) $value;
                } else {
                    return (float) $value;
                }

                default:
                    return  $value;
        }

        return $value;
    } // function convertSQLtoPHP()

    // --------------------------------------------------------
    // getColumn()
    //
    // Construct and return column of data from Query
    //
    // @return SimpleData object
    // --------------------------------------------------------
    public function getColumn(
        $column_type_and_name,
        $limit = null,
        $offset= null
    ) {
        $results = $this->getResults($limit, $offset, false, true);

        $column_type = substr($column_type_and_name, 0, 3);
        $column_name = substr($column_type_and_name, 4);

        $is_dimension = $column_type == 'dim';

        $values_column_name    = null;
        $sem_column_name       = null;
        $ids_column_name       = null;
        $order_ids_column_name = null;

        $dataObject = new \DataWarehouse\Data\SimpleData(
            $this->getColumnLabel($column_name, $is_dimension)
        );

        if ($is_dimension) {
            // JMS note: depends upon column name. Used by truncate()
            $dataObject->setGroupBy( $this->_query->_group_bys[$column_name] );

            $values_column_name    = $column_name . '_name';
            $ids_column_name       = $column_name . '_id';
            $order_ids_column_name = $column_name . '_order_id';

        } else {
            // JMS note: depends upon column name. Used by truncate()
            $dataObject->setStatistic( $this->_query->_stats[$column_name] );
            $values_column_name = $column_name;
        }
        $dataObject->setUnit( $this->getColumnLabel($column_name, $is_dimension) );

        $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
            $column_name
        );

        if (isset($this->_query->_stats[$semStatId])) {
            $sem_column_name = $semStatId;
        }

        $columnTypes = $this->_columnTypes;

        // Accumulate the values in a temp variable, then set everything
        // at once.
        $dataErrors = [];
        $dataValues = [];
        $dataIds = [];
        $dataOrderIds = [];

        foreach ($results as $row) {
            if ($values_column_name != NULL) {
                if (!array_key_exists($values_column_name, $row)) {
                    throw new \Exception(
                        "SimpleDataset:getColumn()"
                        . " values_column_name=$values_column_name does not"
                        . " exist in the dataset."
                    );
                } else {
                    $dataValues[] = static::convertSQLtoPHP($row[$values_column_name], $columnTypes[$values_column_name]['native_type'], $columnTypes[$values_column_name]['precision']);
                }
            }

            if ($sem_column_name != NULL) {
                if (!array_key_exists($sem_column_name, $row)) {
                    $dataErrors[] = 0;
                } else {
                    $dataErrors[] = static::convertSQLtoPHP($row[$sem_column_name], $columnTypes[$sem_column_name]['native_type'], $columnTypes[$sem_column_name]['precision']);
                }
            }

            // JMS: in pie chart, this is id
            if ($ids_column_name != NULL) {
                if (!array_key_exists($ids_column_name, $row)) {
                    throw new \Exception(
                        "SimpleDataset:getColumn()"
                        . " ids_column_name=$ids_column_name does not exist"
                        . " in the dataset."
                    );
                } else {
                    $dataIds[] = $row[$ids_column_name];
                }
            }

            // JMS: in pie chart, this is label (e.g. "Math and Phy Sci")
            if ($order_ids_column_name != NULL) {
                if (!array_key_exists($order_ids_column_name, $row)) {
                    throw new \Exception(
                        "SimpleDataset:getColumn()"
                        . " order_ids_column_name=$order_ids_column_name does"
                        . " not exist in the dataset."
                    );
                } else {
                    $dataOrderIds[] = $row[$order_ids_column_name];
                }
            }
        } // foreach ($results as $row)

        $dataObject->setValues( $dataValues );
        $dataObject->setErrors( $dataErrors );

        if ($is_dimension) {
            $dataObject->setIds( $dataIds );
            $dataObject->setOrderIds( $dataOrderIds );
        }

        return $dataObject;
    } // function getColumn()

    // --------------------------------------------------------
    // export()
    //
    // Export column of data from Query
    //
    // @return array(
    //            'title'    => $title,
    //            'title2'   => $title2,
    //            'duration' => $duration_info,
    //            'headers'  => $headers,
    //            'rows'     => $rows,
    //        );
    // Used by e.g. DataWarehouse/Access/MetricExplorer.php
    // --------------------------------------------------------
    public function export($export_title = 'title')
    {
        $headers = [];
        $rows    = [];

        $duration_info = ['start' => $this->_query->getStartDate(), 'end'   => $this->_query->getEndDate()];

        $title  = ['title' => 'None'];
        $title2 = ['parameters' => []];

        $title['title'] = $export_title;
        $title2['parameters'] = $this->_query->roleParameterDescriptions;
        $group_bys = $this->_query->getGroupBys();

        $stats = $this->_query->getStats();
        $has_stats = count($stats) > 0;

        foreach ($group_bys as $group_by) {
            $headers[]
                = $group_by->getId() === 'none'
                ? 'Summary'
                : $group_by->getName();
        }

        foreach ($stats as $stat) {
            $stat_unit  = $stat->getUnit();

            $data_unit = '';
            if (str_ends_with($stat_unit, '%')) {
                $data_unit = '%';
            }

            $column_header = $stat->getName();

            if (
                $column_header != $stat_unit
                && !str_contains($column_header, $stat_unit)
            ) {
                $column_header .= ' (' . $stat_unit . ')';
            }

            $headers[]
                = $column_header
                . (
                    count($this->_query->filterParameterDescriptions) > 0
                    ? ' {'
                        . implode(
                            ', ',
                            $this->_query->filterParameterDescriptions
                        )
                        . '}'
                    : ''
                );
        } // foreach ($stats as $stat)


        foreach ($this->getResults() as $result) {
            $record = [];
            foreach ($group_bys as $group_by) {
                $record[$group_by->getId()]
                    = $result[ sprintf('%s_name', $group_by->getId()) ];
            }

            $stats = $this->_query->getStats();
            foreach ($stats as $stat) {
                $record[$stat->getId()]
                    = $result[$stat->getId()];
            }

            $rows[] = $record;
        } // foreach ($this->getResults() as $result)

        return ['title'    => $title, 'title2'   => $title2, 'duration' => $duration_info, 'headers'  => $headers, 'rows'     => $rows];
    } // export()

    // --------------------------------------------------------
    // exportJsonStore()
    //
    // Export JSON describing the present SimpleDataset object.
    // Used by e.g. DataWarehouse/Access/MetricExplorer.php
    //
    // --------------------------------------------------------
    public function exportJsonStore($limit = null, $offset = null)
    {
        $fields   = [];
        $count    = -1;
        $records  = [];
        $columns  = [];
        $subnotes = [];
        $sortInfo = [];
        $message  = '';
        $count    = $this->_query->getCount();

        $results      = $this->getResults($limit, $offset, false);
        $result_count = count($results);

        if ($result_count > 0) {
            $group_bys = $this->_query->getGroupBys();
            $stats     = $this->_query->getStats();
            $has_stats = count($stats) > 0;

            foreach ($group_bys as $group_by) {
                $fields[] = ["name"    => $group_by->getId(), "type"    => 'string', 'sortDir' => 'DESC'];

                $columns[] = ["header"    => $group_by->getId() === 'none'
                             ? 'Source'
                             : $group_by->getName(), "width"     => 150, "dataIndex" => $group_by->getId(), "sortable"  => true, 'editable'  => false, 'locked'    => $has_stats];
            } // foreach ($group_bys as $group_by)

            foreach ($stats as $stat) {
                $stat_unit = $stat->getUnit();
                $stat_alias = $stat->getId();

                $data_unit = '';
                if (str_ends_with($stat_unit, '%')) {
                    $data_unit = '%';
                }

                $column_header = $stat->getName();

                if (
                    $column_header != $stat_unit
                    && !str_contains($column_header, $stat_unit)
                ) {
                    $column_header .= ' (' . $stat_unit . ')';
                }

                $decimals = $stat->getPrecision();

                $fields[] = ["name"    => $stat_alias, "type"    => 'float', 'sortDir' => 'DESC'];

                $columns[] = ["header"    => $column_header, "width"     => 140, "dataIndex" => $stat_alias, "sortable"  => true, 'editable'  => false, 'align'     => 'right', 'xtype'     => 'numbercolumn', 'format'    => (
                                   $decimals > 0
                                   ? '0,000.' . str_repeat(0, $decimals)
                                   : '0,000'
                               ) . $data_unit];
            }
            foreach ($results as $result) {
                $record = [];
                foreach ($group_bys as $group_by) {
                    $record[$group_by->getId()]
                        = $result[ sprintf('%s_name', $group_by->getId()) ];
                }

                $stats = $this->_query->getStats();
                foreach ($stats as $stat) {
                    $record[$stat->getId()]
                        =  $result[$stat->getId()];
                }

                $records[] = $record;
            }

            $query_orders = $this->_query->getOrders();
            foreach ($query_orders as $query_order) {
                $sortInfo = ['field'     => $query_order->getColumnName(), 'direction' => $query_order->getOrder()];
            }
        } else {
            $message = 'Dataset is empty';

            $fields = [["name" => 'Message', "type" => 'string']];

            $records = [['Message' => $message]];

            $columns = [["header"    => 'Message', "width"     => 600, "dataIndex" => 'Message', "sortable"  => false, 'editable'  => false, 'align'     => 'left', 'renderer'  => "CCR.xdmod.ui.stringRenderer"]];
        }

        $returnData = ["metaData" => ["totalProperty"   => "total", 'messageProperty' => 'message', "root"            => "records", "id"              => "id", "fields"          => $fields], 'message' => '<ul>' . $message . '</ul>', "success" => true, "total"   => $count, "records" => $records, "columns" => $columns];
        if (!empty($sortInfo)) {
            $returnData["metaData"]["sortInfo"] = $sortInfo;
        }

        return $returnData;
    } // exportJsonStore()
} // class SimpleDataset
