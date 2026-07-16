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
class TimeseriesChart extends AggregateChart
{
    /**
     * @see AggregateChart
     */
    protected $show_filters;

    /**
     * @see AggregateChart
     */
    protected $_hasLegend;

    // ---------------------------------------------------------
    // __construct()
    //
    // Constructor for TimeseriesChart class.
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
        $isThumbnail = $this->_width <= \DataWarehouse\Visualization::$thumbnail_width;

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
            if(isset($this->_prevCategory) && $category != $this->_prevCategory)
            {
                $multiCategory = true;
            } else {
                $this->_prevCategory = $category;
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
        }

        $yAxisCount = count($yAxisArray);
        $legendRank = 0;
        $globalFilterDescriptions = array();
        $dates = array();

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
                    } else {
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
                $yAxisType = null;
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
                    if(isset($config->chartType))
                    {
                        $yAxisType = $config->chartType;
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
                    $yIndex = $yAxisIndex + 1;
                    $swapXYDone = false;
                    $yAxisName = $yAxisIndex == 0 ? 'yaxis' : "yaxis{$yIndex}";
                    $xAxisName = substr_replace($yAxisName, 'xaxis', 0, 5);

                    $yAxis = array(
                        'automargin' => true,
                        'autorangeoptions' => array(
                            'minallowed' => $yAxisMin,
                            'maxallowed' => $yAxisMax
                        ),
                        'layer' => 'below traces',
                        'title' => array(
                            'text' => '<b>' . $yAxisLabel . '</b>',
                            'font' => array(
                                'color'=> $yAxisColor,
                                'size' => (12 + $font_size),
                                'family' => "'Lucida Grande', 'Lucida Sans Unicode', Arial, Helvetica, sans-serif",
                            ),
                        ),
                        'otitle' => $originalYAxisLabel,
                        'dtitle' => $defaultYAxisLabel,
                        'exponentformat' => 'SI',
                        'tickfont' => array(
                            'size' => (11 + $font_size),
                            'color' => '#606060',
                        ),
                        'ticksuffix' => ' ',
                        'tickprefix' => $yAxisIndex > 0 ? ' ' : null,
                        'tickmode' => 'auto',
                        'nticks' => 10,
                        'type' => ($data_description->log_scale || $yAxisType == 'log') ? 'log' : 'linear',
                        'rangemode' => 'tozero',
                        'range' => [$yAxisMin, $yAxisMax],
                        'index' => $yAxisIndex,
                        'separatethousands' => true,
                        'overlaying' => $yAxisIndex == 0 ? null : 'y',
                        'linewidth' => 2 + $font_size / 4,
                        'linecolor' => '#c0d0e0',
                        'side' => 'left',
                        'anchor' => 'x',
                        'autoshift' => true,
                        'gridwidth' => $yAxisCount > 1 ? 0 : 1 + ($font_size/8),
                        'gridcolor' => '#c0c0c0',
                        'zeroline' => false,
                    );

                    if ($yAxisIndex > 0) {
                        $side = 'left';
                        $anchor = 'free';
                        if ($yAxisIndex % 2 == 1) {
                            $side = 'right';
                        }
                        if ($yAxisIndex == 1) {
                            $anchor = 'x';
                        }
                        $yAxis = array_merge($yAxis, array(
                            'side' => $side,
                            'anchor' => $anchor,
                            'autoshift' => true,
                        ));
                    }

                    $this->_chart['layout']["{$yAxisName}"] = $yAxis;
                }

                $dataset = new TimeseriesDataset($query);

                $xAxisData = $dataset->getTimestamps();

                $start_ts_array = array();
                foreach($xAxisData->getStartTs() as $st)
                {
                    $start_ts_array[] = $st;
                }

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
                    $xAxis = array(
                        'automargin' => true,
                        'layer' => 'below traces',
                        'title' => array(
                            'text' => '<b>' . $xAxisLabel . '</b>',
                            'font' => array(
                                'color' => '#000000',
                                'size' => (12 + $font_size),
                                'family' => "'Lucida Grande', 'Lucida Sans Unicode', Arial, Helvetica, sans-serif",
                            ),
                        ),
                        'otitle' => $originalXAxisLabel,
                        'dtitle' => $defaultXAxisLabel,
                        'tickfont' => array(
                            'size' => $this->_swapXY ? (8 + $font_size) : (11 + $font_size),
                            'color' => '#606060',
                        ),
                        'ticksuffix' => ' ',
                        'tickformat' => $this->getDateFormat(),
                        'type' => 'date',
                        'rangemode' => 'tozero',
                        'hoverformat' => $this->getDateFormat(),
                        'tickmode' => 'array',
                        'timeseries' => true,
                        'nticks' => 20,
                        'spikedash' => 'solid',
                        'spikethickness' => 1,
                        'spikecolor' => '#C0C0C0',
                        'linewidth' => 2 + $font_size / 4,
                        'linecolor' => '#c0d0e0',
                        'showgrid' => false,
                        'gridcolor' => '#c0c0c0',
                        'zeroline' => false,
                    );
                     $this->_chart['layout']['xaxis'] = $xAxis;
                }

                //  ----------- set up yAxis, assign to chart ... eventually -----------
                $semDecimals = null;
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
                foreach($yAxisDataObjectsArray as $traceIndex => $yAxisDataObject)
                {
                    $legendRank += 3;
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

                        // Calculate number of non-null values to determine data series marker visibility.
                        // If all values are null then skip this series.
                        $visiblePoints = 0;
                        foreach($yAxisDataObject->getValues() as $value)
                        {
                            if($value != null)
                            {
                                $visiblePoints++;
                            }
                            if($visiblePoints > 1)
                            {
                                break;
                            }
                        }
                        if($visiblePoints == 0)
                        {
                            continue;
                        }
                        $values = $yAxisDataObject->getValues();

                        // Show markers if the non-thumbnail plot has less than 21 visible data points.
                        // Also show markers if there is one data point otherwise thumbnail plots with 1 non-null point will be
                        // hidden.
                        // Need check for chart types that this applies to otherwise bar, scatter, and pie charts will be hidden.
                        $showMarker = in_array($data_description->display_type, array('scatter', 'pie', 'bar', 'h_bar', 'column'))
                            || (count($values) < 21 && !$isThumbnail)
                            || $visiblePoints == 1;

                        $isRemainder = $yAxisDataObject->getGroupId() === TimeseriesDataset::SUMMARY_GROUP_ID;

                        $filterParametersTitle = $data_description->long_legend == 1?$query->getFilterParametersTitle():'';
                        if($filterParametersTitle != '')
                        {
                            $filterParametersTitle = ' {'.$filterParametersTitle.'}' ;
                        }

                        // --- set up $dataLabelsConfig, $seriesValues, $tooltipConfig ---
                        $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;
                        $this->_chart['layout']['stdErr'] = $data_description->std_err;
                        $xValues = array();
                        $yValues = array();
                        $text = array();
                        $trace = array();
                        $seriesValues = array();
                        if($data_description->display_type == 'pie')
                        {
                            throw new \Exception(get_class($this)." ERROR: chart display_type 'pie' reached in timeseries plot.");
                        } else {
                            if($this->_swapXY)
                            {
                                $trace['textangle'] = 90;
                            } else {
                                $trace['textangle'] = -90;
                            }

                            // set up seriesValues
                            foreach($values as $i => $v)
                            {
                                $xValues[] = $start_ts_array[$i]*1000;
                                $dates[] = $start_ts_array[$i]*1000;
                                $yValues[] = $v;
                                $text[] = number_format($v, $decimals, '.', ',');
                                $seriesValue = array(
                                    'x' => $start_ts_array[$i]*1000,
                                    'y' => $v,
                                );

                                try {
                                    $seriesValue['percentage'] = $yAxisDataObject->getError($i);
                                } catch (\Exception $e) {

                                }

                                $seriesValues[] = $seriesValue;
                            }
                        }

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

                        $tooltip = $lookupDataSeriesName . ": <b>%{y:,.{$decimals}f}</b> <extra></extra>";
                        if ($this->_chart['layout']['hovermode'] != 'closest') {
                            $this->_chart['layout']['hoverlabel']['bordercolor'] = $yAxisColor;
                        }
                        $data_labels_enabled = $data_description->value_labels || $std_err_labels_enabled;
                        // note that this is governed by XId and XValue in the non-timeseries case!
                        $drilldown = array('id' => $yAxisDataObject->getGroupId(), 'label' => $yAxisDataObject->getGroupName());
                        if ($yAxisCount > 1) {
                            $this->_chart['layout']["{$yAxisName}"]['showgrid'] = false;
                        }

                        $trace = array(
                            'name' => $lookupDataSeriesName,
                            'oname' => $lookupDataSeriesName,
                            'meta' => array(
                                'primarySeries' => true
                            ),
                            'customdata' => $lookupDataSeriesName,
                            'otitle' => $formattedDataSeriesName,
                            'datasetId' => $data_description->id,
                            'zIndex' => $zIndex,
                            'cliponaxis' => false,
                            'drilldown' => $drilldown,
                            'marker' => array(
                                'size' => ($font_size/4 + 5) * 2,
                                'color' => $color,
                                'line' => array(
                                    'width' => 1,
                                    'color' => $lineColor
                                ),
                                'symbol' => $this->_symbolStyles[$traceIndex % 5],
                                'opacity' => $showMarker ? 1.0 : 0.0
                            ),
                            'type' => $data_description->display_type == 'h_bar' || $data_description->display_type == 'column' ? 'bar' : $data_description->display_type,
                            'line' => array(
                                'color' => $data_description->display_type == 'pie'? null: $color,
                                'dash' => $data_description->line_type,
                                'width' => $data_description->display_type !== 'scatter' ? $data_description->line_width + $font_size/4 : 0,
                                'shape' => ($data_description->display_type == 'spline' || $data_description->display_type == 'areaspline') ? 'spline' : 'linear'
                            ),
                            'mode' => $data_description->display_type == 'scatter' ? 'markers' : 'lines+markers',
                            'hoveron' => 'points',
                            'yaxis' => "y{$yIndex}",
                            'showlegend' => $data_description->display_type != 'pie',
                            'hovertext' => $text,
                            'hovertemplate' => $tooltip,
                            'hoverlabel' => array(
                                'align' => 'left',
                                'bgcolor' => 'rgba(255, 255, 255, 0.8)',
                                'bordercolor' => $yAxisColor,
                                'font' => array(
                                    'size' => 12.8,
                                    'color' => '#000000',
                                    'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                                ),
                                'namelength' => -1,
                            ),
                            'text' => array(),
                            'textposition' => 'outside',
                            'textangle' => $data_description->display_type == 'h_bar' ? 0 : -90,
                            'textfont' => array(
                                'size' => 11 + $font_size,
                                'color' => $color,
                                'family' => "'Lucida Grande', 'Lucida Sans Unicode', Arial, Helvetica, sans-serif",
                            ),
                            'x' => $this->_swapXY ? $yValues : $xValues,
                            'y' => $this->_swapXY ? $xValues : $yValues,
                            'offsetgroup' => $yAxisCount > 1 ? "group{$yIndex}{$legendRank}" : "group{$legendRank}",
                            'legendgroup' => $legendRank,
                            'legendrank' => $legendRank,
                            'traceorder' => $legendRank,
                            'seriesData' => $seriesValues,
                            'visible' => $visible,
                            'isRemainder' => $isRemainder,
                            'isRestrictedByRoles' => $data_description->restrictedByRoles,
                        ); // $data_series_desc

                        if ($data_description->display_type == 'areaspline') {
                            $trace['type'] = 'area';
                        }

                        // Set swap axis
                        if ($this->_swapXY && $data_description->display_type!='pie') {
                            if ($trace['type'] == 'bar') {
                                $trace = array_merge($trace, array('orientation' => 'h'));
                                $trace['hovertemplate'] = '%{hovertext}' . '<br>'. "<span style=\"color:$color\";> ●</span> "
                                                         . $lookupDataSeriesName . ": <b>%{x:,.{$decimals}f}</b> <extra></extra>";
                                $trace['textangle'] = 0;
                            }

                            $this->_chart['layout']['hovermode'] = 'y unified';
                            $trace['xaxis'] = "x{$yIndex}";
                            unset($trace['yaxis']);
                            $trace['hovertemplate'] = $lookupDataSeriesName . ": <b>%{x:,.{$decimals}f}</b> <extra></extra>";

                            if (!$swapXYDone) {
                                $xAxis['autorange'] = 'reversed';
                                $yAxis['side'] = ($yAxisIndex % 2 != 0) ? 'top' : 'bottom';
                                if ($yAxis['side'] == 'top') {
                                    $yAxis['title']['standoff'] = 0;
                                }
                                $yAxis['anchor'] = 'free';
                                if (isset($yAxis['overlaying'])) {
                                    $yAxis['overlaying'] = 'x';
                                }
                                $xAxisStep = 0.115;
                                $xAxisBottomBoundStart = 0 + ($xAxisStep * ceil($yAxisCount/2));
                                $xAxisTopBoundStart = 1 - ($xAxisStep * floor($yAxisCount/2));
                                $topShift = floor($yAxisCount/2) - floor($yAxisIndex/2);
                                $bottomShift = ceil($yAxisCount/2) - ceil($yAxisIndex/2);

                                $yAxis['position'] = $yAxis['side'] == 'top' ? min(1 - ($xAxisStep * $topShift), 1) : max(0 + ($xAxisStep * $bottomShift), 0);
                                $yAxis['domain'] = array(0,1);
                                $yAxis['title']['standoff'] = 0;
                                $yAxis['showgrid'] = $yAxisCount > 1 ? false : true;
                                $xAxis['domain'] = array($xAxisBottomBoundStart, $xAxisTopBoundStart);

                                $xAxis['tickmode'] = $this->_chart['layout']['xaxis']['tickmode'];
                                $xAxis['tick0'] = $this->_chart['layout']['xaxis']['tick0'];
                                $xAxis['dtick'] = $this->_chart['layout']['xaxis']['dtick'];
                                $xAxis['type'] = $this->_chart['layout']['xaxis']['type'];

                                $this->_chart['layout'][$xAxisName] = $yAxis;
                                $this->_chart['layout']['yaxis'] = $xAxis;
                                $swapXYDone = true;
                            }
                            $this->_chart['layout']['xaxis']['type'] = $yAxis['type'];
                            if ($yAxisIndex > 0) {
                                unset($this->_chart['layout']["{$yAxisName}"]);
                            }
                        }

                        // Set stacking and area configurationg
                        if($data_description->display_type!=='line')
                        {
                            if ($trace['type']=='area' && $traceIndex == 0) {
                                $hidden_trace = array(
                                    'name' => 'area fix',
                                    'x' => $this->_swapXY ? array_fill(0, count($xValues), 0) : $xValues,
                                    'y' => $this->_swapXY ? $xValues : array_fill(0, count($xValues), 0),
                                    'zIndex' => 0,
                                    'showlegend' => false,
                                    'mode' => 'lines+markers',
                                    'marker' => array(
                                        'color' => '#FFFFFF',
                                        'size' => 0.1
                                    ),
                                    'line' => array(
                                        'color' => '#FFFFFF'
                                    ),
                                    'hoverinfo' => 'skip',
                                    'legendrank' => -1000,
                                    'traceorder' => -1000,
                                    'yaxis' => "y{$yIndex}",
                                    'type' => 'scatter',
                                );

                                if ($this->_swapXY) {
                                    $hidden_trace['xaxis'] = "x{$yIndex}";
                                    unset($hidden_trace['yaxis']);
                                }


                                $this->_chart['data'][] = $hidden_trace;
                            }

                            if ($trace['type'] == 'bar') {
                                $trace['line']['width'] = 0;
                                $trace['marker']['line']['width'] = 0;
                            }

                            if ($data_description->combine_type=='side' && $trace['type']=='area'){
                                if ($this->_swapXY) {
                                    $trace['fill'] = 'tozerox';
                                } else {
                                    $trace['fill'] = 'tozeroy';
                                }
                            }
                            elseif($data_description->combine_type=='stack')
                            {
                                $trace['stackgroup'] = 'one';
                                $this->_chart['layout']['barmode'] = 'stack';
                            }
                            elseif($data_description->combine_type=='percent' && !$data_description->log_scale )
                            {
                                $trace['stackgroup'] = 'one';
                                $trace['groupnorm'] = 'percent';
                                $trace['hovertemplate'] = $formattedDataSeriesName . ': <b>%{hovertext}</b> <extra></extra>';
                                $this->_chart['layout']['barmode'] = 'stack';
                                $this->_chart['layout']['barnorm'] = 'percent';
                            }
                        }

                        // Set null connector
                        if (in_array(null, $yValues) && $data_description->display_type == 'line') {
                            $null_trace = array(
                                'name' => 'gap connector',
                                'zIndex' => $zIndex,
                                'x' => $this->_swapXY ? $yValues : $xValues,
                                'y' => $this->_swapXY ? $xValues : $yValues,
                                'showlegend' => false,
                                'mode' => 'lines',
                                'line' => array(
                                    'color' => $color,
                                    'dash' => 'dash'
                                ),
                                'connectgaps' => true,
                                'hoverinfo' => 'skip',
                                'offsetgroup' => $yAxisCount > 1 ? "group{$yIndex}{$legendRank}" : "group{$legendRank}",
                                'legendgroup' => $legendRank,
                                'legendrank' => -1000,
                                'traceorder' => -1000,
                                'type' => 'scatter',
                                'visible' => $visible,
                                'yaxis' => "y{$yIndex}",
                                'isRestrictedByRoles' => $data_description->restrictedByRoles,
                            );

                            if ($this->_swapXY) {
                                $null_trace['xaxis'] = "x{$yIndex}";
                                unset($null_trace['yaxis']);
                            }

                            $this->_chart['data'][] = $null_trace;
                        }

                        $this->_chart['data'][] = $trace;
                        $idx = count($this->_chart['data']) - 1;
                        $error_color_value = \DataWarehouse\Visualization::alterBrightness($color_value, -70);
                        $error_color = '#'.str_pad(dechex($error_color_value), 6, '0', STR_PAD_LEFT);
                        $error_info = $this->buildErrorDataSeries(
                            $trace,
                            $data_description,
                            $legend,
                            $error_color,
                            $yAxisDataObject,
                            $formattedDataSeriesName,
                            $yAxisIndex,
                            $semDecimals,
                            $decimals,
                            $zIndex
                        );
                        if ($data_labels_enabled) {
                            if ($this->_chart['data'][$idx]['type'] == 'bar' && count($yAxisDataObjectsArray) > 1) {
                                $this->_chart['data'][$idx]['constraintext'] = 'inside';
                                if ($std_err_labels_enabled && $data_description->value_labels) {
                                    $this->_chart['data'][$idx]['text'] = $error_info['data_labels'];
                                }
                                elseif ($std_err_labels_enabled && !$data_description->value_labels){
                                    $this->_chart['data'][$idx]['text'] = $error_info['error_labels'];
                                } else {
                                    $this->_chart['data'][$idx]['text'] = $text;
                                }
                            } else {
                                $this->configureDataLabels(
                                    $data_description,
                                    $error_info,
                                    $xValues,
                                    $yValues,
                                    $std_err_labels_enabled,
                                    $font_size,
                                    $color,
                                    $isThumbnail,
                                    $decimals
                                );
                            }
                        }

                        // ---- Add a trend line on the dataset ----
                        if(isset($data_description->trend_line) && $data_description->trend_line == 1 && $data_description->display_type != 'pie' )
                        {
                            $newValues = array_filter($values, function ($value) {
                                return $value !== null;
                            });

                            $new_values_count = count($newValues);

                            if($new_values_count > 1)
                            {
                                list($m, $b, $r, $r_squared) = \xd_regression\linear_regression(array_keys($newValues), array_values($newValues));
                                $trendX = array();
                                $trendY = array();
                                foreach($newValues as $ii => $value) //first first positive point on trend line since when logscale negative values make it barf
                                {
                                    $y = ($m*$ii)+$b;
                                    if(!$data_description->log_scale || $y > 0)
                                    {
                                        $trendX[] = date('Y-m-d', $start_ts_array[$ii]);
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
                                    'oname' => $lookupDataSeriesName,
                                    'meta' => array(
                                        'primarySeries' => false,
                                        'trendlineSeries' => true
                                    ),
                                    'otitle' => $dsn,
                                    'zIndex' => $zIndex,
                                    'datasetId' => $data_description->id,
                                    'drilldown' => $drilldown,
                                    'color' => $color,
                                    'type' => 'scatter',
                                    'yaxis' => "y{$yIndex}",
                                    'showlegend' => true,
                                    'hoverinfo' => 'skip',
                                    'text' => array(),
                                    'mode' => 'lines+markers',
                                    'marker' => array(
                                        'size' => 0.1,
                                    ),
                                    'line' => array(
                                        'shape' => 'linear',
                                        'dash' => 'dot',
                                        'color' => $color,
                                        'width' => $data_description->line_width + $font_size/4,
                                    ),
                                    'legendrank' => $trace['legendrank'] + 2,
                                    'traceorder' => $trace['legendrank'] - 2,
                                    'visible' => $visible,
                                    'm' => $m,
                                    'b' => $b,
                                    'r' => $r,
                                    'x' => $this->_swapXY ? $trendY : $trendX,
                                    'y' => $this->_swapXY ? $trendX : $trendY,
                                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                                );

                                if ($this->_swapXY) {
                                    unset($trendline_trace['yaxis']);
                                    $trendline_trace['xaxis'] = "x{$yIndex}";
                                }

                                $this->_chart['data'][] = $trendline_trace;
                            }
                        } // end closure for trendline trace
                    } // end closure for if $yAxisDataObject does not equal NULL
                }
            } // end closure for each $yAxisArray
        } // end closure for each $yAxisDataDescriptions

        if ($this->_showWarnings) {
            $this->addRestrictedDataWarning();
        }

        // Compute tick label placement and format
        $axisName = $this->_swapXY ? 'yaxis' : 'xaxis';
        if (!empty($dates)) {
            $dates = array_unique($dates);
            $value_count = count($dates);
            $dtick = $this->getPointInterval();

            if (($this->_aggregationUnit == 'Day' || $this->_aggregationUnit == 'day')) {
                $dtick = max(floor($value_count / 12), 1);
            }

            if ($this->_aggregationUnit == 'Month' || $this->_aggregationUnit == 'month') {
                $dtick = max(round($value_count / 12), 1);
            }

            if ($this->_aggregationUnit == 'Quarter' || $this->_aggregationUnit == 'quarter') {
                $dtick = ceil(max(ceil($value_count / 4), 1) / 3.5);
            }

            if ($this->_aggregationUnit == 'Year' || $this->_aggregationUnit == 'year') {
                $dtick = ceil($value_count / 15);
            }

            $tickvals = array();
            $last_idx = $value_count - 1;
            $include_both_labels = false;
            for ($i = 0; $i < $value_count; $i += $dtick) {
                if (!$include_both_labels && (($value_count - $i) <= $dtick)) {
                    if (($value_count - $i) <= round($dtick * .30)) {
                        // tick at end of loop is close (within 30%) to last data point
                        // thererfore just include the last data point tick label
                        $i = $last_idx;
                    } else {
                        $include_both_labels = true;
                    }
                }
                $tickvals[] = $dates[$i];
                if ($i != $last_idx && $include_both_labels) {
                    $i = $last_idx - $dtick;
                }
            }
            $this->_chart['layout']["{$axisName}"]['tickvals'] = $tickvals;
        }

        // Timeseries ticks need to be set to 'auto' if all legend elements are hidden
        // due to bug with Plotly JS manually set ticks.
        if (isset($this->_chart['layout']["{$axisName}"]) && $this->_chart['layout']["{$axisName}"]['tickmode'] !== 'auto') {
            $this->_chart['layout']["{$axisName}"]['tickmode'] = 'auto';
            $visibility = array_column($this->_chart['data'], 'visible');
            if (in_array(true, $visibility, true)) {
                $this->_chart['layout']["{$axisName}"]['tickmode'] = 'array';
            }
        }

        if($this->show_filters)
        {
            $subtitle_separator = " -- ";
            $subtitle = implode($subtitle_separator, array_unique($globalFilterDescriptions));
            $this->setSubtitle($subtitle, $font_size);
        }

        $this->setDataSource(array_keys($dataSources));

        $this->setChartTitleSubtitle($font_size);
    } // end closure for configure() functions
}
