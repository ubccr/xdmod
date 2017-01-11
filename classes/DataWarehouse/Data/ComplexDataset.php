<?php

namespace DataWarehouse\Data;

/**
 * Models a dataset comprised of multiple x and y axes.
 *
 * @author Amin Ghadersohi
 * @author Jeanette Sperhac
 */
class ComplexDataset
{
    protected $_dataDescripters = array();

    protected $_totalX;

    protected $_xAxisDataObject;

    public function __construct()
    {
        $this->_totalX = 1;
    }

    // --------------------------------------------------------------
    // init()
    // Instantiate and set up each Query, then instantiate
    // its Simple*Dataset and add it to $this->_dataDescripters.
    //
    // Does not compute combined x axis. @refer getXAxis().
    // Now general for Simple or SimpleTimeseries Dataset types.
    //
    // Annotated with JMS notes from 29 Apr 2014
    //
    //  @param $startDate -- format YYYY-MM-DD
    //  @param $endDate -- format YYYY-MM-DD
    //  @param $aggregationUnit -- (string) e.g. day, month, quarter, year
    //  @param $data_series -- array of stdClass Objects for the ComplexDataset
    //  @param $global_filters -- stdClass Object of arrays ??
    //  @param $query_type -- string containing 'aggregate' or 'timeseries'
    //
    //  @return array $globalFilterDescriptions -- Descriptions of the global filters
    //  @return array $yAxisArray  --  elided due to lack of interest, JMS
    //  @return array $metrics -- name (key) and description (value) of data metric
    //  @return array $dimensions -- name (key) and description (value) of data dimension
    //  @return array $dataSources -- text data source name (key)
    // --------------------------------------------------------------
    public function init(
        $startDate,
        $endDate,
        $aggregationUnit,
        $data_series,
        $global_filters,
        $query_type // new and semi improved...
    ) {
        // JMS: please improve this when possible.
        if (!in_array($query_type, array('aggregate','timeseries'))) {
            throw new \Exception(get_class($this)." unsupported query_type found: ".$query_type);
        }

        $globalFilterDescriptions = array();
        //$yAxisArray  = array();
        $metrics     = array();
        $dimensions  = array();
        $dataSources = array();

        foreach ($data_series as $data_description_index => $data_description) {
            // Determine query class, then instantiate it
            // this is quite horrible, and I apologize, but it beats 900 lines of
            // redundant code, no? --JMS
            $query_classname = '\\DataWarehouse\\Query\\' .
                $data_description->realm .  '\\' .
                ( $query_type == "aggregate" ? "Aggregate" : "Timeseries");

            try {
                $stat = $query_classname::getStatistic($data_description->metric);
                $metrics[$stat->getLabel(false)] = $stat->getInfo();
            } catch (\Exception $ex) {
                continue;
            }

            $query = new $query_classname(
                $aggregationUnit,
                $startDate,
                $endDate,
                null,
                null,
                array(),
                'tg_usage',
                array(),
                false
            );

            $dataSources[$query->getDataSource()] = 1;
            $group_by = $query->addGroupBy($data_description->group_by);
            $dimensions[$group_by->getLabel()] = $group_by->getInfo();
            $query->addStat($data_description->metric);

            if ($data_description->std_err == 1
                || (
                    property_exists($data_description, 'std_err_labels')
                    && $data_description->std_err_labels
                )
            ) {
                try {
                    $query->addStat('sem_'.$data_description->metric);
                } catch (\Exception $ex) {
                    $data_description->std_err = 0;
                    $data_description->std_err_labels = false;
                }
            }

            // set sort and order by for dataset
            $query = $this->setOrderBy($data_description, $query);

            // Role parameters: grouped with global filters.
            //      User-added filters added to chart
            //      implicit role-associated filters
            $groupedRoleParameters = $this->determineRoleParameters(
                $data_description,
                $global_filters
            );
            $query->setRoleParameters($groupedRoleParameters);

            $data_description->roleRestrictionsParameters = $query->setMultipleRoleParameters($data_description->authorizedRoles);
            $data_description->restrictedByRoles = $query->isLimitedByRoleRestrictions();

            $globalFilterDescriptions = array_merge(
                $globalFilterDescriptions,
                $query->roleParameterDescriptions
            );

            $query->setFilters($data_description->filters);

            // Create the Simple*Dataset; add to $this->_dataDescripters[]
            $this->addDataset($data_description, $query);
        } // foreach ($data_series as $data_description_index => $data_description)

        return array(
            $dimensions,
            $metrics,
            //$yAxisArray,
            $globalFilterDescriptions,
            $dataSources
        );
    } // function init()

    // --------------------------------------------------------------
    // addDataset()
    // Instantiate Simple*Dataset object and add it to $this->_dataDescripters.
    // Now general for Simple or SimpleTimeseries Dataset types.
    //
    // @param data_description
    // @param query object
    // --------------------------------------------------------------
    protected function addDataset($data_description, $query)
    {
        // what type is this query?
        $query_type = $query->getQueryType();

        $datasetClassname
            = $query_type == "aggregate"
            ? '\DataWarehouse\Data\SimpleDataset'
            : '\DataWarehouse\Data\SimpleTimeseriesDataset';

        // Create the resulting Simple*Dataset; add to $this->_dataDescripters[]
        $dataset = new $datasetClassname($query);

        $this->_dataDescripters[] = (object) array(
            'data_description' => $data_description,
            'dataset'          => $dataset,
        );
    } // function addDataset()

    // --------------------------------------------------------------
    // getTotalX()
    // Throws exception if _xAxisDataObject not set.
    // @returns $this->_totalX, total number of points on x axis
    // --------------------------------------------------------------
    public function getTotalX()
    {

        if (!isset($this->_xAxisDataObject)) {
            throw new \Exception(get_class($this)." _xAxisDataObject not set; _totalX=". $this->_totalX);
        }
        return $this->_totalX;
    } // function getTotalX()

    // --------------------------------------------------------------
    // getTotalDatasetCount()
    // Throws exception if _dataDescripters not set.
    // @returns total number of _dataDescripters (y datasets)
    // --------------------------------------------------------------
    public function getTotalDatasetCount()
    {

        // TODO JMS note: I may want to be able to return 0 here..
        if (empty($this->_dataDescripters)) {
            throw new \Exception(get_class($this)." _dataDescripters array is empty");
        }
        return count($this->_dataDescripters);
    } // function getTotalDatasetCount()

    // ---------------------------------------------
    // setOrderBy()
    // Set sort and order by for a single SimpleDataset.
    // These parameters are used to sort the store.
    // This is called by init() for the ComplexDataset.
    //
    // @return Query
    // ---------------------------------------------
    protected function setOrderBy(&$data_description, &$query)
    {
        $query->addOrderByAndSetSortInfo($data_description);
        return $query;
    } // function setOrderBy()

    // ---------------------------------------------------------------
    // determineRoleParameters()
    //
    // Set role parameters for a single SimpleDataset.
    //  Role parameters: grouped with global filters.
    //      User-added filters added to chart
    //      implicit role-associated filters
    // This is called by init() for the ComplexDataset.
    //
    // @return array
    // ---------------------------------------------------------------
    protected function determineRoleParameters(
        $data_description,
        $global_filters
    ) {
        $groupedRoleParameters = array();
        // set global filters for dataset
        if (!$data_description->ignore_global) {
            foreach ($global_filters->data as $global_filter) {
                if (isset($global_filter->checked)
                    && $global_filter->checked == 1
                ) {
                    if (!isset($groupedRoleParameters[
                            $global_filter->dimension_id
                        ])
                    ) {
                        $groupedRoleParameters[$global_filter->dimension_id]
                            = array();
                    }

                    $groupedRoleParameters[$global_filter->dimension_id][]
                        = $global_filter->value_id;
                }
            } // foreach ($global_filters...)
        }
        return $groupedRoleParameters;
    } // function determineRoleParameters()


    // --------------------------------------------------------------
    // getXAxis()
    //
    // Interleave the x axes for the multiple SimpleDatasets that
    // constitute this ComplexDataset. Assumes init() has been called.
    // Call this function prior to determining $this->_totalX.
    //
    //  @param $force_reexec (boolean) -- should query be re-executed?
    //  @param $limit (int) -- limit to count of records to return
    //  @param $offset (int) -- offset in count of records to return
    //
    //  @return SimpleData object
    // --------------------------------------------------------------
    public function getXAxis(
        $force_reexec = false,
        $limit = null,
        $offset = null
    ) {

        // init() should be called before this function.
        if (!isset($this->_dataDescripters)) {
            throw new \Exception(get_class($this)." _dataDescripters not set");
        }

        if (!isset($this->_xAxisDataObject) || $force_reexec === true) {
            $names = array();
            $tempXDataObject = array();
            $sort_type = 'none';

            foreach ($this->_dataDescripters as $dataDescripterAndDataset) {
                if ($dataDescripterAndDataset->data_description->sort_type
                    !== 'none'
                ) {
                    $sort_type = $dataDescripterAndDataset->data_description
                                                          ->sort_type;
                }

                $subXAxisObject
                    = $dataDescripterAndDataset->dataset->getColumn(
                        'dim_'
                        . $dataDescripterAndDataset->data_description->group_by
                    );

                $names[$subXAxisObject->getName()] = $subXAxisObject->getName();

                $yAxisDataObject
                    = $dataDescripterAndDataset->dataset->getColumn(
                        'met_'
                        . $dataDescripterAndDataset->data_description->metric
                    );

                // Ensure that all possible x values are reflected in the _xAxisDataObject
                foreach ($subXAxisObject->getValues() as $index => $label) {
                    // if we are missing this x value, add it to temp xAxisDataObject
                    if (!isset($tempXDataObject[$label])) {
                        $order = $subXAxisObject->getOrderId($index);
                        $value = $yAxisDataObject->getValue($index);

                        $tempXDataObject[$label] = array(
                            'label' => $label, // x value from x axis obj
                            'order' => $order, // order id from x axis obj
                            'value' => $value, // y value
                        );
                    } // if (!isset ... )
                } // foreach $subXAxisObject
            } // foreach ($this->_dataDescripters ... )

            // set the _xAxisDataObject. Since this is Aggregate data it is a dimension not a metric
            $this->_xAxisDataObject = new \DataWarehouse\Data\SimpleData('', 'dim');
            $this->_xAxisDataObject->setUnit('X Axis');

            // tempXDataObject contains properly sorted values.
            $sortedVals = $this->sortTempXArray($sort_type, $tempXDataObject);
            $this->_xAxisDataObject->setValues($sortedVals);

            // Determine total number of points on x axis:
            $this->_totalX = $this->_xAxisDataObject->getCount();

            // Assign name for x axis from the names of constituent datasets:
            $this->_xAxisDataObject->setName(implode(' / ', array_unique($names)));
        } // if (!isset($this->_xAxisDataObject) || $force_reexec === true)

        // Slice array according to supplied limit and offset, reset count:
        $this->_xAxisDataObject->setValues(array_slice(
            $this->_xAxisDataObject->getValues(),
            $offset,
            $limit
        ));
        $this->_xAxisDataObject->getCount(true);

        return $this->_xAxisDataObject;
    } // function getXAxis

    // --------------------------------------------------------------
    // sortTempXArray()
    // Given a sort type (value or label, asc or desc) and a temp data object
    // consisting of array of x values, order ids, and y values, return a
    // sorted array of x values.
    //
    // @return array
    // --------------------------------------------------------------
    protected function sortTempXArray($sort_type, &$tempXDataObject)
    {

        // arrays are keyed by x value, from x axis obj;
        $values = array(); // y values
        $orders = array(); // order id from x axis obj

        foreach ($tempXDataObject as $key => $vArray) {
            $values[$key] = $vArray['value'];
            $orders[$key] = $vArray['order'];
        }

        // sort the x axis values as specified
        switch ($sort_type) {
            case 'value_asc':
                array_multisort(
                    $values,
                    SORT_ASC,
                    $tempXDataObject
                );
                break;
            case 'value_desc':
                array_multisort(
                    $values,
                    SORT_DESC,
                    $tempXDataObject
                );
                break;
            case 'none':
            case 'label_asc':
                array_multisort(
                    $orders,
                    SORT_ASC,
                    $tempXDataObject
                );
                break;
            case 'label_desc':
                array_multisort(
                    $orders,
                    SORT_DESC,
                    $tempXDataObject
                );
                break;
        } // switch

        // now retrieve the x values reflecting the proper sort
        $labels = array();
        foreach ($tempXDataObject as $value) {
            $labels[] = $value['label'];
        }

        return $labels;
    } // function sortTempXArray()


    // --------------------------------------------------------------
    // getYAxis()
    //
    //    @return array
    //          $returnYAxis[$yAxisIndex] = $yAxisObject;
    //    containing yAxisObjects.
    //
    //  Each element has format:
    //              $yAxisObject->series[] = array(
    //                   'yAxisDataObject',      SimpleData
    //                   'data_description',     ...   ??
    //                   'decimals',             integer
    //                   'semDecimals',          integer
    //                   'filterParametersTitle' string??
    //                );
    //              $yAxisObject->decimals = 0;
    //              $yAxisObject->std_err = false;
    //              $yAxisObject->value_labels = false;
    //              $yAxisObject->log_scale = false;
    // --------------------------------------------------------------
    public function getYAxis(
        $limit = null,
        $offset = null,
        $shareYAxis = false
    ) {
        $this->getXAxis(true, $limit, $offset);

        $yAxisArray = array();

        // For each item on the _dataDescripters array, generate an axisId (name)
        // and push the dataset onto a yAxisArray. If shared y axis, push onto
        // subarray.
        foreach ($this->_dataDescripters as $data_description_index => $dataDescripterAndDataset) {
            $data_description = $dataDescripterAndDataset->data_description;

            $query_classname
                = '\\DataWarehouse\\Query\\'
                . $data_description->realm
                . '\\Aggregate';

            $stat = $query_classname::getStatistic($data_description->metric);

            if ($shareYAxis) {
                $axisId = 'sharedAxis';
            } else {
                $axisId
                    = $stat->getUnit()
                    . '_'
                    . $data_description->log_scale
                    . '_'
                    . ($data_description->combine_type == 'percent');
            }

            if (!isset($yAxisArray[$axisId])) {
                $yAxisArray[$axisId] = array();
            }

            $yAxisArray[$axisId][] = $dataDescripterAndDataset;
        } // foreach _dataDescripter ... => dataDescripterAndDataset

        $returnYAxis = array();
        foreach (array_values($yAxisArray) as $yAxisIndex => $yAxisDataDescriptions) {
            // build up a default class structure and accumulate values inside it:
            $yAxisObject = new \stdClass();
            $yAxisObject->series = array();
            $yAxisObject->decimals = 0;
            $yAxisObject->std_err = false;
            $yAxisObject->value_labels = false;
            $yAxisObject->log_scale = false;
            $yAxisObject->title = '';

            foreach ($yAxisDataDescriptions as $dataDescripterAndDataset) {
                $yAxisObject->title
                    = (
                        $dataDescripterAndDataset->data_description
                                                 ->combine_type == 'percent'
                        ? '% of '
                        : ''
                    )
                    . $dataDescripterAndDataset->dataset->getColumnUnit(
                        $dataDescripterAndDataset->data_description->metric,
                        false
                    );

                $statisticObject
                    = $dataDescripterAndDataset->dataset->_query->_stats[
                        $dataDescripterAndDataset->data_description->metric
                    ];

                $subXAxisObject
                    = $dataDescripterAndDataset->dataset->getColumn(
                        'dim_'
                        . $dataDescripterAndDataset->data_description->group_by,
                        $limit,
                        $offset
                    );

                $yAxisDataObject
                    = $dataDescripterAndDataset->dataset->getColumn(
                        'met_'
                        . $dataDescripterAndDataset->data_description->metric,
                        $limit,
                        $offset
                    );

                $filterParametersTitle
                    = $data_description->long_legend == 1
                    ? $dataDescripterAndDataset->dataset->_query
                                               ->getFilterParametersTitle()
                    : '';

                if ($filterParametersTitle != '') {
                    $filterParametersTitle
                        = ' {' . $filterParametersTitle . '}';
                }

                if ($this->_xAxisDataObject->getCount() <= 0) {
                    continue;
                }

                $newValues = array_fill(
                    0,
                    $this->_xAxisDataObject->getCount(),
                    null
                );

                $xIds = array_fill(
                    0,
                    $this->_xAxisDataObject->getCount(),
                    null
                );

                $xValues = array_fill(
                    0,
                    $this->_xAxisDataObject->getCount(),
                    null
                );

                if ($dataDescripterAndDataset->data_description->std_err) {
                    $newErrors = array_fill(
                        0,
                        $this->_xAxisDataObject->getCount(),
                        null
                    );
                }

                foreach ($subXAxisObject->getValues() as $xIndex => $xValue) {
                    $found = array_search(
                        $xValue,
                        $this->_xAxisDataObject->getValues(),
                        true
                    );

                    if ($found !== false) {
                        $newValues[$found] = $yAxisDataObject->getValue($xIndex);
                        $xIds[$found]      = $subXAxisObject->getId($xIndex);
                        $xValues[$found]   = $subXAxisObject->getValue($xIndex);

                        if ($dataDescripterAndDataset->data_description
                                                     ->std_err
                        ) {
                            $newErrors[$found]
                                = $yAxisDataObject->getError($xIndex);
                        }
                    }
                } // foreach ($subXAxisObject->getValues() ...

                $yAxisObject->std_err
                    = $dataDescripterAndDataset->data_description->std_err
                    || $yAxisObject->std_err;

                $yAxisObject->value_labels
                    = $dataDescripterAndDataset->data_description->value_labels
                    || $yAxisObject->value_labels;

                $yAxisObject->log_scale
                    = $dataDescripterAndDataset->data_description->log_scale
                    || $yAxisObject->log_scale;

                $decimals = $statisticObject->getDecimals();

                $yAxisObject->decimals = max($yAxisObject->decimals, $decimals);
                $yAxisDataObject->setValues($newValues);

                // following used only to id drilldown for pie charts. (!!)
                $yAxisDataObject->setXIds($xIds);
                $yAxisDataObject->setXValues($xValues);

                if ($dataDescripterAndDataset->data_description->std_err) {
                    //$yAxisDataObject->errors = $newErrors;
                    $yAxisDataObject->setErrors($newErrors);
                    $yAxisDataObject->getErrorCount(true);

                    $semStatisticObject
                        = $dataDescripterAndDataset->dataset->_query->_stats[
                            'sem_'
                            . $dataDescripterAndDataset->data_description
                                                       ->metric
                        ];

                    $semDecimals = $semStatisticObject->getDecimals();
                }

                $yAxisObject->series[] = array(
                    'yAxisDataObject'       => $yAxisDataObject,
                    'data_description'      => $dataDescripterAndDataset
                                               ->data_description,
                    'decimals'              => $decimals,
                    'semDecimals'           => isset($semDecimals)
                                             ? $semDecimals
                                             : 0,
                    'filterParametersTitle' => $filterParametersTitle
                );
            } // foreach ($yAxisDataDescriptions as $dataDescripterAndDataset) {

            $returnYAxis[$yAxisIndex] = $yAxisObject;
        } // foreach ( array_values($yAxisArray) ... => $yAxisDataDescriptions)

        return $returnYAxis;
    } // getYAxis()
} // class ComplexDataset
