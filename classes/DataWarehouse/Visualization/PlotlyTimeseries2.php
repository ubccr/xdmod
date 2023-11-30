<?php
namespace DataWarehouse\Visualization;

use DataWarehouse;
use DataWarehouse\Data\TimeseriesDataset;

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
class PlotlyTimeseries2 extends Plotly2
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
        $user,
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
            $user,
            $swap_xy,
            $showContextMenu,
            $share_y_axis,
            $hide_tooltip,
            $min_aggregation_unit,
            $showWarnings
        );

        $this->_queryType = 'timeseries';
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

        // Keep track of the unique data unit names
        $yUnitNames = array();

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

            try
            {
                $realm = \Realm\Realm::factory($data_description->realm);
                $stat = $realm->getStatisticObject($data_description->metric);
            }
            catch(\Exception $ex)
            {
                continue;
            }
            $this->_chart['metrics'][$stat->getName(false)] = $stat->getHtmlDescription();
            $yUnitNames[$stat->getName()] = 1;

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
                $query = new \DataWarehouse\Query\TimeseriesQuery(
                    $data_description->realm,
                    $this->_aggregationUnit,
                    $this->_startDate,
                    $this->_endDate
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

                $data_description->roleRestrictionsParameters = $query->setMultipleRoleParameters($data_description->authorizedRoles, $this->user);
                $data_description->restrictedByRoles = $query->isLimitedByRoleRestrictions();

                $globalFilterDescriptions = array_merge(
                    $globalFilterDescriptions,
                    $query->roleParameterDescriptions
                );

                $query->setFilters($data_description->filters);

                $dataSources[$query->getDataSource()] = 1;
                $group_by = $query->addGroupBy($data_description->group_by);
                $this->_chart['dimensions'][$group_by->getName()] = $group_by->getHtmlDescription();
                $query->addStat($data_description->metric);
                if (
                    $data_description->std_err == 1
                    || (
                        property_exists($data_description, 'std_err_labels')
                        && $data_description->std_err_labels
                    )) {
                    $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
                        $data_description->metric
                    );
                    if ($query->getRealm()->statisticExists($semStatId)) {
                        $query->addStat($semStatId);
                    }
                    else {
                        $data_description->std_err = 0;
                        $data_description->std_err_labels = false;
                    }
                }

                $query->addOrderByAndSetSortInfo($data_description);

                $statisticObject = $query->_stats[$data_description->metric];
                $decimals = $statisticObject->getPrecision();

                $defaultYAxisLabel = 'yAxis'.$yAxisIndex;
                $yAxisLabel = ($data_description->combine_type=='percent'? '% of ':'').(
                                ($this->_hasLegend && $dataSeriesCount > 1)
                                    ? $statisticObject->getUnit()
                                    : $statisticObject->getName()
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
                    $yAxisLeftBoundStart = 0.175;
                    $yAxisRightBoundStart = 0.825;
                    $yAxisStep = 0.175;

                    $yAxis = array(
                        'automargin' => true,
                        'title' => '<b>' . $yAxisLabel . '</b>', 
                        'titlefont' => array(
                                'color'=> $yAxisColor,
                                'size' => (12 + $font_size)
                        ),
                        'otitle' => $originalYAxisLabel,
                        'dtitle' => $defaultYAxisLabel,
                        'tickfont' => array(
                            'size' => (11 + $font_size)
                        ),
                        'type' => $data_description->log_scale? 'logarithmic' : 'linear',
                        'separatethousands' => true,
                        'overlaying' => $yAxisIndex == 0 ? null : 'y',
                        'linewidth' => 2 + $font_size / 4,
                        'gridwidth' => $yAxisCount > 1 ?0: 1 + ($font_size/4),
                    );

                    if ($yAxisIndex > 0){
                        if ($yAxisIndex % 2 == 0) {
                            $yAxis = array_merge($yAxis, array(
                                'side' => 'left',
                                'anchor' => 'free',
                                'position' => $yAxisLeftBoundStart - ($yAxisStep * ($yAxisIndex/2))
                            ));
                        }
                        else {
                            $yAxis = array_merge($yAxis, array(
                                'side' => 'right',
                                'anchor' => $yAxisIndex > 1 ? 'free' : 'x',
                                'position' => $yAxisIndex > 1 ? $yAxisRightBoundStart + ($yAxisStep * (floor($yAxisIndex/2))) : null
                            ));
                        }
                    } 

                    $yIndex = $yAxisIndex + 1;
                    $this->_chart['layout']["yaxis{$yIndex}"] = $yAxis;
                } // if($yAxis == null)

                $dataset = new TimeseriesDataset($query);

                $xAxisData = $dataset->getTimestamps();

                $start_ts_array = array();
                foreach($xAxisData->getStartTs() as $st)
                {
                    $start_ts_array[] = $st;
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
                        'automargin' => true,
                        'ticklen' => 0,
                        'title' => array(
                            'text' => '<b>' . $xAxisLabel . '</b>',
                            'font' => array(
                                'color'=> '#000000',
                                'size' => (12 + $font_size)
                            )
                        ),
                        'otitle' => $originalXAxisLabel,
                        'dtitle' => $defaultXAxisLabel,
                        'tickfont' => array(
                            'size' => (11 + $font_size)
                        ),
                        'linewidth' => 2 + $font_size / 4,
                        'showgrid' => false,
                    );
                     $this->_chart['layout']['xaxis'] = $xAxis;
                } // if(!isset($xAxis))

                //  ----------- set up yAxis, assign to chart ... eventually -----------

                if($data_description->std_err == 1)
                {
                    $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
                        $data_description->metric
                    );
                    $semStatisticObject = $query->_stats[$semStatId];
                    $semDecimals = $semStatisticObject->getPrecision();
                }

                $this->_total = max($this->_total, $dataset->getUniqueCount());

                $yAxisDataObjectsArray = $dataset->getDatasets($limit, $offset, $summarizeDataseries);

                // operate on each yAxisDataObject, a SimpleTimeseriesData object
                // @refer HighChart2 line 866

                foreach($yAxisDataObjectsArray as $traceIndex => $yAxisDataObject)
                {
                    //throw new \Exception(json_encode($yAxisDataObject));
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

                        // Decide whether to show data point markers:
                        $values_count = count($values);
                        // Count datapoints having actual, non-null y values:
                        $y_values_count = 0;
                        foreach ($values as $y_value) {
                            if ($y_value !== null) {
                                ++$y_values_count;
                            }
                            // we are only interested in the == 1 case.
                            if ($y_values_count > 1) {
                                break;
                            }
                        }
                        // Display markers for scatter plots, non-thumbnail plots of data series
                        // with fewer than 21 points, or for any data series with a single y value.
                        $showMarker = $data_description->display_type == 'scatter' ||
                            ($values_count < 21 && $this->_width > \DataWarehouse\Visualization::$thumbnail_width) ||
                            $y_values_count == 1;

                        $isRemainder = $yAxisDataObject->getGroupId() === TimeseriesDataset::SUMMARY_GROUP_ID;

                        $filterParametersTitle = $data_description->long_legend == 1?$query->getFilterParametersTitle():'';
                        if($filterParametersTitle != '')
                        {
                            $filterParametersTitle = ' {'.$filterParametersTitle.'}' ;
                        }

                        // --- set up $dataLabelsConfig, $seriesValues, $tooltipConfig ---
                        $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;
                        //throw new \Exception('hi');
                        $tooltipConfig = array();
                        $xValues = array();
                        $yValues = array();
                        $trace = array();
                        $seriesValues = array();
                        if($data_description->display_type == 'pie')
                        {
                            throw new \Exception(get_class($this)." ERROR: chart display_type 'pie' reached in timeseries plot.");
                        } else {
                            if($this->_swapXY)
                            {
                                $trace['textangle'] = 90;
                                $this->_chart['layout']['xaxis']['tickangle'] = 0;
                            }
                            else // !($this->_swapXY)
                            {
                                $trace['textangle'] = -90;
                            } // if($this->_swapXY)

                            // set up seriesValues
                            foreach($values as $i => $v)
                            {
                                $xValues[] = date('m-d-Y', $start_ts_array[$i]);
                                $yValues[] = $v;
                                $seriesValue = array(
                                    'x' => date('m-d-Y', $start_ts_array[$i]),
                                    'y' => $v,
                                );

                                try {
                                    $seriesValue['percentage'] = $yAxisDataObject->getError($i);
                                } catch (\Exception $e) {

                                }

                                $seriesValues[] = $seriesValue;
                            }
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
                                $data_description->category
                                . ': '
                                . $dataSeriesName
                            );
                        }
                        $formattedDataSeriesName = $dataSeriesName;

                        // Append units to legend if there are mutiple datasets unless they all use the same units (i.e. a
                        // single y-axis is used without forcing it). If you force the single y-axis then the
                        // units will be appended.
                        if ($areMultipleDataSeries && (count($yUnitNames) > 1 || $this->_shareYAxis ))
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

                        $tooltip = '%{x}' . '<br> <span style="color:' . $color
                            . ';">●</span> ' . $formattedDataSeriesName . ': <b>%{y:,}</b> <extra></extra>';

                        $tooltip = $formattedDataSeriesName . ': <b>%{y:,}</b> <extra></extra>';
                        $data_labels_enabled = $data_description->value_labels || $std_err_labels_enabled;
                        // note that this is governed by XId and XValue in the non-timeseries case!
                        $drilldown = array('id' => $yAxisDataObject->getGroupId(), 'label' => $yAxisDataObject->getGroupName());

                        $trace = array(
                            'name' => $lookupDataSeriesName,
                            'otitle' => $formattedDataSeriesName,
                            'datasetId' => $data_description->id,
                            'zIndex' => $zIndex,
                            'drilldown' => $drilldown,
                            'marker' => array(
                                'size' => $font_size + 7,
                                'color' => $color,
                                'line' => array(
                                    'width' => 1,
                                    'color' => $lineColor
                                )
                            ),
                            'line' => array(
                                'color' => $data_description->display_type == 'pie'? null: $lineColor,
                                'dash' => $data_description->line_type,
                                'width' => $data_description->display_type !== 'scatter' ? $data_description->line_width + $font_size/4:0
                            ),
                            'mode' => $data_description->display_type == 'scatter' ? 'markers' : 'lines+markers',
                            'hoveron' => $data_description->display_type == 'area' ||
                                            $data_description->display_type == 'areaspline' ? 'points+fills' : 'points',
                            'yaxis' => "y{$yIndex}",
                            'showlegend' => $data_description->display_type != 'pie',
                            'hovertemplate' => $tooltip,
                            'text' => $data_labels_enabled ? $yValues : null,
                            'textposition' => 'top right',
                            'textfont' => array(
                                'color' => $color
                            ),
                            'x' => $xValues,
                            'y' => $yValues,
                            'seriesPoints' => $seriesValues,
                            'visible' => $visible,
                            'isRemainder' => $isRemainder,
                            'isRestrictedByRoles' => $data_description->restrictedByRoles,
                        ); // $data_series_desc

                        // Check type to adjust plot for current settings
                        //if (


                        if($data_description->display_type!=='line')
                        {

                            if ($data_description->display_type=='area' && $traceIndex == 0) {
                                $hidden_trace = array(
                                    'x' => $xValues,
                                    'y' => array_fill(0, count($xValues) , 0),
                                    'showlegend' => false,
                                    'marker' => array(
                                        'color' => '#FFFFFF'
                                    ),
                                    'line' => array(
                                        'color' => '#FFFFFF'
                                    ),
                                    'hoverinfo' => 'skip',
                                    'type' => 'scatter',
                                );

                                $this->_chart['data'][] = $hidden_trace;

                            }

                            if ($data_description->combine_type=='side' && $data_description->display_type=='area'){
                                $trace['fill'] = $traceIndex == 0 ? 'tozeroy' : 'tonexty';
                            }
                            elseif($data_description->combine_type=='stack')
                            {
                                // ask the highcharts library to connect nulls for stacking
                                $trace['stackgroup'] = 'one';
                                $trace['stackgaps'] = 'interpolate';
                            }
                            elseif($data_description->combine_type=='percent' && !$data_description->log_scale )
                            {
                                $trace['stackgroup'] = 'one';
                                $trace['stackgaps'] = 'interpolate';
                                $trace['groupnorm'] = 'percent';
                            }
                        }
                        $this->_chart['data'][] = $trace;

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
                                $trendX = array();
                                $trendY = array();
                                foreach($newValues as $ii => $value) //first first positive point on trend line since when logscale negative values make it barf
                                {
                                    $y = ($m*$ii)+$b;
                                    if(!$data_description->log_scale || $y > 0)
                                    {
                                        $trendX[] = date('m-d-Y', $start_ts_array[$ii]);
                                        $trendY[] = $y;
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
                                $trendline_trace = array(
                                    'name' => $lookupDataSeriesName,
                                    'otitle' => $dsn,
                                    'zIndex' => $zIndex,
                                    'datasetId' => $data_description->id,
                                    'drilldown' => $drilldown,
                                    'color'=> $color,
                                    'type' => 'scatter',
                                    'yaxis' => "y{$yAxisIndex}",
                                    'lineWidth' => 2 + $font_size/4.0,
                                    'showlegend' => true,
                                    'hoverinfo' => 'skip',
                                    'marker' => array(
                                        'size' => 0
                                    ),
                                    'line' => array(
                                        'shape' => $data_description->log_scale ? 'spline' : 'line',
                                        'dash' => 'dot' 
                                    ),
                                    'visible' => $visible,
                                    'm' => $m,
                                    'b' => $b,
                                    'x' => $trendX,
                                    'y' => $trendY,
                                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                                );
                                $this->_chart['data'][] = $trendline_trace;
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
                                    'x' => date('m-d-Y', start_ts_array[$i]),
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
