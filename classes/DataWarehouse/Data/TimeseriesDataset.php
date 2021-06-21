<?php

namespace DataWarehouse\Data;

use CCR\DB;

use \DataWarehouse\Query\TimeseriesQuery;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\Schema;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\OrderBy;

/**
 * TimeseriesDataset class is used to generate one or more
 * data series from a query.
 */
class TimeseriesDataset
{
    /**
     * The summarized dataseries is assigned a dedicated group id value. This
     * is used by the frontend code in the Usage Tab to not generate a
     * drilldown tooltip @refer html/gui/js/DrillDownMenu.js The visualization
     * class also checks this to set the remainder flag on the dataset for the
     * metric explorer.
     */
    const SUMMARY_GROUP_ID = -99999;

    /**
     * @var TimeseriesQuery. The timeseries query instance that is used to generate the dataset.
     */
    protected $query;

    /**
     * @var AggregateQuery. The associated aggregate query used to generate the dataset.
     */
    protected $agg_query;

    /**
     * @var The number of series in the dataset.
     */
    protected $series_count = null;

    /**
     * @param TimeseriesQuery $query The timeseries query instance that is used to generate the dataset.
     */
    public function __construct(TimeseriesQuery $query)
    {
        $this->query = $query;
        $this->agg_query = $query->getAggregateQuery();

    }

    /**
     * Get the ordered list of data series identifiers based on the
     * aggregate query. This is used to order the datasets that are returned
     * from the timeseries query.
     * @param integer $limit  The number of data series ids to return.
     * @param integer $offset The start offset for the data series.
     * @return array
     */
    protected function getSeriesIds($limit, $offset)
    {
        $statement = $this->agg_query->getRawStatement($limit, $offset);
        $statement->execute();

        $groupInstance = reset($this->agg_query->getGroupBys());
        $groupIdColumn = $groupInstance->getId() . '_id';

        $seriesIds = array();

        while($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            $seriesIds[] = "${row[$groupIdColumn]}";
        }

        return $seriesIds;
    }

    /**
     * Get the time-based and space-based groupby class instances from the underlying
     * query class. Note this class only supports a single space-based group by
     * class per query.
     * @return array containing two elements: the time-based groupby and the space-based groupby.
     */
    protected function getGroupByClasses()
    {
        $timeGroup = null;
        $spaceGroup = null;

        foreach ($this->query->getGroupBys() as $name => $groupBy) {
            if ($name === $this->query->getAggregationUnit()->getUnitName()) {
                $timeGroup = $groupBy;
            } else {
                $spaceGroup = $groupBy;
            }
        }

        return array($timeGroup, $spaceGroup);
    }

    /**
     * Generate the datasets for a query. This executes the necessary queries
     * and returns the results as SimpleTimeseriesData instances.
     * @param integer $limit     The total number of series to return.
     * @param integer $offset    The offset of the first series.
     * @param boolean $summarize Whether to generate a summary data series also. If
     *                           summarize is true then $limit + 1 SimpleTimeseriesData
     *                           instances will be returned.
     * @return array of timeseries datasets. If the summarize flag is set true and there
     */
    public function getDatasets($limit, $offset, $summarize)
    {
        $summaryDataset = null;

        list($timeGroup, $spaceGroup) = $this->getGroupByClasses();

        $statObj = reset($this->query->getStats());
        $seriesIds = $this->getSeriesIds($limit, $offset);

        if (!empty($seriesIds)) {
            if ($summarize && $limit < $this->getUniqueCount()) {
                $summaryDataset = $this->getSummarizedColumn(
                    $statObj->getId(),
                    $spaceGroup->getId(),
                    $this->getUniqueCount() - $limit,
                    $seriesIds
                );
            }

            $this->query->addWhereAndJoin($spaceGroup->getId(), 'IN', $seriesIds);
        } else {
            // this happens when the offset is greater than the number of series. This
            // can occur when muliple datasets with different numbers of series
            // are plotted on the same chart.
            return array();
        }

        $statement = $this->query->getRawStatement();
        $statement->execute();

        $columnTypes = array();
        for ($end = $statement->columnCount(), $i = 0; $i < $end; $i++) {
             $raw_meta = $statement->getColumnMeta($i);
             $columnTypes[$raw_meta['name']] = $raw_meta;
        }

        $dataSets = array();
        foreach ($seriesIds as $seriesId) {
            $dataSets[$seriesId] = null;
        }

        while($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

            $seriesId = $row[$spaceGroup->getId() . '_id'];
            $dimension = $row[$spaceGroup->getId() . '_name'];

            $dataSet = $dataSets[$seriesId];
            if ($dataSet === null) {
                $dataSet = $dataSets[$seriesId] = new SimpleTimeseriesData($dimension);

                $dataSet->setUnit($statObj->getName()); // <- check this is correct
                $dataSet->setStatistic($statObj);
                $dataSet->setGroupName($dimension);
                $dataSet->setGroupId($row[$spaceGroup->getId() . '_id']); // <- check this is correct
            }

            $value_col = $statObj->getId();

            $start_ts  = $row[$timeGroup->getId() . '_start_ts'];
            $value = SimpleDataset::convertSQLtoPHP(
                $row[$value_col],
                $columnTypes[$value_col]['native_type'],
                $columnTypes[$value_col]['precision']
            );

            $error = null;
            $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic($value_col);

            if (isset($this->query->_stats[$semStatId])) {
                $error = SimpleDataset::convertSQLtoPHP(
                    $row[$semStatId],
                    $columnTypes[$semStatId]['native_type'],
                    $columnTypes[$semStatId]['precision']
                );
            }

            $dataSet->addDatum($start_ts, $value, $error);
        }

        $retVal = array_values($dataSets);

        if ($summaryDataset !== null) {
            $retVal[] = $summaryDataset;
        }

        return $retVal;
    }

    /**
     * The choice of summary algorithm is determined based on the alias name
     * for the statistic.
     * @param string  $column_name The alias of the statistic to be summarized.
     * @param integer $normalizeBy The total number of series to be summarized.
     * @return array the sql fragment, series name and summariation algorthm type.
     */
    protected function getSummaryOp($column_name, $normalizeBy)
    {
        $series_name = "All $normalizeBy Others";
        $sql = "SUM(t.$column_name)";
        $type = 'sum';

        if (strpos($column_name, 'min_') !== false)
        {
            $series_name = "Minimum over all $normalizeBy others";
            $sql = "MIN(t.$column_name)";
            $type = 'min';
        }
        elseif (strpos($column_name, 'max_') !== false)
        {
            $series_name = "Maximum over all $normalizeBy others";
            $sql = "MAX(t.$column_name)";
            $type = 'max';
        }
        elseif (strpos($column_name, 'avg_') !== false
            || strpos($column_name, 'count') !== false
            || strpos($column_name, 'utilization') !== false
            || strpos($column_name, 'rate') !== false
            || strpos($column_name, 'expansion_factor') !== false)
        {
            $series_name = "Avg of $normalizeBy Others";
            $sql = "SUM(t.$column_name)";
            $type = 'avg';
        }

        return array($sql, $series_name, $type);
    }

    /**
     * Generate the summary dataset for a query.
     *
     * @param string  $column_name       The sql alias for the statistic to summarize.
     * @param string  $where_name        The id of the GroupBy class that is being used.
     * @param integer $normalizeBy       The total number of distinct groups in the data.
     * @param array   $whereExcludeArray Array of values to exclude from the summary calculation.
     * @return SimpleDataset
     */
    protected function getSummarizedColumn(
        $column_name,
        $where_name,
        $normalizeBy,
        array $whereExcludeArray
    ) {
        // determine the selected time aggregation unit
        $aggunit_name = $this->query->getAggregationUnit()->getUnitName();

        // assign column names for returned data:
        $start_ts_column_name  = $aggunit_name . '_start_ts';

        $query = clone $this->query;

        // group on the where clause column, which will be enforced after time agg. unit
        $query->addGroupBy($where_name);
        $query->addWhereAndJoin($where_name, "NOT IN", $whereExcludeArray);

        list($sql, $series_name, $type) = $this->getSummaryOp($column_name, $normalizeBy);

        $dataObject = new \DataWarehouse\Data\SimpleTimeseriesData($series_name);
        $dataObject->setStatistic($query->_stats[$column_name]);
        $dataObject->setUnit($query->_stats[$column_name]->getName());
        $dataObject->setGroupId(self::SUMMARY_GROUP_ID);
        $dataObject->setGroupName($series_name);

        $query_string = "SELECT t.$start_ts_column_name AS $start_ts_column_name,
                                $sql AS $column_name "
                        . " FROM ( "
                        .   $query->getQueryString()
                        . " ) t "
                        . " GROUP BY t.$start_ts_column_name";

        $statement = DB::factory($query->_db_profile)->query($query_string, array(), true);
        $statement->execute();

        $columnTypes = array();
        for ($end = $statement->columnCount(), $i = 0; $i < $end; $i++) {
            $raw_meta = $statement->getColumnMeta($i);
            $columnTypes[$raw_meta['name']] = $raw_meta;
        }

        while ( $row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            $start_ts  = $row[$start_ts_column_name];
            $value = SimpleDataset::convertSQLtoPHP(
                $row[$column_name],
                $columnTypes[$column_name]['native_type'],
                $columnTypes[$column_name]['precision']
            );
            if ($type === 'avg') {
                $value = $value / $normalizeBy;
            }
            $dataObject->addDatum($start_ts, $value, null);
        }

        return $dataObject;
    }

    /**
     * Build a SimpleTimeseriesData object containing the timestamps for
     * the time range of the query.
    * @return SimpleTimeseriesData
    */
    public function getTimestamps()
    {
        $dataStartTs = array();

        foreach ($this->query->getTimestamps() as $raw_timetamp) {
            $dataStartTs[] = $raw_timetamp['start_ts'];
        }

        $column_name = $this->query->getAggregationUnit()->getUnitName();

        $timestampsDataObject = new \DataWarehouse\Data\SimpleTimeseriesData(
            $this->query->_group_bys[$column_name]->getId()
        );

        $timestampsDataObject->setStartTs($dataStartTs);

        return $timestampsDataObject;
    }

    /**
     * Get the total number of series in the dataset. The count is determined from the
     * aggregate version of the supplied timeseries query.
     * @return the number of data series in this dataset.
     */
    public function getUniqueCount()
    {
        if ($this->series_count === null) {
            $this->series_count = $this->agg_query->getCount();
        }
        return $this->series_count;
    }

    /**
     * Implementation notes: This function is very similar to the corresponding
     * one in SimpleDataset. However it is different enough to warrant its own
     * implementation. Note also that even though it appears that this
     * code is generating output that is fed into ExtJS infact it gets heavily
     * manipulated in the DataWarehouse/Access/Usage class before heading
     * out on its merry way over the network.
     *
     * Note the limit and offset parameters are ignored by this function and
     * only exist for API compatibility.
     */
    public function exportJsonStore($limit = null, $offset = null)
    {
        list($timeGroup, $spaceGroup) = $this->getGroupByClasses();
        $stat = reset($this->query->getStats());

        $fields = array(
            array('name' => $timeGroup->getId(), 'type' => 'string', 'sortDir' => 'DESC'),
            array('name' => $spaceGroup->getId(), 'type' => 'string', 'sortDir' => 'DESC'),
            array('name' => $stat->getId(), 'type' => 'float', 'sortDir' => 'DESC')
        );

        $stat_unit = $stat->getUnit();
        $data_unit = '';
        if (substr($stat_unit, -1) == '%') {
            $data_unit = '%';
        }

        $stat_header = $stat->getName();
        if ($stat_header !== $stat_unit
            && strpos($stat_header, $stat_unit) === false
        ) {
            $stat_header .= ' (' . $stat_unit . ')';
        }

        $columns = array(
            array(
                'header' => $timeGroup->getName(),
                'width' => 150,
                'dataIndex' => $timeGroup->getId(),
                'sortable' => true,
                'editable' => false,
                'locked' => true
            ),
            array(
                'header' => $spaceGroup->getId() === 'none' ? 'Source' : $spaceGroup->getName(),
                'width' => 150,
                'dataIndex' => $spaceGroup->getId(),
                'sortable' => true,
                'editable' => false,
                'locked' => true
            ),
            array(
                'header'    => $stat_header,
                'width'     => 140,
                'dataIndex' => $stat->getId(),
                'sortable'  => true,
                'editable'  => false,
                'align'     => 'right',
                'xtype'     => 'numbercolumn',
                'format'    => ($stat->getPrecision() > 0 ? '0,000.' . str_repeat(0, $stat->getPrecision()) : '0,000') . $data_unit,
            )
        );

        $statement = $this->query->getRawStatement();
        $statement->execute();
        $records = array();
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            $records[] = array(
                $timeGroup->getId() => $row[$timeGroup->getId() . '_name'],
                $stat->getId() => $row[$stat->getId()],
                $spaceGroup->getId() => $row[$spaceGroup->getId() . '_name']
            );
        }

        $message = '';

        if (empty($records)) {
            $message = 'Dataset is empty';
            $fields = array(array("name" => 'Message', "type" => 'string'));
            $records = array(array('Message' => $message));
            $columns = array(array(
                "header"    => 'Message',
                "width"     => 600,
                "dataIndex" => 'Message',
                "sortable"  => false,
                'editable'  => false,
                'align'     => 'left',
                'renderer'  => "CCR.xdmod.ui.stringRenderer",
            ));
        }

        return array(
            'metaData' => array(
                'totalProperty'   => 'total',
                'messageProperty' => 'message',
                'root'            => 'records',
                'id'              => 'id',
                'fields'          => $fields
            ),
            'message' => '<ul>' . $message . '</ul>',
            'success' => true,
            'total'   => count($records),
            'records' => $records,
            'columns' => $columns
        );
    }

    /**
     * @see SimpleDataset::export
     */
    public function export($export_title = 'title')
    {
        $exportData = array(
            'title' => array(
                'title' => $export_title
            ),
            'title2' => array(
                'parameters' => $this->query->roleParameterDescriptions
            ),
            'duration' => array(
                'start' => $this->query->getStartDate(),
                'end'   => $this->query->getEndDate(),
            ),
            'headers' => array(),
            'rows' => array()
        );

        list($timeGroup, $spaceGroup) = $this->getGroupByClasses();

        $exportData['headers'][] = $timeGroup->getName();

        $stat = reset($this->query->getStats());
        $stat_unit  = $stat->getUnit();

        $seriesName = $stat->getName();
        if ( $seriesName != $stat_unit && strpos($seriesName, $stat_unit) === false) {
            $seriesName .= ' (' . $stat_unit . ')';
        }
        if (count($this->query->filterParameterDescriptions) > 0) {
            $seriesName .= ' {' . implode(', ', $this->query->filterParameterDescriptions) . '}';
        }

        $dimensions = $this->getSeriesIds(null, null);

        $dimensionNames = array();
        $timeData = array();
        $timestamps = array();

        $statement = $this->query->getRawStatement();
        $statement->execute();
        while($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {

            $dimension = $row[$spaceGroup->getId() . '_id'];

            if (!isset($dimensionNames[$dimension])) {
                $dimensionNames[$dimension] = $row[$spaceGroup->getId() . '_name'];
            }

            $timeTs = $row[$timeGroup->getId() . '_start_ts'];

            if (!isset($timestamps[$timeTs]) ) {
                $timestamps[$timeTs] = $row[$timeGroup->getId() . '_name'];
                $timeData[$timeTs] = array();
            }

            $timeData[$timeTs][$dimension] = $row[$stat->getId()];
        }

        // Build header
        foreach ($dimensions as $dimension) {
            $exportData['headers'][] = "[{$dimensionNames[$dimension]}] $seriesName";
        }

        // Data are returned in time order, but every dimension may not have all timestamps
        // so the timestamps array may not be in time order
        ksort($timestamps);

        foreach ($timestamps as $timeTs => $timeName) {
            $values = array($timeName);

            foreach ($dimensions as $dimension) {
                if (isset($timeData[$timeTs][$dimension])) {
                    $values[] = $timeData[$timeTs][$dimension];
                } else {
                    $values[] = 0;
                }
            }
            $exportData['rows'][] = $values;
        }

        return $exportData;
    }
}
