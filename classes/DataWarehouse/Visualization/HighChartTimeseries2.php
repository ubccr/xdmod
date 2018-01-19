<?php
namespace DataWarehouse\Visualization;

use DataWarehouse;

/*
*
* @author Amin Ghadersohi
* @author Jeanette Sperhac
* @date 2012-05-03
* @date 2015-07-30
*
* It is traditional for the author to magnanimously accept the blame for whatever
* deficiencies remain. I don’t. Any errors, deficiencies, or problems in this code are
* somebody else’s fault, but I would appreciate knowing about them so as to determine
* who is to blame.
*
* TODO: It would be great to factor ComplexDataset-handling code out of configure()
* and instead extend ComplexDataset class for timeseries.
* Or static-ize some of the general functions that are part of ComplexDataset and
* use them here in configure()
*
*/
class HighChartTimeseries2 extends HighChart2
{
    // ---------------------------------------------------------
    // __construct()
    //
    // Constructor for HighChart2Timeseries class.
    //
    // note that showContextMenu has default false in HC2T class.
    // ---------------------------------------------------------
    public function __construct(
        $aggregation_unit,
        $start_date,
        $end_date,
        $scale,
        $width,
        $height,
        $swap_xy = false,
        $showContextMenu = false,
        $share_y_axis = false,
        $hide_tooltip = false,
        $min_aggregation_unit = null,
        $showWarnings = true
    ) {

        parent::__construct(
            $aggregation_unit,
            $start_date,
            $end_date,
            $scale,
            $width,
            $height,
            $swap_xy,
            $showContextMenu,
            $share_y_axis,
            $hide_tooltip,
            $min_aggregation_unit,
            $showWarnings
        );

        $this->_queryType = 'timeseries';
    }

    //-------------------------------------------------
    // useMean( $stat )
    // Based on inspection of the Statistic Alias for the dataset,
    // should the mean of the values be computed for the remainder set?
    //
    // @param is $dataObj->getStatistic()->getAlias(); or $data_description->metric
    //-------------------------------------------------
    private function useMean($stat) {
            $retVal
                = strpos($stat, 'avg_') !== false
                || strpos($stat, 'count') !== false
                || strpos($stat, 'utilization') !== false
                || strpos($stat, 'rate') !== false
                || strpos($stat, 'expansion_factor') !== false;
            return $retVal;
    }

    //-------------------------------------------------
    // getDataname( $stat, $limit )
    //
    // Based on inspection of the Statistic Alias for the dataset,
    // what operation should be reported for the remainder set?
    //
    // @param is $dataObj->getStatistic()->getAlias(); or $data_description->metric
    // @param is number of full datasets to represent in chart
    //-------------------------------------------------
    private function getDataname($stat, $limit) {

            // use statistics alias for object
            //$stat = $dataObj->getStatistic()->getAlias(); or $data_description->metric
            $useMean = $this->useMean($stat );

            $isMin = strpos($stat, 'min_') !== false ;
            $isMax = strpos($stat, 'max_') !== false ;

            // Determine label for the summary dataset
            $dataname
                = ($useMean ? 'Avg of ' : 'All ')
                . ($this->_total - $limit)
                . ' Others';

        if ($isMin) {
            $dataname
            = 'Minimum over all '
            . ($this->_total - $limit)
            . ' others';
        } elseif ($isMax) {
            $dataname
            = 'Maximum over all '
            . ($this->_total - $limit)
            . ' others';
        } // if $isMin

            return $dataname;
    }

    // ---------------------------------------------------------
    // configure()
    //
    // Given chart data series and parameters, build
    // SimpleTimeseriesDataset, set all needed chart parameters.
    //
    // ---------------------------------------------------------
    public function configure(
        &$data_series,
        &$x_axis,
        &$y_axis,
        &$legend,
        &$show_filters,
        &$global_filters,
        $font_size,
        $limit = null,
        $offset = null,
        $summarizeDataseries = false
    ) {   // JMS: clearly we do not have enough parameters.
                                        // support min/max/average 'truncation' of dataset


        $this->show_filters = $show_filters;

        // Instantiate the color generator:
        $colorGenerator = new \DataWarehouse\Visualization\ColorGenerator();

        $dataSeriesCount  = count($data_series);
        $dataSources = array();

        // ---- set up yAxisArray ----

        // prepare yAxisArray for each yAxis we will plot:
        $yAxisArray = array();
        $multiCategory = false;
        foreach($data_series as $data_description_index => $data_description)
        {
            // set multiCategory true or false
            $category = DataWarehouse::getCategoryForRealm($data_description->realm);
            if(isset($prevCategory) && $category != $prevCategory)
            {
                $multiCategory = true;
            } else {
                $prevCategory = $category;
            }

            // Determine statistic name. In this case use the Aggregate classname to get the stat.
            // (Speculative). August 2015
            $query_classname = '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Aggregate';
            try
            {
                $stat = $query_classname::getStatistic($data_description->metric);
            }
            catch(\Exception $ex)
            {
                continue;
            }
            $this->_chart['metrics'][$stat->getLabel(false)] = $stat->getInfo();

            // determine axisId
            if($this->_shareYAxis)
            {
                $axisId = 'sharedAxis';
            } else {
                if($this->_hasLegend && $dataSeriesCount > 1) {
                    $axisId = $stat->getUnit().'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
                } else {
                    $axisId = $data_description->realm.'_'.$data_description->metric.'_'.
                                $data_description->log_scale.'_'.($data_description->combine_type == 'percent');
                }
            }

            if(!isset($yAxisArray[$axisId]))
            {
                $yAxisArray[$axisId] = array();
            }

            $yAxisArray[$axisId][] = $data_description;
        } // foreach($data_series ...

        $yAxisCount = count($yAxisArray);
        $results = array();

        $globalFilterDescriptions = array();

        // ==== Big long effing loop ====
        // --- Walk through y axes and do some setup ---
        foreach(array_values($yAxisArray) as $yAxisIndex => $yAxisDataDescriptions)
        {
            $yAxis = null;

            // === mind you, this is also a big long loop ===
            foreach($yAxisDataDescriptions as $data_description_index => $data_description)
            {
                //if($data_description->display_type == 'pie') $data_description->display_type = 'line';
                $query_classname = '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Timeseries';

                $query = new $query_classname(
                    $this->_aggregationUnit,
                    $this->_startDate,
                    $this->_endDate,
                    null,
                    null,
                    array(),
                    'tg_usage',
                    array(),
                    false
                );

                // @refer ComplexDataset::determineRoleParameters()
                // why this is done twice is a complete mystery to me...
                $groupedRoleParameters = array();
                if(!$data_description->ignore_global)
                {
                    foreach($global_filters->data as $global_filter)
                    {
                        if(isset($global_filter->checked) && $global_filter->checked == 1)
                        {
                            if(!isset($groupedRoleParameters[$global_filter->dimension_id]))
                            {
                                $groupedRoleParameters[$global_filter->dimension_id] = array();
                            }
                            $groupedRoleParameters[$global_filter->dimension_id][] = $global_filter->value_id;
                        }
                    }
                }
                $query->setRoleParameters($groupedRoleParameters);

                $data_description->roleRestrictionsParameters = $query->setMultipleRoleParameters($data_description->authorizedRoles);
                $data_description->restrictedByRoles = $query->isLimitedByRoleRestrictions();

                $globalFilterDescriptions = array_merge(
                    $globalFilterDescriptions,
                    $query->roleParameterDescriptions
                );

                $query->setFilters($data_description->filters);

                // TODO JMS: special case for Timeseries. Handle after init()
                if($data_description->filters->total === 1 && $data_description->group_by === 'none')
                {
                    $data_description->group_by = $data_description->filters->data[0]->dimension_id;
                }
                // end TODO JMS: special case for Timeseries. Handle after init()

                // @refer ComplexDataset::init()
                $dataSources[$query->getDataSource()] = 1;
                $group_by = $query->addGroupBy($data_description->group_by);
                $this->_chart['dimensions'][$group_by->getLabel()] = $group_by->getInfo();
                $query->addStat($data_description->metric);
                if (
                    $data_description->std_err == 1
                    || (
                        property_exists($data_description, 'std_err_labels')
                        && $data_description->std_err_labels
                    )
                ) {
                    try
                    {
                         $query->addStat('sem_'.$data_description->metric);
                    }
                    catch(\Exception $ex)
                    {
                        $data_description->std_err = 0;
                        $data_description->std_err_labels = false;
                    }
                } // if($data_description->std_err == 1)

                $query->addOrderByAndSetSortInfo($data_description);

                // JMS:
                // and here we instantiate the dataset.
                // Note that while this var name is a ComplexDataset in the parent class
                // the item it contains is a Simple*Dataset.
                // so when we iterate over datasets in parent class we are fiddling with SImple*Datsets

                $dataset = new \DataWarehouse\Data\SimpleTimeseriesDataset($query);

                // JMS: to here we have the ComplexDataset::init() function

                $statisticObject = $query->_stats[$data_description->metric];
                $decimals = $statisticObject->getDecimals();

                $defaultYAxisLabel = 'yAxis'.$yAxisIndex;
                $yAxisLabel = ($data_description->combine_type=='percent'? '% of ':'').(
                                ($this->_hasLegend && $dataSeriesCount > 1)
                                    ? $dataset->getColumnUnit($data_description->metric, false)
                                    : $dataset->getColumnLabel($data_description->metric, false)
                                );
                if($this->_shareYAxis)
                {
                    $yAxisLabel = $defaultYAxisLabel;
                }
                $originalYAxisLabel = $yAxisLabel;
                $yAxisMin = $data_description->log_scale?null:0;
                $yAxisMax = null;
                $config = $this->getAxisOverrides($y_axis, $yAxisLabel, $yAxisIndex);
                if($config !== null)
                {
                    if(isset($config->title))
                    {
                        $yAxisLabel = $config->title;
                    }
                    if(isset($config->min))
                    {
                        $yAxisMin = $data_description->log_scale && $config->min <= 0?null:$config->min;
                    }
                    if(isset($config->max))
                    {
                        $yAxisMax = $config->max;
                    }
                }
                if($yAxisLabel == $defaultYAxisLabel)
                {
                    $yAxisLabel = '';
                }

                if($yAxis == null)
                {
                    // set initial dataset's plot color:
                    $yAxisColorValue = ($data_description->color == 'auto' || $data_description->color == 'FFFFFF')
                                ? $colorGenerator->getColor()
                                : $colorGenerator->getConfigColor(hexdec($data_description->color) );
                    $yAxisColor = '#'.str_pad(dechex($yAxisColorValue), 6, '0', STR_PAD_LEFT);
                    $yAxisColorUsedBySeries = false;

                    $yAxis = array(
                        'title' => array(
                            'text' => $yAxisLabel,
                            'style' => array(
                                'color'=> $yAxisColor,
                                'fontWeight'=> 'bold',
                                'fontSize' => (12 + $font_size).'px'
                            )
                        ),
                        'otitle' => $originalYAxisLabel,
                        'dtitle' => $defaultYAxisLabel,
                        'labels' =>
                        array(
                            'style' => array(
                                'fontWeight'=> 'normal',
                                'fontSize' => (11 + $font_size).'px'
                            )
                        ),
                        'opposite' => $yAxisIndex % 2 == 1,
                        'min' => $yAxisMin,
                        'max' => $yAxisMax,
                        'startOnTick' => $yAxisMin == null,
                        'endOnTick' => $yAxisMax == null,
                        'type' => $data_description->log_scale? 'logarithmic' : 'linear',
                        'showLastLabel' =>  $this->_chart['title']['text'] != '',
                        'gridLineWidth' => $yAxisCount > 1 ?0: 1 + ($font_size/8),
                        'lineWidth' => 2 + $font_size/4,
                        'allowDecimals' => $decimals > 0,
                        'tickInterval' => $data_description->log_scale ?1:null,
                        'maxPadding' =>  max(0.05, ($data_description->value_labels?0.25:0) + ($data_description->std_err?.25:0))
                    );

                    $this->_chart['yAxis'][] = $yAxis;
                } // if($yAxis == null)

                $xAxisData = $dataset->getTimestamps();

                $start_ts_array = array();
                foreach($xAxisData->getStartTs() as $st)
                {
                    $start_ts_array[] = $st*1000;
                }
                $pointInterval = $this->getPointInterval();

                // set x axis if needed
                if(!isset($xAxis))
                {
                    $defaultXAxisLabel = 'xAxis';
                    $xAxisLabel = $defaultXAxisLabel;
                    $originalXAxisLabel = $xAxisLabel;

                    if(isset($x_axis->{$xAxisLabel}))
                    {
                        $config = $x_axis->{$xAxisLabel};
                        if(isset($config->title))
                        {
                            $xAxisLabel = $config->title;
                        }
                    }
                    if($xAxisLabel == $defaultXAxisLabel)
                    {
                        $xAxisLabel = '';
                    }
                    $xAxisLabelFormat = '{value:'.$this->getDateFormat().'}';
                    $start_ts = strtotime($this->_startDate)*1000;
                    $end_ts = strtotime($this->_endDate)*1000;
                    $expectedDataPointCount = ($end_ts - $start_ts) / $pointInterval;

                    $xAxis = array(
                        'type' => 'datetime',
                        'tickLength' => 0,
                        'title' => array(
                            'text' => $xAxisLabel,
                            'style' => array(
                                'color'=> '#000000',
                                'fontWeight'=> 'bold',
                                'fontSize' => (12 + $font_size).'px'
                            )
                        ),
                        'otitle' => $originalXAxisLabel,
                        'dtitle' => $defaultXAxisLabel,
                        'labels' => $this->_swapXY ? array(
                            'enabled' => true,
                            'staggerLines' => 1,
                            //'step' => round( ( $font_size < 0 ? 0 : $font_size + 5 ) / 11 ),
                            'format' => $xAxisLabelFormat,
                            'style' => array(
                                'fontWeight'=> 'normal',
                                'fontSize' => (11 + $font_size).'px',
                                'marginTop' => $font_size * 2
                            )
                        )
                        : array(
                            'enabled' => true,
                            'staggerLines' => 1,
                            //'overflow' => 'justify',
                            //'step' => ceil(($font_size<0?0:$font_size+11  ) / 11),
                            'rotation' => $xAxisData->getName() != 'Year'  && $expectedDataPointCount > 25 ? -90 : 0,
                            'format' => $xAxisLabelFormat,
                            'style' => array(
                                'fontSize' => (8 + $font_size).'px',
                                'marginTop' => $font_size * 2
                            )
                        ),
                        'minTickInterval' => $pointInterval,
                        'minRange' => $pointInterval,
                        'lineWidth' => 2 + $font_size / 4
                    );
                     $this->_chart['xAxis'] = $xAxis;
                } // if(!isset($xAxis))

                //  ----------- set up yAxis, assign to chart ... eventually -----------

                if($data_description->std_err == 1)
                {
                    $semStatisticObject = $query->_stats['sem_'.$data_description->metric];
                    $semDecimals = $semStatisticObject->getDecimals();
                }

                // get the full dataset count: how many unique values in the dimension we group by?
                $datagroupFullCount = $dataset->getUniqueCount(
                    $data_description->group_by,
                    $data_description->realm
                );
                $this->_total = max($this->_total, $datagroupFullCount);

                // If summarizeDataseries (Usage), use $limit to minimize the number of sorted
                // queries we must execute. If ME, use the $limit (default 10) to enable paging
                // thru dataset.

                // Query for the top $limit categories in the realm, over the selected time period
                //$datagroupDataObject = $dataset->getColumnSortedMax($data_description->metric,
                //                                                        'dim_'.$data_description->group_by,
                //                                                        $limit,
                //                                                        $offset,
                //                                                        $data_description->realm);
                // Roll back to the 'old way' of getting top-n sorted dimension values:
                $datagroupDataObject = $dataset->getColumnUniqueOrdered(
                    'dim_'.$data_description->group_by,
                    $limit,
                    $offset,
                    $data_description->realm
                );

                // Use the top $limit categories to build an iterator with $limit objects inside:
                $yAxisDataObjectsIterator = $dataset->getColumnIteratorBy(
                    'met_'.$data_description->metric,
                    $datagroupDataObject
                );

                // --- Set up dataset truncation for Usage tab support: ----
                // Populate an array with our iterator contents, but only up to $limit.
                // (implicitly) run the queries and populate the array, but only up to $limit:
                // that is taken care of by the limit on datagroupDataObject above.
                $yAxisDataObjectsArray = array();
                foreach($yAxisDataObjectsIterator as $yAxisDataObject)
                {
                    $yAxisDataObjectsArray[] = $yAxisDataObject;
                }

                $dataTruncated = false;
                if ( $summarizeDataseries && $this->_total > $limit )
                {
                    // Run one more query containing everything that was NOT in the top n.
                    // Populate SimpleTimeseriesData object yAxisTruncateObj with this
                    // summary information
                    $yAxisTruncateObj = $dataset->getSummarizedColumn(
                        $data_description->metric,
                        $data_description->group_by,
                        $this->_total - $limit,
                        $yAxisDataObjectsIterator->getLimitIds(),
                        $data_description->realm
                    );

                    // set the remainder dataset label for plotting
                    $dataname = $this->getDataname($data_description->metric, $limit );
                    $yAxisTruncateObj->setGroupName($dataname );
                    // set label on the dataseries' legend
                    $yAxisTruncateObj->setName($dataname );

                    $yAxisDataObjectsArray[] = $yAxisTruncateObj;
                    $dataTruncated = true;
                }
                unset($yAxisDataObjectsIterator);

                // operate on each yAxisDataObject, a SimpleTimeseriesData object
                // @refer HighChart2 line 866

                $numYAxisDataObjects = count($yAxisDataObjectsArray);
                foreach($yAxisDataObjectsArray as $yIndex => $yAxisDataObject)
                {
                    if( $yAxisDataObject != null)
                    {
                        $yAxisDataObject->joinTo($xAxisData, null);

                        // If the first data set in the series, pick up the yAxisColorValue.
                        if(!$yAxisColorUsedBySeries)
                        {
                            $color_value = $yAxisColorValue;
                            $yAxisColorUsedBySeries = true;
                        } else {
                            $color_value = $colorGenerator->getColor();
                        }

                        $color = '#'.str_pad(dechex($color_value), 6, '0', STR_PAD_LEFT);
                        $lineColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color_value, -70)), 6, '0', STR_PAD_LEFT);

                        //highcharts chokes on datasets that are all null so detect them and replace
                        // all with zero. this will give the user the right impression. (hopefully)
                        $all_null = true;
                        foreach($yAxisDataObject->getValues() as $value)
                        {
                            if($value != null)
                            {
                                $all_null = false;
                                break;
                            }
                        }
                        if($all_null)
                        {
                            continue;
                        }

                        $values = $yAxisDataObject->getValues();
                        $values_count = count($values);

                        $isRemainder = $dataTruncated && ($yIndex === $numYAxisDataObjects - 1);

                        $filterParametersTitle = $data_description->long_legend == 1?$query->getFilterParametersTitle():'';
                        if($filterParametersTitle != '')
                        {
                            $filterParametersTitle = ' {'.$filterParametersTitle.'}' ;
                        }

                        // --- set up $dataLabelsConfig, $seriesValues, $tooltipConfig ---
                        $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;
                        $dataLabelsConfig = array(
                            'enabled' => $data_description->value_labels || $std_err_labels_enabled,
                            'settings' => array(
                                'value_labels' => $data_description->value_labels,
                                'error_labels' => $std_err_labels_enabled,
                                'decimals' => $decimals
                            ),
                            'style' => array(
                                'fontSize' => (11 + $font_size).'px',
                                'fontWeight'=> 'normal',
                                // this appears to fix a Highcharts bug that
                                // makes text fuzzy in the color #0053b9
                                'textShadow' => false,
                                'color' => $color
                             )
                        );
                        $tooltipConfig = array();
                        $seriesValues = array();
                        if($data_description->display_type == 'pie')
                        {
                            throw new \Exception(get_class($this)." ERROR: chart display_type 'pie' reached in timeseries plot.");
                        }
                        else // ($data_description->display_type != 'pie')
                        {
                            if($this->_swapXY)
                            {
                                $dataLabelsConfig  = array_merge(
                                    $dataLabelsConfig,
                                    array(
                                        'x' => 70
                                    )
                                );
                                $this->_chart['xAxis']['labels']['rotation'] = 0;
                            }
                            else // !($this->_swapXY)
                            {
                                $dataLabelsConfig  = array_merge(
                                    $dataLabelsConfig,
                                    array(
                                        'rotation' => -90,
                                        'align' => 'center',
                                        'y' => -70,
                                    )
                                );
                            } // if($this->_swapXY)

                            // set up seriesValues
                            foreach($values as $i => $v)
                            {
                                $seriesValue = array(
                                    'x' => $start_ts_array[$i],
                                    'y' => $v,
                                );

                                try {
                                    $seriesValue['percentage'] = $yAxisDataObject->getError($i);
                                } catch (\Exception $e) {

                                }

                                $seriesValues[] = $seriesValue;
                            }
                            $tooltipConfig = array_merge(
                                $tooltipConfig,
                                array(
                                    'valueDecimals' => $decimals
                                )
                            );
                        } // ($data_description->display_type == 'pie')

                        $zIndex = isset($data_description->z_index) ? $data_description->z_index : $data_description_index;

                        $areMultipleDataSeries = $dataSeriesCount > 1;
                        $dataSeriesName = $areMultipleDataSeries ? $yAxisDataObject->getName() : $yAxisDataObject->getGroupName();
                        if ($data_description->restrictedByRoles && $this->_showWarnings)
                        {
                            $dataSeriesName .= $this->roleRestrictionsStringBuilder->registerRoleRestrictions($data_description->roleRestrictionsParameters);
                        }
                        if($multiCategory)
                        {
                            $dataSeriesName = (
                                DataWarehouse::getCategoryForRealm($data_description->realm)
                                . ': '
                                . $dataSeriesName
                            );
                        }
                        $formattedDataSeriesName = $dataSeriesName;
                        if ($areMultipleDataSeries)
                        {
                            $dataUnit = $yAxisDataObject->getUnit();
                            $formattedDataSeriesName .= " [<span style=\"color:$yAxisColor\">$dataUnit</span>]";
                        }
                        $formattedDataSeriesName.= $filterParametersTitle;

                        $lookupDataSeriesName = $formattedDataSeriesName;
                        if(isset($legend->{$formattedDataSeriesName}))
                        {
                            $config = $legend->{$formattedDataSeriesName};
                            if(isset($config->title))
                            {
                                $lookupDataSeriesName = $config->title;
                            }
                        }
                        $visible = true;
                        if(isset($data_description->visibility) && isset($data_description->visibility->{$formattedDataSeriesName}))
                        {
                            $visible = $data_description->visibility->{$formattedDataSeriesName};
                        }

                        // note that this is governed by XId and XValue in the non-timeseries case!
                        $drilldown = array('id' => $yAxisDataObject->getGroupId(), 'label' => $yAxisDataObject->getGroupName());

                        $data_series_desc = array(
                            'name' => $lookupDataSeriesName,
                            'otitle' => $formattedDataSeriesName,
                            'datasetId' => $data_description->id,
                            'zIndex' => $zIndex,
                            'drilldown' => $drilldown,
                            'color'=> $data_description->display_type == 'pie'? null: $color,
                            'trackByArea'=>  $data_description->display_type == 'area' ||  $data_description->display_type == 'areaspline',
                            'type' => $data_description->display_type,
                            'dashStyle' => $data_description->line_type,
                            'shadow' => $data_description->shadow,
                            'groupPadding' => 0.1,
                            'pointPadding' => 0,
                            'borderWidth' => 0,
                            'yAxis' => $yAxisIndex,
                            'lineWidth' =>  $data_description->display_type !== 'scatter' ? $data_description->line_width + $font_size/4:0,
                            'showInLegend' => $data_description->display_type != 'pie',
                            //'innerSize' => min(100,(100.0/$totalSeries)*count($this->_chart['series'])).'%',
                            'connectNulls' => $data_description->display_type == 'line' || $data_description->display_type == 'spline',
                            'marker' => array(
                                'enabled' =>$data_description->display_type == 'scatter' || ($values_count < 21 && $this->_width > \DataWarehouse\Visualization::$thumbnail_width),
                                'lineWidth' => 1,
                                'lineColor' => $lineColor,
                                'radius' => $font_size/4 + 5
                            ),
                            'tooltip' => $tooltipConfig,
                            'dataLabels' => $dataLabelsConfig,
                            'data' => $seriesValues,
                            'cursor' => 'pointer',
                            'visible' => $visible,
                            'pointRange' => $pointInterval,
                            'isRemainder' => $isRemainder,
                            'isRestrictedByRoles' => $data_description->restrictedByRoles
                        ); // $data_series_desc

                        if($data_description->display_type!=='line')
                        {
                            if($data_description->combine_type=='stack')
                            {
                                // ask the highcharts library to connect nulls for stacking
                                $data_series_desc['stacking'] = 'normal';
                                $data_series_desc['connectNulls'] = true;
                            }
                            elseif($data_description->combine_type=='percent' && !$data_description->log_scale )
                            {
                                $data_series_desc['stacking'] = 'percent';
                            }
                        }
                        $this->_chart['series'][] = $data_series_desc;

                        // REMOVED: Add percent allocated to XSEDE line if the metric
                        // being displayed is XSEDE Utilization.

                        // ---- Add a trend line on the dataset ----
                        if(isset($data_description->trend_line) && $data_description->trend_line == 1 && $data_description->display_type != 'pie' )
                        {
                            $newValues = array_filter($values, function ($value) {
                                return $value !== null;
                            });

                            $new_values_count = count($newValues);

                            if($new_values_count > 1)
                            {
                                list($m,$b,$r, $r_squared) = \xd_regression\linear_regression(array_keys($newValues), array_values($newValues));
                                $trend_points = array();
                                foreach($newValues as $ii => $value) //first first positive point on trend line since when logscale negative values make it barf
                                {
                                    $y = ($m*$ii)+$b;
                                    if(!$data_description->log_scale || $y > 0)
                                    {
                                        $trend_points[] = array($start_ts_array[$ii], $y);
                                    }
                                }

                                $trend_formula = (number_format($m, 2)==0?number_format($m, 3):number_format($m, 2)).'x '.($b>0?'+':'').number_format($b, 2);

                                $dsn = 'Trend Line: '.$formattedDataSeriesName.' ('.$trend_formula.'), R-Squared='.number_format($r_squared, 2);

                                $lookupDataSeriesName = $dsn;
                                if(isset($legend->{$dsn}))
                                {
                                    $config = $legend->{$dsn};
                                    if(isset($config->title))
                                    {
                                        $lookupDataSeriesName = $config->title;
                                    }
                                }
                                $visible = true;
                                if(isset($data_description->visibility) && isset($data_description->visibility->{$dsn}))
                                {
                                    $visible = $data_description->visibility->{$dsn};
                                }
                                $data_series_desc = array(
                                    'name' => $lookupDataSeriesName,
                                    'otitle' => $dsn,
                                    'zIndex' => $zIndex,
                                    'datasetId' => $data_description->id,
                                    'drilldown' => $drilldown,
                                    'color'=> $color,
                                    'type' => $data_description->log_scale?'spline':'line',
                                    'shadow' => $data_description->shadow,
                                    'groupPadding' => 0.05,
                                    'pointPadding' => 0,
                                    'borderWidth' => 0,
                                    'enableMouseTracking' => false,
                                    'yAxis' => $yAxisIndex,
                                    'lineWidth' => 2 + $font_size/4.0,
                                    'showInLegend' => true,
                                    'marker' => array(
                                        'enabled' => false,
                                        'hover' => array(
                                            'enabled' => false
                                        )
                                    ),
                                    'visible' => $visible,
                                    'dashStyle' => 'ShortDot',
                                    'm' => $m,
                                    'b' => $b,
                                    'data' => $trend_points,
                                    'isRemainder' => $isRemainder,
                                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                                );
                                $this->_chart['series'][] = $data_series_desc;
                            } // if($new_values_count > 1)

                        } // if(isset($data_description->trend_line) && $data_description->trend_line == 1 && $data_description->display_type != 'pie' )


                        if($data_description->std_err == 1 && $data_description->display_type != 'pie')
                        {
                            $error_color_value = \DataWarehouse\Visualization::alterBrightness($color_value, -70);
                            $error_color = '#'.str_pad(dechex($error_color_value), 6, '0', STR_PAD_LEFT);

                            $errorCount = $yAxisDataObject->getErrorCount();
                            $error_series = array();

                            for($i = 0; $i < $errorCount; $i++)
                            {
                                // build the error bar and set for display
                                $v = $yAxisDataObject->getValue($i);
                                $e = $yAxisDataObject->getError($i);
                                $has_value = ( isset($v) && ($v != 0) );
                                $error_series[] = array(
                                    'x' => $start_ts_array[$i],
                                    'low' => $has_value ? $v-$e : null,
                                    'high' => $has_value ? $v+$e : null,
                                    'stderr' => $e
                                );
                            } // for $i ...
                            $dsn = 'Std Err: '.$formattedDataSeriesName;

                            $lookupDataSeriesName = $dsn;
                            if(isset($legend->{$dsn}))
                            {
                                $config = $legend->{$dsn};
                                if(isset($config->title))
                                {
                                    $lookupDataSeriesName = $config->title;
                                }
                            }
                            $visible = true;
                            if(isset($data_description->visibility) && isset($data_description->visibility->{$dsn}))
                                {
                                $visible = $data_description->visibility->{$dsn};
                            }

                            $err_data_series_desc = array(
                                'name' => $lookupDataSeriesName,
                                'showInLegend' => true,
                                'otitle' => $dsn,
                                'zIndex' => $zIndex,
                                'datasetId' => $data_description->id,
                                'drilldown' => $drilldown,
                                'color'=> $error_color,
                                'type' => 'errorbar',
                                'shadow' => $data_description->shadow,
                                'groupPadding' => 0.05,
                                'lineWidth' =>2 + $font_size/4,
                                'pointPadding' => 0,
                                'yAxis' => $yAxisIndex,
                                'tooltip' => array(
                                    'valueDecimals' => $semDecimals
                                ),
                                'data' => $error_series,
                                'cursor' => 'pointer',
                                'visible' => $visible,
                                'isRemainder' => $isRemainder,
                                'isRestrictedByRoles' => $data_description->restrictedByRoles,
                            );

                            if(! $data_description->log_scale)
                            {
                                $this->_chart['series'][] = $err_data_series_desc;
                            } // if ! log_scale
                        } // if($data_description->std_err == 1 && $data_description->display_type != 'pie')
                    } // if( $yAxisDataObject != NULL)
                }
            } // foreach(array_values($yAxisArray) as $yAxisIndex
        } // foreach(array_values($yAxisArray) as $yAxisIndex => $yAxisDataDescriptions) (big long effing loop)

        if ($this->_showWarnings) {
            $this->addRestrictedDataWarning();
        }

        if($this->show_filters)
        {
            $subtitle_separator = " -- ";
            $subtitle = implode($subtitle_separator, array_unique($globalFilterDescriptions));
            $this->setSubtitle($subtitle, $font_size);
        }

        $this->setDataSource(array_keys($dataSources));

        $this->setChartTitleSubtitle($font_size);

    } // function configure()
} // class HighChartTimeseries2
