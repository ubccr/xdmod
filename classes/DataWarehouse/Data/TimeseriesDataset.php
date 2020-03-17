<?php

namespace DataWarehouse\Data;

use CCR\DB;

use \DataWarehouse\Query\TimeseriesQuery;

use \DataWarehouse\Query\Model\Table;
use \DataWarehouse\Query\Model\TableField;
use \DataWarehouse\Query\Model\Schema;
use \DataWarehouse\Query\Model\WhereCondition;
use \DataWarehouse\Query\Model\OrderBy;

class TimeseriesDataset
{
    // The summarized dataseries is assigned a dedicated group id value. This
    // is used by the frontend code in the Usage Tab to not generate a
    // drilldown tooltip @refer html/gui/js/DrillDownMenu.js The visualization
    // class also checks this to set the remainder flag on the dataset for hte
    // metric explorer.
    const SUMMARY_GROUP_ID = -99999;

    protected $query;
    protected $agg_query;

    protected $series_count = null;

    public function __construct(TimeseriesQuery $query)
    {
        $this->query = $query;
        $this->agg_query = $query->getAggregateQuery();

    }

    /**
     * Get the ordered list of data series identifiers based on the
     * aggregate query. This is used to order the datasets that are returned
     * from the timeseries query.
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
     * return an array of timeseries datasets. If the summarize flag is set true and there
     * are more data series that the $limit then $limit + 1 datasets will be returned with
     * the last one being the summarized version of the remainder.
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
    * Build a SimpleTimeseriesData object containing the timeseries data.
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
     * Returns the number of data series in this dataset. The count is determined from the
     * aggregate version of the supplied timeseries query.
     */
    public function getUniqueCount()
    {
        if ($this->series_count === null) {
            $this->series_count = $this->agg_query->getCount();
        }
        return $this->series_count;
    }

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
