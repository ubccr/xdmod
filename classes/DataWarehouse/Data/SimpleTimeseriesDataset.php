<?php

namespace DataWarehouse\Data;

use CCR\DB;

/**
 * This class represents a set of timeseries data columns
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 */
class SimpleTimeseriesDataset extends SimpleDataset
{
    public function __construct(&$query)
    {
        parent::__construct($query);
    }

    // JMS TODO: determine how we will order "top n" dimension values.
    //  @refer getColumnUniqueOrdered() (existing code and also previous)
    //  @refer getColumnSortedMax() (JMS, sort according to underlying metric)
    //
    // JMS TODO: factor out the common stuff, call the parent and
    // add in the start_ts assignment...
    //
    // JMS TODO: is there a good reason to use a fresh PDO call instead of getResults
    // in the UniqueOrdered case below?
    // Yes.
    //
    // JMS TODO: can we generalize and push to the superclass the accumulation of
    // error, value, id, orderid, and possibly startts values below? This code
    // is more or less repeated wholesale in 3 places.
    //      which is to say:
    //          obvious similarities in
    //              getColumn()
    //              getColumnUniqueOrdered()
    //              getTimestamp()
    //                  ...and then there's the fact that getColumn() overrides
    //                  the base class. Cmon let's be OO here...

    //-------------------------------------------------
    // public function getColumn
    //
    // Note that $where_value must be single valued.
    //
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    //-------------------------------------------------
    public function getColumn(
        $column_type_and_name,
        $limit = null,
        $offset = null,
        $wherecolumn_name = null,
        $where_value = null
    ) {
        $column_type = substr($column_type_and_name, 0, 3);
        $column_name = substr($column_type_and_name, 4);

        $is_dimension = $column_type == 'dim';

        if ($column_type_and_name == 'time') {
            $is_dimension = true;
            $column_name = $this->_query->getAggregationUnit()->getUnitName();
        }

        // Handle the where clause for ids, equivalence or not-in:
        $hasWhere = $wherecolumn_name != NULL && $where_value !== NULL;

        // run the query; set $this->_results
        $this->getResults(
            $limit,
            $offset,
            true, // force reexec!!!
            true, // get column metadata so we can assign colnames
            $hasWhere ? $wherecolumn_name        : null, // where column name
            $hasWhere ? "'" . $where_value . "'" : null  // where values
        );

        $dataObject = $this->assembleDataObject($column_name,
                                        $is_dimension, 
                                        $hasWhere, 
                                        $wherecolumn_name, 
                                        $where_value); 
        return $dataObject;
    }

    //-------------------------------------------------
    // public function assembleDataObject
    //
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    //-------------------------------------------------
    public function assembleDataObject( $column_name, 
                                        $is_dimension, 
                                        $hasWhere, 
                                        $wherecolumn_name, 
                                        $where_value) 
    {
        // assign column names for returned data:
        $values_column_name    = null;
        $sem_column_name       = null;
        $ids_column_name       = null;
        $order_ids_column_name = null;
        $start_ts_column_name  = null; // timeseries only

        $start_ts_column_name  = $this->_query->getAggregationUnit()->getUnitName() 
                                . '_start_ts';
        // standard error
        if (isset($this->_query->_stats['sem_' . $column_name])) {
            $sem_column_name = 'sem_' . $column_name;
        }

        // create the data object
        $dataObject = new \DataWarehouse\Data\SimpleTimeseriesData(
            $this->getColumnLabel($column_name, $is_dimension)
        );

        $dataObject->setUnit( $this->getColumnLabel($column_name, $is_dimension) );
        if ($is_dimension) {
            $dataObject->setGroupBy( $this->_query->_group_bys[$column_name] );
            $values_column_name    = $column_name . '_name';
            $ids_column_name       = $column_name . '_id';
            $order_ids_column_name = $column_name . '_order_id';

        } else {
            $dataObject->setStatistic( $this->_query->_stats[$column_name] );
            $values_column_name = $column_name;
        }

        // accumulate the values in temp variables, then set everything at once. 
        $dataErrors = array();
        $dataValues = array();
        $dataIds = array();
        $dataOrderIds = array();
        $dataStartTs = array();

        // walk through the result set and assign ...
        foreach ($this->_results as $row) {

            // This section unique to TS
            if ($hasWhere && $row[$wherecolumn_name] != $where_value) {
                continue;
            }

            if (
                $start_ts_column_name != NULL
                && !isset($row[$start_ts_column_name])
            ) {
                throw new \Exception(
                    get_class($this). ":" . __FUNCTION__ ."()"
                    . " start_ts_column_name=$start_ts_column_name does not"
                    . " exist in the dataset."
                );
            }

            $start_ts  = $row[$start_ts_column_name];
            $dataStartTs[] = $start_ts;

            // End of section unique to TS

            if ($values_column_name != NULL) {
                if (!array_key_exists($values_column_name, $row)) {
                    throw new \Exception(
                        get_class($this). ":" . __FUNCTION__ ."()"
                        . " values_column_name=$values_column_name does not"
                        . " exist in the dataset.");
                } else {
                    $dataValues[] = $this->convertSQLtoPHP(
                        $row[$values_column_name],
                        $this->_columnTypes[$values_column_name]['native_type'],
                        $this->_columnTypes[$values_column_name]['precision']
                    );
                }
            }

            if ($sem_column_name != NULL) {
                if (!array_key_exists($sem_column_name, $row)) {
                    $dataErrors[] = 0;
                } else {
                    $dataErrors[] = $this->convertSQLtoPHP(
                        $row[$sem_column_name],
                        $this->_columnTypes[$sem_column_name]['native_type'],
                        $this->_columnTypes[$sem_column_name]['precision']
                    );
                }
            }

            if ($ids_column_name != NULL) {
                if (!array_key_exists($ids_column_name, $row)) {
                    throw new \Exception(
                        get_class($this). ":" . __FUNCTION__ ."()"
                        . " ids_column_name=$ids_column_name does not exist"
                        . " in the dataset."
                    );
                } else {
                    $dataIds[] = $row[$ids_column_name];
                }
            }

            if ($order_ids_column_name != NULL) {
                if (!array_key_exists($order_ids_column_name, $row)) {
                    throw new \Exception(
                        get_class($this). ":" . __FUNCTION__ ."()"
                        . " order_ids_column_name=$order_ids_column_name does"
                        . " not exist in the dataset."
                    );
                } else {
                    $dataOrderIds[] = $row[$order_ids_column_name];
                }
            }
        }

        $dataObject->setValues( $dataValues );
        $dataObject->setErrors( $dataErrors );
        $dataObject->setStartTs( $dataStartTs );

        if ($is_dimension) {
            $dataObject->setIds( $dataIds );
            $dataObject->setOrderIds( $dataOrderIds );
        }

        return $dataObject;
    } // function assembleDataObject

    //-------------------------------------------------
    // public function getSummaryOperation
    //
    // Use data object's statistic alias to determine
    // what operation will be used for data series
    // summarization beyond the top-n, for display.
    // 
    // Summarization performed by database will consist
    // of SUM, MIN, MAX, or AVG by time aggregation unit
    //
    // @return String
    //-------------------------------------------------
    public function getSummaryOperation($stat) {

        // statistics alias for the data object
        //$stat = $dataObject->getStatistic()->getAlias();
        $operation = "SUM";

        // Determine operation for summarizing the dataset
        if ( strpos($stat, 'min_') !== false ) {
            $operation = "MIN";

        } elseif ( strpos($stat, 'max_') !== false ) {
            $operation = "MAX";

        } else {
            $useMean
                = strpos($stat, 'avg_') !== false
                || strpos($stat, 'count') !== false
                || strpos($stat, 'utilization') !== false
                || strpos($stat, 'rate') !== false
                || strpos($stat, 'expansion_factor') !== false;

            $operation = $useMean ? "AVG" : "SUM";  
        } // if strpos

        return $operation;
    } // getSummaryOperation

    //-------------------------------------------------
    // public function getUniqueCount
    //
    // Query for the total count of unique dimension values 
    // in the chosen $realm, over the selected time period. 
    // 
    // Used by HighChartTimeseries2 configure()
    //
    // @return int
    //-------------------------------------------------
    public function getUniqueCount(
        $column_name,
        $realm 
    ) {
        // Following are true but unneeded:
        //$is_dimension = true;
        //$column_type = 'dim';

        $query_classname = '\\DataWarehouse\\Query\\' . $realm . '\\Aggregate';

        $agg_query = new $query_classname(
            $this->_query->getAggregationUnit()->getUnitName(),
            $this->_query->getStartDate(),
            $this->_query->getEndDate(),
            null,
            null,
            array(),
            'tg_usage',
            array(),
            false
        );

        $agg_query->addGroupBy($column_name);

        foreach ($this->_query->_stats as $stat_name => $stat) {
            $agg_query->addStat($stat_name);
        }

        // we only return a count here, so remove this unneeded order by:
        $agg_query->clearOrders();
        $agg_query->setParameters($this->_query->parameters);

        return $agg_query->getCount();
    } // getUniqueCount

    //-------------------------------------------------
    // public function getColumnUniqueOrdered
    //
    // Query for the highest average $limit dimension values 
    // in the chosen $realm, over the selected time period. 
    //
    // This is the old way to fetch, order, and return
    // the "top n" values for a given dimension.
    // What "top" means varies by the type of column
    // we are dealing with. Some are sorted by dimension label, 
    // others by metric.
    // 
    // Used by HighChartTimeseries2 configure() for
    // fetching the top $limit examples of a metric.
    //
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    //-------------------------------------------------
    public function getColumnUniqueOrdered(
        $column_type_and_name,
        $limit = null,
        $offset = null,
        $realm = 'Jobs'
    ) {
        $column_type = substr($column_type_and_name, 0, 3);
        $column_name = substr($column_type_and_name, 4);

        $is_dimension = $column_type == 'dim';

        if ($column_type_and_name == 'time') {
            $is_dimension = true;
            $column_name = $this->_query->getAggregationUnit()->getUnitName();
        }

        $query_classname = '\\DataWarehouse\\Query\\' . $realm . '\\Aggregate';

        $agg_query = new $query_classname(
            $this->_query->getAggregationUnit()->getUnitName(),
            $this->_query->getStartDate(),
            $this->_query->getEndDate(),
            null,
            null,
            array(),
            'tg_usage',
            array(),
            false
        );

        $agg_query->addGroupBy($column_name);

        foreach ($this->_query->_stats as $stat_name => $stat) {
            $agg_query->addStat($stat_name);
        }

        // No need to clear orders as there is no order by time; this is an Aggregate query. Keep
        // the ordering, and we'll actually match the dataset ordering that aggregate achieves.
        // JMS 12 Nov 15

        // Note: only one item can be stored in Query::sortInfo array at present
        // @refer Query member function addOrderByAndSetSortInfo()
        if (isset($this->_query->sortInfo)) {
            foreach ($this->_query->sortInfo as $sort) {
                $agg_query->addOrderBy(
                    $sort['column_name'],
                    $sort['direction']
                );
            }
        }

        $agg_query->setParameters($this->_query->parameters);

        $dataObject = new \DataWarehouse\Data\SimpleTimeseriesData($column_name);

        if ($is_dimension) {
            $dataObject->setGroupBy( $agg_query->_group_bys[$column_name] );

            $values_column_name    = $column_name . '_name';
            $ids_column_name       = $column_name . '_id';
            $order_ids_column_name = $column_name . '_order_id';

            $dataObject->setUnit( $agg_query->_group_bys[$column_name] );
        } else {
            $dataObject->setStatistic ( $agg_query->_stats[$column_name] );
            $values_column_name = $column_name;

            $dataObject->setUnit( $agg_query->_stats[$column_name] );
        }

        $query_string = $agg_query->getQueryString($limit,  $offset);
        
        $statement = DB::factory($agg_query->_db_profile)->query(
            $query_string,
            array(),
            true
        );
        $statement->execute();

        $columnTypes = array();

        for ($end = $statement->columnCount(), $i = 0; $i < $end; $i++) {
            $raw_meta = $statement->getColumnMeta($i);
            $columnTypes[$raw_meta['name']] = $raw_meta;
        }

        // accumulate the values in a temp variable, then set everything
        // at once. 
        $dataErrors = array();
        $dataValues = array();
        $dataIds = array();
        $dataOrderIds = array();
        $dataStartTs = array();

        while (
            $row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)
        ) {
            if ($values_column_name != NULL) {
                if (!array_key_exists($values_column_name, $row)) {
                    throw new \Exception(
                        get_class($this) .":".  __FUNCTION__ ."()"
                        . " values_column_name=$values_column_name does not"
                        . " exist in the dataset."
                    );
                } else {
                    $dataValues[] = $this->convertSQLtoPHP(
                        $row[$values_column_name],
                        $columnTypes[$values_column_name]['native_type'],
                        $columnTypes[$values_column_name]['precision']
                    );
                }

                $sem_column_name = 'sem_' . $values_column_name;

                if (!array_key_exists($sem_column_name, $row)) {
                    $dataErrors[] = 0;
                } else {
                    $dataErrors[] = $this->convertSQLtoPHP(
                        $row[$sem_column_name],
                        $columnTypes[$sem_column_name]['native_type'],
                        $columnTypes[$sem_column_name]['precision']
                    );
                }
            }

            if ($ids_column_name != NULL) {
                if (!array_key_exists($ids_column_name, $row)) {
                    throw new \Exception(
                        get_class($this) .":".  __FUNCTION__ ."()"
                        . " ids_column_name=$ids_column_name does not exist"
                        . " in the dataset."
                    );
                } else {
                    $dataIds[] = $row[$ids_column_name];
                }
            }

            if ($order_ids_column_name != NULL) {
                if (!array_key_exists($order_ids_column_name, $row)) {
                    throw new \Exception(
                        get_class($this) .":".  __FUNCTION__ ."()"
                        . " order_ids_column_name=$order_ids_column_name does"
                        . " not exist in the dataset."
                    );
                } else {
                    $dataOrderIds[] = $row[$order_ids_column_name];
                }
            }
        }

        $dataObject->setValues( $dataValues );
        $dataObject->setErrors( $dataErrors );
        $dataObject->setStartTs( $dataStartTs);

        // Generalize for class type: TODO JMS
        if ($is_dimension) {
            $dataObject->setIds( $dataIds );
            $dataObject->setOrderIds( $dataOrderIds );
        }

        return $dataObject;
    } 
        

    //-------------------------------------------------
    // public function getTimestamps
    //
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    //-------------------------------------------------
    public function getTimestamps()
    {
        $raw_timetamps = $this->_query->getTimestamps();
        $column_name = $this->_query->getAggregationUnit()->getUnitName();

        $timestampsDataObject = new \DataWarehouse\Data\SimpleTimeseriesData(
            $this->getColumnLabel($column_name, true)
        );

        $values_column_name    = 'short_name';
        $ids_column_name       = 'id';
        $order_ids_column_name = 'id';
        $start_ts_column_name  = 'start_ts';

        // JMS:  accumulate the values in a temp variable, then set everything
        // at once. 
        $dataErrors = array();
        $dataValues = array();
        $dataIds = array();
        $dataOrderIds = array();
        $dataStartTs = array();

        foreach ($raw_timetamps as $raw_timetamp) {
            if (!array_key_exists($start_ts_column_name, $raw_timetamp)) {
                throw new \Exception(
                    get_class($this) .":".  __FUNCTION__ ."()"
                    . " start_ts_column_name=$start_ts_column_name does not"
                    . " exist in the dataset."
                );
            }

            $start_ts = $raw_timetamp[$start_ts_column_name];
            $dataStartTs[] = $start_ts;

            if (!array_key_exists($values_column_name, $raw_timetamp)) {
                throw new \Exception(
                    get_class($this) .":".  __FUNCTION__ ."()"
                    . " values_column_name=$values_column_name does not exist"
                    . " in the dataset."
                );
            } else {
                $dataValues[]
                    = $raw_timetamp[$values_column_name];
            }

            $dataErrors[] = 0;

            if (!array_key_exists($ids_column_name, $raw_timetamp)) {
                throw new \Exception(
                    get_class($this) .":".  __FUNCTION__ ."()"
                    . " ids_column_name=$ids_column_name does not exist in"
                    . " the dataset."
                );
            } else {
                $dataIds[]
                    = $raw_timetamp[$ids_column_name];
            }

            if (!array_key_exists($order_ids_column_name, $raw_timetamp)) {
                throw new \Exception(
                    get_class($this) .":".  __FUNCTION__ ."()"
                    . " order_ids_column_name=$order_ids_column_name does not"
                    . " exist in the dataset."
                );
            } else {
                $dataOrderIds[]
                    = $raw_timetamp[$order_ids_column_name];
            }
        }

        $timestampsDataObject->setValues( $dataValues );
        $timestampsDataObject->setErrors( $dataErrors );
        $timestampsDataObject->setStartTs( $dataStartTs);

        // Generalize for class type: TODO JMS may need to test for this
        //if ($is_dimension) {
            $timestampsDataObject->setIds( $dataIds );
            $timestampsDataObject->setOrderIds( $dataOrderIds );
        //}
        return $timestampsDataObject;
    } // function getTimestamps()

    //-------------------------------------------------
    // public function getColumnIteratorBy
    //
    // @param string description of column as metric
    // @param SimpleTimeseriesData column
    //
    // @return SimpleTimeseriesDataIterator
    //-------------------------------------------------
    public function getColumnIteratorBy(
        $column_type_and_name,
        $datagroup_type_and_name
    ) {
        return new SimpleTimeseriesDataIterator(
            $this,
            $column_type_and_name,
            $datagroup_type_and_name
        );
    } // function getColumnIteratorBy

    //-------------------------------------------------
    // public function export()
    //
    // @param title of export
    //
    // @see SimpleDataset::export
    //-------------------------------------------------
    public function export($export_title = 'title')
    {
        $exportData = parent::export($export_title);

        // Organize the rows by dimension and get all dates used.
        $dateSet = array();
        $dimensionValuesSet = array();
        foreach ($exportData['rows'] as $row) {
            $rowDate = reset($row);
            $rowDimension = next($row);
            $rowValue = next($row);

            $dateSet[$rowDate] = true;
            $dimensionValuesSet[$rowDimension][$rowDate] = $rowValue;
        }

        // Convert the date set into an ordered list.
        $dates = array_keys($dateSet);
        sort($dates);

        // Order the dimensions as requested.
        $queryGroupByName = 'none';
        foreach ($this->_query->getGroupBys() as $groupBy) {
            $groupByName = $groupBy->getName();
            if (
                $groupByName !== 'day'
                && $groupByName !== 'month'
                && $groupByName !== 'quarter'
                && $groupByName !== 'year'
            ) {
                $queryGroupByName = $groupByName;
                break;
            }
        }
        if ($queryGroupByName !== 'none') {
            $datasetIterator = $this->getColumnIteratorBy(
                'met_' . reset($this->_query->getStats())->getAlias(),
                $this->getColumnUniqueOrdered(
                    'dim_' . $queryGroupByName,
                    null,
                    null,
                    $this->_query->getRealmName()
                )
            );

            $dimensionNames = array_map(function ($dataset) {
                return $dataset->getName();
            }, iterator_to_array($datasetIterator));

            $orderedDimensionValuesSet = array();
            foreach ($dimensionNames as $dimensionName) {
                if (!array_key_exists($dimensionName, $dimensionValuesSet)) {
                    continue;
                }

                $orderedDimensionValuesSet[$dimensionName] = $dimensionValuesSet[$dimensionName];
            }
            foreach ($dimensionValuesSet as $dimension => $dimensionValues) {
                if (array_key_exists($dimension, $orderedDimensionValuesSet)) {
                    continue;
                }

                $orderedDimensionValuesSet[$dimension] = $dimensionValues;
            }

            $dimensionValuesSet = $orderedDimensionValuesSet;
        }

        // Change the set of rows and their headers so that there is one
        // column per dimension.
        $newHeaders = array_slice($exportData['headers'], 0, 1);
        $seriesName = $exportData['headers'][2];
        foreach ($dimensionValuesSet as $dimension => $dimensionValues) {
            $newHeaders[] = "[$dimension] $seriesName";
        }

        $dateOrderedRows = array();
        foreach ($dates as $date) {
            $dateRow = array(
                $date,
            );

            foreach ($dimensionValuesSet as $dimensionValues) {
                $dateRow[] = \xd_utilities\array_get($dimensionValues, $date, 0);
            }

            $dateOrderedRows[] = $dateRow;
        }

        $exportData['headers'] = $newHeaders;
        $exportData['rows'] = $dateOrderedRows;

        return $exportData;
    }

    //-------------------------------------------------
    // public function getSummarizedColumn
    //
    // Query to summarize the non-top $limit timeseries metrics 
    // for $column_name in the chosen $realm, over the selected 
    // time period. 
    //
    // Error values are not retained as they are not meaningful
    // here. This is consistent with the previous version of this
    // functionality.
    // 
    // Used by HighChartTimeseries2 configure() for
    // fetching and summarizing the "other" examples of a metric.
    //
    // @param type and name of column being summarized
    // @param type and name of where clause column
    // @param count of values we are averaging over, if operation is AVG.
    // @param array of ids corresponding to top n. Exclude these in where clause
    // @param name of data's realm 
    // 
    // @return \DataWarehouse\Data\SimpleTimeseriesData
    // @author J.M. Sperhac
    //-------------------------------------------------
    public function getSummarizedColumn(
        $column_name,
        $where_name,
        $normalizeBy, // should we report the mean for the summarized column?
                       // if so, normalize by this total.
        $whereExcludeArray, // array of top-n ids to exclude from query
        $realm = 'Jobs'
    ) {

        // determine the selected time aggregation unit
        $aggunit_name = $this->_query->getAggregationUnit()->getUnitName();

        // assign column names for returned data:
        $values_column_name    = $column_name; 
        $start_ts_column_name  = $aggunit_name . '_start_ts';
        $count_ts_column_name = 'count_by_ts_unit';

        $query_classname = '\\DataWarehouse\\Query\\' . $realm . '\\Timeseries';

        // JMS test
        /*
        throw new \Exception(
                        get_class($this) ." ".  __FUNCTION__
                        ." column_name=$column_name,   
                        where_name=$where_name,   
                        whereExcludeArray=".implode(",",$whereExcludeArray).", 
                        realm=$realm,   
                        aggunit_name=$aggunit_name,   
                        query_classname=$query_classname
                        ");
        */

        // Construct a TS query using the selected time agg unit 
        // Group by the nothing in constructor call, so you *dont* roll up by time;
        // later, add the where column name for the group by 
        $q = new $query_classname(
            $aggunit_name,  // $this->_query->getAggregationUnit()->getUnitName(),
            $this->_query->getStartDate(),
            $this->_query->getEndDate(),
            null,           // no group by in constructor
            null,           // statname associated with query in constructor
            array(),        // params
            'tg_usage',     // query groupname
            array(),        // parameter_description
            false           // single_stat
        );

        // add a where condition on the array of excluded ids. These are the top-n
        if (!empty($whereExcludeArray)) {
            $w = $q->addWhereAndJoin($where_name, "NOT IN", $whereExcludeArray);
        }

        // add the stats
        foreach ($this->_query->_stats as $stat_name => $stat) {
            $q->addStat($stat_name);
        }

        // if we have additional parameters:
        $q->setParameters($this->_query->parameters);

        // group on the where clause column, which will be enforced after time agg. unit
        $q->addGroupBy($where_name);

        // set up data object for return
        $dataObject = new \DataWarehouse\Data\SimpleTimeseriesData($column_name);
        $dataObject->setStatistic ( $q->_stats[$column_name] );
        // set unit part of label on dataseries' legend
        $dataObject->setUnit( $this->getColumnLabel( $column_name, false) );

        // perform the summarization right in the database

        // Take AVG, MIN, MAX, or SUM of the column_name, grouped by time aggregation unit
        $statAlias =  $dataObject->getStatistic()->getAlias();
        $operation = $this->getSummaryOperation($statAlias);

        // Now perform the summarization, making use of the Query class query string, fetch:
        //    * the timeseries unit appropriate to the time aggregation level, 
        //    * the actual count of values being summarized over (for normalizing averaging)
        //    * the averaged/min/max/summed data over the time aggregation unit.
        $query_string = "SELECT t.$start_ts_column_name AS $start_ts_column_name, 
                                count( t.$start_ts_column_name ) as $count_ts_column_name, 
                                $operation( t.$column_name ) AS $column_name "
                        . " FROM ( "
                        .   $q->getQueryString()
                        . " ) t "
                        . " GROUP BY t.$start_ts_column_name";

        // JMS test
        /*
        throw new \Exception(
                        get_class($this) ." ".  __FUNCTION__ 
                        ." statAlias ".$statAlias
                        ." operation ".$operation 
                        ." column_name ".$column_name
                        ." where_name ".$where_name
                        ." realm  ".$realm 
                        ." aggunit_name ".$aggunit_name 
                        ." final_qs: ". $query_string );
        */

        $statement = DB::factory($q->_db_profile)->query(
            $query_string,
            array(),
            true
        );
        $statement->execute();

        $columnTypes = array();

        for ($end = $statement->columnCount(), $i = 0; $i < $end; $i++) {
            $raw_meta = $statement->getColumnMeta($i);
            $columnTypes[$raw_meta['name']] = $raw_meta;
        }

        // accumulate the values in a temp variable, then set everything
        // at once. 
        $dataValues = array();
        $dataStartTs = array();

        while ( $row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

            if (
                $start_ts_column_name != NULL
                && !isset($row[$start_ts_column_name])
            ) {
                throw new \Exception(
                    get_class($this). ":" . __FUNCTION__ ."()"
                    . " start_ts_column_name=$start_ts_column_name does not"
                    . " exist in the dataset."
                );
            }

            $start_ts  = $row[$start_ts_column_name];
            $dataStartTs[] = $start_ts;

            // populate the values
            if ($values_column_name != NULL) {
                if (!array_key_exists($values_column_name, $row)) {
                    throw new \Exception(
                        get_class($this) .":".  __FUNCTION__ ."()"
                        . " values_column_name=$values_column_name does not"
                        . " exist in the dataset."
                    );
                } else {


                    // get the data value
                    $dataCurrentValue = $this->convertSQLtoPHP(
                        $row[$values_column_name],
                        $columnTypes[$values_column_name]['native_type'],
                        $columnTypes[$values_column_name]['precision']
                    );

                    if ($operation == 'AVG') {
                        if (!array_key_exists($count_ts_column_name, $row)) {
                            throw new \Exception(
                                get_class($this) .":".  __FUNCTION__ ."()"
                                . " count_ts_column_name=$count_ts_column_name does not"
                                . " exist in the dataset."
                            );
                        } else {
                            $countTsCurrentValue = $this->convertSQLtoPHP(
                                $row[$count_ts_column_name],
                                $columnTypes[$values_column_name]['native_type'],
                                $columnTypes[$values_column_name]['precision']
                            );

                            // if we are taking AVG, correct it to 'avg of n others', n is $normalizeBy value
                            $dataCurrentValue = $dataCurrentValue * ($countTsCurrentValue / $normalizeBy); 
                        }
                    } // if AVG

                    // stuff it onto the array
                    $dataValues[] = $dataCurrentValue;

                } // if (!array_key_exists($values_column_name, $row)) 
            } // if ($values_column_name != NULL) 
        } // while

        $dataObject->setValues( $dataValues );
        $dataObject->setStartTs( $dataStartTs);

        // Prevent drilldown from this summarized data series
        // @refer html/gui/js/DrillDownMenu.js
        // this.groupByIdParam < -9999
        $dataSummaryGroupVal = -99999;
        $dataObject->setGroupId( $dataSummaryGroupVal );
        $dataObject->setGroupName( $dataSummaryGroupVal );

        return $dataObject;
    } // public function getSummarizedColumn

} // class SimpleTimeseriesDataset extends SimpleDataset
