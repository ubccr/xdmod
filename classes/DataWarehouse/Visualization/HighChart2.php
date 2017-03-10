<?php
namespace DataWarehouse\Visualization;

use DataWarehouse;
use DataWarehouse\RoleRestrictionsStringBuilder;

/*
*
* @author Amin Ghadersohi
* @author Jeanette Sperhac
* @date 2012-05-03
* @date 2015-06-xx
*
* Base class for server side generated highcharts charts
*/
class HighChart2
{
    protected $_swapXY;
    protected $_chart;
    protected $_width;
    protected $_height;
    protected $_scale;//deprecated

    protected $_aggregationUnit;
    protected $_startDate;
    protected $_endDate;

    protected $_total;

    protected $_dashStyles = array(
        'Solid',
        'ShortDash',
        'ShortDot',
        'ShortDashDot',
        'ShortDashDotDot',
        'Dot',
        'Dash',
        'LongDash',
        'DashDot',
        'LongDashDot',
        'LongDashDotDot'
    );

    protected $_dashStyleCount = 11;

    // we love instance variables: JMS
    protected $_shareYAxis;
    protected $_hideTooltip;
    protected $_showContextMenu;
    protected $_showWarnings;
    protected $_dataset; // ComplexDataset object, a collection of Simple*Datasets

    protected $_xAxisDataObject; // SimpleData object
    protected $_xAxisLabel;
    protected $_multiCategory = false;
    protected $_queryType = 'aggregate';

    protected $_subtitleText = '';

    /**
     * The role restrictions string builder used by this chart.
     *
     * @var DataWarehouse\RoleRestrictionsStringBuilder
     */
    protected $roleRestrictionsStringBuilder;

    // ---------------------------------------------------------
    // __construct()
    //
    // Constructor for HighChart2 class.
    //
    // ---------------------------------------------------------
    public function __construct(
        $aggregation_unit,
        $start_date,
        $end_date,
        $scale,
        $width,
        $height,
        $swap_xy = false,
        $showContextMenu = true,
        $share_y_axis = false,
        $hide_tooltip = false,
        $min_aggregation_unit = null,
        $showWarnings = true
    ) {

        $this->setDuration($start_date, $end_date, $aggregation_unit, $min_aggregation_unit);

        $this->_width = $width *$scale;
        $this->_height = $height *$scale;
        $this->_scale = 1; //deprecated

        $this->_swapXY = $swap_xy;
        $this->_shareYAxis = $share_y_axis;
        $this->_hideTooltip = $hide_tooltip;

        $this->_total = 0;
        $this->_showContextMenu = $showContextMenu;
        $this->_showWarnings = $showWarnings;
        $this->_chart = array(
            'chart' => array(
                'inverted' => $this->_swapXY,
                'zoomType' => 'xy',
                'alignTicks' => false,
                'showWarnings' => $this->_showWarnings,
            ),
            'credits' => array(
                'text' => $this->_startDate.' to '. $this->_endDate.'. Powered by XDMoD/Highcharts',
                'href' => ''
            ),
            'title' => array(
                'text' => ''
            ),
            'subtitle' => array(
                'text' => ''
            ),
            'xAxis' => array(
                'categories' => array()
            ),
            'yAxis' => array(),
            'legend' => array(
                'symbolWidth' => 40,
                'backgroundColor' => '#FFFFFF',
                'borderWidth' => 0,
                'y' => -5,
                'labelFormatter' => "function()
				{
					var ret = '';
					var x = this.name;
					var indexOfSQ = x.indexOf(']');
					var brAlready = false;
					if( indexOfSQ > 0)
					{
						ret += x.substring(0,indexOfSQ+1)+'<br/>';
						 x = x.substring(indexOfSQ+1,x.length);
						brAlready = true;
					}
					var indexOfBr = x.indexOf('{');
					if( indexOfBr > 0 && !brAlready)
					{
						 ret += x.substring(0,indexOfBr)+'<br/>';
						 x = x.substring(indexOfBr,x.length);
					}
					ret+=x.wordWrap(50,'<br/>');
					return ret;
				}
				"
            ),
            'series' => array(),
            'tooltip' => array(
                'enabled' => $this->_hideTooltip?false:true,
                'crosshairs' => true,
                'shared' => true,
                'xDateFormat' => $this->getDateFormat()
            ),
            'plotOptions' => array(
                'series' => array(
                    'allowPointSelect' => false,
                    'connectNulls' => false,
                    'animation' => false
                )
            ),
            'dimensions' => array(),
            'metrics' => array(),
            'exporting' => array(
                'enabled' => false
            )
        );

        $this->roleRestrictionsStringBuilder = new RoleRestrictionsStringBuilder();
    } // __construct()

    // ---------------------------------------------------------
    // getPointInterval()
    //
    // Determine point interval for chart points, given
    // selected aggregation unit.
    //
    // Returns pointInterval as milliseconds for javascript
    // Used by the HighChartTimeseries2 class.
    //
    // Note: Javascript/display related
    // ---------------------------------------------------------
    public function getPointInterval()
    {
        switch($this->_aggregationUnit)
        {
            case 'Month':
            case 'month':
                $pointInterval = 28;
                break;
            case 'Quarter':
            case 'quarter':
                $pointInterval = 88;
                break;
            case 'Year':
            case 'year':
                $pointInterval = 364;
                break;
            default:
            case 'Day':
            case 'day':
                $pointInterval = 1;
                break;
        }
        return $pointInterval * 24 * 3600 * 1000;
    } // getPointInterval()

    // ---------------------------------------------------------
    // setDuration()
    //
    // Format and adjust start and end date for chart, given
    // selected aggregation unit.
    //
    // Note: Data related
    // ---------------------------------------------------------
    public function setDuration($startDate, $endDate, $aggregationUnit, $min_aggregation_unit = null)
    {
        $this->_aggregationUnit = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName(
            $aggregationUnit,
            $startDate,
            $endDate,
            $min_aggregation_unit
        );

        $s = new \DateTime($startDate);
        $e = new \DateTime($endDate);
        switch($this->_aggregationUnit)
        {
            case 'Month':
            case 'month':
                $startDate = $s->format('Y-m-01');
                $endDate = $e->format('Y-m-t');
                break;
            case 'Quarter':
            case 'quarter':
                $sm = $s->format('m') - 1;
                $sy = $s->format('Y');
                $sq = floor($sm / 3);
                $s->setDate($sy, ($sq * 3) + 1, 1);

                $em = $e->format('m') - 1;
                $ey = $e->format('Y');
                $eq = floor($em / 3);
                $e->setDate($ey, ($eq * 3) + 3, 1);

                $startDate = $s->format('Y-m-d');
                $endDate = $e->format('Y-m-t');
                break;
            case 'Year':
            case 'year':
                $startDate = $s->format('Y-01-01');
                $endDate = $e->format('Y-12-31');
                break;
            default:
            case 'Day':
            case 'day':
                //no need to adjust
                break;
        }

        $this->_startDate = $startDate;
        $this->_endDate = $endDate;
    } // setDuration()

    // ---------------------------------------------------------
    // getDateFormat()
    //
    // Determine appropriate date format given selected aggregation unit.
    //
    // ---------------------------------------------------------
    public function getDateFormat()
    {
        switch($this->_aggregationUnit)
        {
            case 'Month':
            case 'month':
                $format = '%b %Y';
                break;
            case 'Quarter':
            case 'quarter':
                $format = 'Q%Q %Y';
                break;
            case 'Year':
            case 'year':
                $format = '%Y';
                break;
            default:
            case 'Day':
            case 'day':
                $format = '%Y-%m-%d';
                break;
        }
        return $format;
    } // getDateFormat()

    // ---------------------------------------------------------
    // setDataSource()
    //
    // Set chart data source information for display at bottom
    // of plotted chart.
    //
    // ---------------------------------------------------------
    public function setDataSource(array $source)
    {
        $src = count($source) > 0? ' Src: '.implode(', ', $source).'.':'';
        $this->_chart['credits']['text'] = $this->_startDate.' to '.
            $this->_endDate.' '.$src.' Powered by XDMoD/Highcharts';
    } // setDataSource()

    // ---------------------------------------------------------
    // getTitle()
    //
    // Get and return chart title as text.
    //
    // ---------------------------------------------------------
    public function getTitle()
    {
        return $this->_chart['title']['text'];
    } // getTitle()

    // ---------------------------------------------------------
    // setTitle()
    //
    // Set chart's title text and highcharts-specific title formatting.
    //
    // ---------------------------------------------------------
    public function setTitle($title, $font_size = 3)
    {
        $this->_chart['title']['text'] = $title;
        $this->_chart['title']['style'] = array(
            'color'=> '#000000',
            'fontSize' => (16 + $font_size).'px'
        );
    } // setTitle()

    /**
     * @return The chart subtitle encoded as plain text.
     */
    public function getSubtitleText()
    {
        return $this->_subtitleText;
    }

    /**
     * Set chart's subtitle text and highcharts-specific subtitle formatting.
     * The text is automatically word wrapped based on the chart settings.
     *
     * @param $subtitle_html The charts subtitle in HTML format
     * @param $font_size The font size for the subtitle plus 12 px
     */
    public function setSubtitle($subtitle_html, $font_size = 3)
    {
        if($subtitle_html !== null) {
            $this->_subtitleText = html_entity_decode($subtitle_html);
            $this->_chart['subtitle']['text'] = $subtitle_html;
        } else {
            $this->_subtitleText = '';
            $this->_chart['subtitle']['text'] = '';
        }
        $this->_chart['subtitle']['style'] = array(
            'color'=> '#5078a0',
            'fontSize' => (12 + $font_size).'px'
        );
        $this->_chart['subtitle']['y'] = 30 + $font_size;
    } // setSubtitle()

    // ---------------------------------------------------------
    // setLegend()
    //
    // Set chart's legend location and format based on user's
    // location selection.
    //
    // ---------------------------------------------------------
    public function setLegend($legend_location, $font_size = 3)
    {
        $this->_chart['legend']['itemStyle'] = array(
            'fontWeight' => 'normal',
            'color' => '#274b6d',
            'fontSize' => (12  + $font_size).'px'
        );
        $this->_legend_location = $legend_location;
        switch($legend_location)
        {
            case 'top_center':
                $this->_chart['legend']['align'] = 'center';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $pad = 0;
                if($this->_chart['title']['text'] != '')
                {
                    $pad += 30;
                }
                if($this->_chart['subtitle']['text'] != '')
                {
                    $pad += 20;
                }
                $this->_chart['legend']['y'] = $pad;

                break;
            //case 'bottom_right':
            //break;
            //case 'bottom_left':
            //break;
            case 'left_center':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'middle';
                $this->_chart['legend']['layout'] = 'vertical';
                break;

            case 'left_top':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'left_bottom':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'bottom';
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'right_center':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'middle';
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'right_top':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'right_bottom':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'bottom';
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_bottom_center':
                $this->_chart['legend']['align'] = 'center';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['y'] = -100;
                break;
            case 'floating_top_center':
                $this->_chart['legend']['align'] = 'center';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['y'] = 70;
                break;
            case 'floating_left_center':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'middle';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = 80;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_left_top':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = 80;
                $this->_chart['legend']['y'] = 70;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_left_bottom':
                $this->_chart['legend']['align'] = 'left';
                $this->_chart['legend']['verticalAlign'] = 'bottom';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = 80;
                $this->_chart['legend']['y'] = -100;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_right_center':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'middle';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = -10;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_right_top':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'top';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = -10;
                $this->_chart['legend']['y'] = 70;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case 'floating_right_bottom':
                $this->_chart['legend']['align'] = 'right';
                $this->_chart['legend']['verticalAlign'] = 'bottom';
                $this->_chart['legend']['floating'] = true;
                $this->_chart['legend']['x'] = -10;
                $this->_chart['legend']['y'] = -100;
                $this->_chart['legend']['layout'] = 'vertical';
                break;
            case '':
            case 'none':
            case 'off':
                $this->_legend_location = 'off';
                $this->_chart['legend']['enabled'] = false;
                break;
            case 'bottom_center':
            default:
                $this->_legend_location = 'bottom_center';
                $this->_chart['legend']['align'] = 'center';
                $this->_chart['legend']['margin'] = 15;
                break;
        }
        if($legend_location != 'bottom_center')
        {
            $this->_chart['chart']['spacingBottom'] = 25;
        }
        if($legend_location != 'right_center' &&
             $legend_location != 'right_top' &&
             $legend_location != 'right_bottom')
        {
            $this->_chart['chart']['spacingRight'] = 20;
        }
        $this->_hasLegend = $this->_legend_location != 'off';

    } // setLegend()

    // ---------------------------------------------------------
    // registerContextMenus()
    //
    // Set chart's context menus in javascript, as chart
    // parameters. Not ideal.
    //
    // ---------------------------------------------------------
    protected function registerContextMenus()
    {
        if($this->_showContextMenu)
        {
            $this->_chart['chart']['events'] = array(
                'titleClick' => 'function(event){return XDMoD.Module.MetricExplorer.titleContextMenu(event);}',
                'subtitleClick' => 'function(event){return XDMoD.Module.MetricExplorer.subtitleContextMenu(event);}',
                'xAxisClick' => 'function(axis){return XDMoD.Module.MetricExplorer.xAxisContextMenu(axis);}',
                'yAxisClick' => 'function(axis){return XDMoD.Module.MetricExplorer.yAxisContextMenu(axis);}',
                'xAxisTitleClick' => 'function(axis){return XDMoD.Module.MetricExplorer.xAxisTitleContextMenu(axis);}',
                'yAxisTitleClick' => 'function(axis){return XDMoD.Module.MetricExplorer.yAxisTitleContextMenu(axis);}',
                'xAxisLabelClick' => 'function(axis){return XDMoD.Module.MetricExplorer.xAxisLabelContextMenu(axis);}',
                'yAxisLabelClick' => 'function(axis){return XDMoD.Module.MetricExplorer.yAxisLabelContextMenu(axis);}',
                'click' => 'function(event){return XDMoD.Module.MetricExplorer.chartContextMenu.call(this,event);}'
            );
        }
    } // registerContextMenus()

    // ---------------------------------------------------------
    // setMultiCategory()
    //
    // Determine whether plot contains multiple categories; set
    // _multiCategory instance variable.
    //
    // called by configure()
    // JMS, June 2015
    //
    // @param data_series
    // ---------------------------------------------------------
    protected function setMultiCategory(&$data_series)
    {
        // For each data series, check if the category matches the category of
        // the previous data series. If not, there is more than one category
        // being used in this plot.
        foreach($data_series as $data_description_index => $data_description)
        {
            $category = DataWarehouse::getCategoryForRealm(
                $data_description->realm
            );
            if(isset($prevCategory) && $category != $prevCategory)
            {
                $this->_multiCategory = true;
                return;
            } else {
                $prevCategory = $category;
            }
        }
        $this->_multiCategory = false;
    } // setMultiCategory()

    // ---------------------------------------------------------
    // setXAxisLabel()
    //
    // Set XAxis label. TODO: Take another look
    //
    // called by configure()
    // JMS, June 2015
    //
    // @param x_axis
    // ---------------------------------------------------------
    /*
	protected function setXAxisLabel($x_axis)
	{
			if (!isset($this->_xAxisDataObject) )  {
					throw new \Exception( get_class($this)." _xAxisDataObject not set ");
			}

			$defaultXAxisLabel = 'xAxis';
			$xAxisLabel = $this->_xAxisDataObject->getName() == ORGANIZATION_NAME ? '' :
										$this->_xAxisDataObject->getName();

			if($xAxisLabel == '') $xAxisLabel = $defaultXAxisLabel;
			$originalXAxisLabel = $xAxisLabel;

			if(isset($x_axis->{$xAxisLabel}))
			{
					$config = $x_axis->{$xAxisLabel};
					if(isset($config->title)) $xAxisLabel = $config->title;
			}
			if($xAxisLabel == $defaultXAxisLabel) $xAxisLabel = '';

			$this->_xAxisLabel = $xAxisLabel;
	} // setXAxisLabel()
	*/

    // ---------------------------------------------------------
    // setXAxis()
    //
    // Set XAxis in _chart object. TODO: Take another look
    //
    // called by configure()
    // JMS, June 2015
    //
    // @param x_axis
    // ---------------------------------------------------------
    protected function setXAxis(&$x_axis, $font_size)
    {
        if (!isset($this->_xAxisDataObject) )  {
            throw new \Exception(get_class($this)." _xAxisDataObject not set ");
        }

        // part from setXAxisLabel(), which we may or may not keep.
        // if we need the default and original, guess we should keep this.
        // otherwise, just call it (see above)
        $defaultXAxisLabel = 'xAxis';
        $xAxisLabel = $this->_xAxisDataObject->getName() == ORGANIZATION_NAME ? '' :
                                    $this->_xAxisDataObject->getName();

        if($xAxisLabel == '')
        {
            $xAxisLabel = $defaultXAxisLabel;
        }
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

        // --- set xAxis in _chart object ---
        $this->_chart['xAxis'] = array(
                'title' => array(
                        'text' => $xAxisLabel,
                        'margin' => 15 + $font_size,
                        'style' => array(
                                'color'=> '#000000',
                                'fontWeight'=> 'bold',
                                'fontSize' => (12 + $font_size).'px'
                        )
                ),
                'otitle' => $originalXAxisLabel,
                'dtitle' => $defaultXAxisLabel,

                // set chart labels:
                'labels' => $this->_swapXY ? array(
                        'enabled' => true,
                        'staggerLines' => 1,
                        'step' => $this->_xAxisDataObject->getCount() < 20 ? 0  :round($this->_xAxisDataObject->getCount() / 20 ),
                        'style' => array(
                                'fontWeight'=> 'normal',
                                'fontSize' => (11 + $font_size).'px'
                        ),
                        'formatter' => "function(){
								var maxL = ".floor($this->_width*20/580).";
								var x  = (this.value.length>maxL-3)?this.value.substring(0,maxL-3)+'...':this.value;
								return x;
						}
						" //x.wordWrap(".floor($this->_width*($this->limit<11?22:22)/580).",'<br/>');
                ) // !($this->_swapXY)
                : array(
                        'enabled' => true,
                        'staggerLines' => 1,
                        'rotation' => $this->_xAxisDataObject->getCount()<= 8?0: -90,
                        'align' => $this->_xAxisDataObject->getCount()<= 8?'center':'right',
                        'step' => $this->_xAxisDataObject->getCount()< 20?0:round($this->_xAxisDataObject->getCount()/20),
                        'style' => array('fontSize' => (11 + $font_size).'px'),
                        'formatter' => "function(){
								var maxL = ".floor($this->_height*($this->limit<11?30:15)/400).";
								var x  = (this.value.length>maxL-3)?this.value.substring(0,maxL-3)+'...':''+this.value;
								return x.wordWrap(".floor($this->_height*($this->limit<11?18:18)/400).",'<br/>');
						}
						"
                ),
                'lineWidth' => 2 + $font_size / 4,
                'categories' => $this->_xAxisDataObject->getValues()
        );
    }   // setXAxis()

    // ---------------------------------------------------------
    // setAxes()
    //
    // Set xAxis obj and yAxis data array for plotting. Account for summarizeDataseries
    // parameter to enable pre-truncation of dataset for summarization.
    //
    // supports: multiple y axes,
    //      multiple dataseries,
    //      standard error labels
    //      truncation is done in the SQL itself (or in PHP for Usage),
    //      complex dataset
    //      user interactivity
    //      average, min, max, count of N others
    //
    // Used by configure()
    //
    // @return yAxisArray
    //
    // ---------------------------------------------------------
    protected function setAxes($summarizeDataseries, $offset, $x_axis, $font_size)
    {
        //  ----------- set up xAxis and yAxis, assign to chart -----------

        // Enable summarization/truncation of datasets:
        // Usage tab coexistence hack: If summarizeDataseries is true (Usage/Metric
        // Explorer pie charts), set X axis and Y axis contents
        // without specifying a limit on count of records returned from query.
        // If ME and non-pie, use $this->limit (default 10) to enable paging thru dataset.
        $queryLim = $summarizeDataseries ? null : $this->limit;

        $this->_xAxisDataObject = $this->_dataset->getXAxis(false, $queryLim, $offset);
        $this->_total = $this->_dataset->getTotalX(); //has to be called after getXAxis

        //$this->setXAxisLabel($x_axis);
        $this->setXAxis($x_axis, $font_size, $queryLim );

        $yAxisArray = $this->_dataset->getYAxis($queryLim, $offset, $this->_shareYAxis);
        return $yAxisArray;
    }

    // ---------------------------------------------------------
    // configure()
    //
    // Given chart data series and parameters, build ComplexDataset,
    // set all needed chart parameters.
    //
    // supports: multiple y axes,
    //      multiple dataseries,
    //      standard error labels
    //      truncation is done in the SQL itself (or in PHP for Usage),
    //      complex dataset
    //      user interactivity
    //      average, min, max, count of N others
    //
    // Used by MetricExplorer, Summary, AppKernel, CustomQueries, Usage, etc.
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
        // default 10 from Metric Explorer
        $offset = null,
        $summarizeDataseries = false
    ) {   // JMS: clearly we do not have enough parameters.
                                        // support min/max/average 'truncation' of dataset


        $this->limit = $limit;
        $this->show_filters = $show_filters;
        $this->setMultiCategory($data_series);
        $this->registerContextMenus();

        // Instantiate the color generator:
        $colorGenerator = new \DataWarehouse\Visualization\ColorGenerator();

        // Instantiate the ComplexDataset to plot
        $this->_dataset = new \DataWarehouse\Data\ComplexDataset();

        list(
            $dimensions,
            $metrics,
            //$yAxisArray,
            $globalFilterDescriptions,
            $dataSources
        ) = $this->_dataset->init(
            $this->_startDate,
            $this->_endDate,
            $this->_aggregationUnit,
            $data_series,
            $global_filters,
            $this->_queryType
        );

        $this->_chart['metrics'] = $metrics;
        $this->_chart['dimensions'] = $dimensions;
        $this->setDataSource(array_keys($dataSources));

        if($this->show_filters)
        {
            $subtitle_separator = " -- ";
            $subtitle = implode($subtitle_separator, array_unique($globalFilterDescriptions));
            $this->setSubtitle($subtitle, $font_size);
        }

        //  ----------- set up xAxis and yAxis, assign to chart -----------

        $yAxisArray = $this->setAxes($summarizeDataseries, $offset, $x_axis, $font_size);
        $yAxisCount = count($yAxisArray);

        // ------------ prepare to plot ------------

        // --- If ME, Does any dataset specify a pie chart? If so, set to truncate/summarize -- //
        // This corrects the 'pie charts are wrong' bug...
        if ( !$summarizeDataseries )
        {
            foreach($yAxisArray as $yAxisIndex => $yAxisObject)
            {
                foreach ($yAxisObject->series as $data_object)
                {
                    if ( $data_object['data_description']->display_type=='pie' )
                    {
                        // set to summarize, then break both loops.
                        $summarizeDataseries = true;
                        continue 2;
                    }
                }
            } // foreach($yAxisArray ...

            // if we have just reset summarizeDatasets, recompute limit, xAxis, and yAxis
            // with the new limit in place. Reset dataset _total to 1 to disable paging.
            if ($summarizeDataseries)
            {
                $yAxisArray = $this->setAxes($summarizeDataseries, $offset, $x_axis, $font_size);
                $yAxisCount = count($yAxisArray);

                // disable ME paging by setting dataset size to 1
                $this->_total = 1;
            }
        } // !$summarizeDatasets

        // OK, now let's plot something...
        // Each of the yAxisObjects returned from getYAxis()
        foreach($yAxisArray as $yAxisIndex => $yAxisObject)
        {
            if(count($yAxisObject->series) < 1)
            {
                continue;
            }

            $first_data_description = $yAxisObject->series[0]['data_description'];

            // set initial dataset's plot color:
            $yAxisColorValue = ($first_data_description->color == 'auto' || $first_data_description->color == 'FFFFFF')
                                                                ? $colorGenerator->getColor()
                                                                : $colorGenerator->getConfigColor(hexdec($first_data_description->color) );
            $yAxisColor = '#'.str_pad(dechex($yAxisColorValue), 6, '0', STR_PAD_LEFT);

            // set axis labels
            $defaultYAxisLabel = 'yAxis'.$yAxisIndex;
            $yAxisLabel = $yAxisObject->title;
            if($this->_shareYAxis)
            {
                $yAxisLabel = $defaultYAxisLabel;
            }
            $originalYAxisLabel = $yAxisLabel;

            $yAxisMin = $first_data_description->log_scale?null:0;
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
                    $yAxisMin = $first_data_description->log_scale && $config->min <= 0?null:$config->min;
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

            // populate the yAxis:
            $yAxis = array(
                'title' => array(
                    'text' => $yAxisLabel ,
                    'style' => array(
                        'color'=> $yAxisColor,
                        'fontWeight'=> 'bold',
                        'fontSize' => (12 + $font_size).'px'
                    )
                ),
                'otitle' => $originalYAxisLabel,
                'dtitle' => $defaultYAxisLabel,
                'labels' => array(
                    'style' => array(
                        'fontWeight'=> 'normal',
                        'fontSize' => (11 + $font_size).'px'
                    )
                ),
                'startOnTick' => $yAxisMin == null,
                'endOnTick' => $yAxisMax == null,
                'opposite' => $yAxisIndex % 2 == 1,
                'min' => $yAxisMin,
                'max' => $yAxisMax,
                'type' => $yAxisObject->log_scale? 'logarithmic' : 'linear',
                'showLastLabel' => $this->_chart['title']['text'] != '',
                'gridLineWidth' => $yAxisCount > 1 ?0: 1 + ($font_size/8),
                'lineWidth' => 2 + $font_size/4,
                'allowDecimals' => $yAxisObject->decimals > 0,
                'tickInterval' => $yAxisObject->log_scale ?1:null,
                'maxPadding' => max(0.05, ($yAxisObject->value_labels?0.25:0.0) + ($yAxisObject->std_err?.25:0))
            );

            $this->_chart['yAxis'][] = $yAxis;

            // for each of the dataseries on this yAxisObject:
            foreach($yAxisObject->series as $data_description_index => $yAxisDataObjectAndDescription)
            {
                $yAxisDataObject = $yAxisDataObjectAndDescription['yAxisDataObject']; // SimpleData object
                $data_description = $yAxisDataObjectAndDescription['data_description'];
                $decimals = $yAxisDataObjectAndDescription['decimals'];
                $semDecimals = $yAxisDataObjectAndDescription['semDecimals'];
                $filterParametersTitle =  $yAxisDataObjectAndDescription['filterParametersTitle'];

                // ============= truncate (e.g. take average, min, max, sum of remaining values) ==========

                // Usage tab charts and ME pie charts will have their datasets summarized as follows:
                $dataSeriesSummarized = false;
                if ( $summarizeDataseries )
                {
                        $valuesCount = $yAxisDataObject->getCount(true );

                    if($valuesCount - $this->limit >= 1)
                        {
                        // only compute average (2nd parameter) if not drawing pie chart
                        $yAxisDataObject->truncate($this->limit, $data_description->display_type!=='pie');

                        // now merge the x labels, if needed, from multiple datasets:
                        $mergedlabels = $yAxisDataObject->getXValues();
                        foreach( $mergedlabels as $idx => $label) {
                            if ($label === null) {
                                $tmp = $this->_xAxisDataObject->getValues();
                                $mergedlabels[$idx] = $tmp[$idx];
                            }
                        }
                        $this->_xAxisDataObject->setValues($mergedlabels);
                        $this->_xAxisDataObject->getCount(true);
                        $this->setXAxis($x_axis, $font_size);
                        $dataSeriesSummarized = true;
                    }
                } // summarizeDataseries

                //  ================ set color ================

                // If the first data set in the series, pick up the yAxisColorValue.
                if($data_description_index == 0)
                {
                        $color_value = $yAxisColorValue;
                } else {
                        $color_value = $colorGenerator->getColor();
                }
                $color = '#'.str_pad(dechex($color_value), 6, '0', STR_PAD_LEFT);
                $lineColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color_value, -70)), 6, '0', STR_PAD_LEFT);

                $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;
                $dataLabelsConfig = array(
                    'enabled' => $data_description->value_labels || $std_err_labels_enabled,
                    'style' => array(
                        'fontSize' => (11 + $font_size).'px',
                        'fontWeight' => 'normal',
                        'color' => $color,
                        'textShadow' => false
                    )
                );

                $tooltipConfig = array();
                $values = array();

                // to display as pie chart:
                if($data_description->display_type == 'pie')
                {
                    $this->_chart['chart']['inverted'] = false;

                    foreach( $yAxisDataObject->getValues() as $index => $value)
                    {
                        // If the first value, give it the yAxisColor so we don't skip
                        // that color in the dataset. Otherwise, pick the next color.
                        $point = array(
                            'name' => $this->_xAxisDataObject->getValue($index),
                            'y' => $value,
                            'color' => ($index == 0) ? $yAxisColor
                                    : '#'.str_pad(dechex($colorGenerator->getColor() ), 6, '0', STR_PAD_LEFT)
                        );

                        // N.B.: These are drilldown labels.
                        // X axis labels will be the same, but are plotted
                        // from the x axis object instance variable.
                        // See setXAxis() and _xAxisDataObject.
                        $point['drilldown'] = array(
                            'id' => $yAxisDataObject->getXId($index),
                            'label' => $yAxisDataObject->getXValue($index)
                        );
                        $values[] = $point;

                    } // foreach

                    $dataLabelsConfig  = array_merge(
                        $dataLabelsConfig,
                        array(
                            'color' => '#000000',
                            'formatter' => "function(){
								var maxL = ".floor($this->_width*($this->limit<11?30:15)/580).";
								var x = (this.point.name.length>maxL+3)?this.point.name.substring(0,maxL-3)+'...':this.point.name;
								return '<b>'+x.wordWrap( ".floor($this->_width*($this->limit<11?15:15)/580).
                                            ",'</b><br/><b>')+'</b><br/>'+Highcharts.numberFormat(this.y, $decimals);
							}"
                         )
                    );

                    $tooltipConfig = array_merge(
                        $tooltipConfig,
                        array(
                            'pointFormat' => "{series.name}: {point.y} <b>({point.percentage:.1f}%)</b>",
                            'percentageDecimals' => 1,
                            'valueDecimals' => $decimals
                        )
                    );

                    $this->_chart['tooltip']['shared'] = false;
                }
                else // ($data_description->display_type !== 'pie')
                {
                    if ($data_description->value_labels && $std_err_labels_enabled) {
                        $dataLabelsConfig['formatter'] = "function(){ return Highcharts.numberFormat(this.y, $decimals)+' [+/-'+Highcharts.numberFormat(this.percentage, $decimals)+']';}";
                    } elseif ($std_err_labels_enabled) {
                        $dataLabelsConfig['formatter'] = "function(){ return '+/-'+Highcharts.numberFormat(this.percentage, $decimals);}";
                    } else {
                        $dataLabelsConfig['formatter'] = "function(){ return Highcharts.numberFormat(this.y, $decimals);}";
                    }

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
                    else // (!$this->_swapXY)
                    {
                        $dataLabelsConfig  = array_merge(
                            $dataLabelsConfig,
                            array(
                                'rotation' => -90,
                                'align' => 'center',
                                'y' => -70,
                            )
                        );
                    } // _swapXY

                    // set the label for each value:
                    foreach( $yAxisDataObject->getValues() as $index => $value)
                    {
                        $point = array(
                            'y' => $yAxisObject->log_scale && $value == 0 ? null : $value//,
                        );

                        // N.B.: The following are drilldown labels.
                        // Labels on the x axis come from the x axis object
                        // (Though they are the same labels...)
                        $point['drilldown'] = array(
                            'id' => $yAxisDataObject->getXId($index),
                            'label' => $yAxisDataObject->getXValue($index)
                        );

                        try {
                            $point['percentage'] = $yAxisDataObject->getError($index);
                        } catch (\Exception $e) {

                        }

                        $values[] = $point ;
                    } // foreach

                    $tooltipConfig = array_merge(
                        $tooltipConfig,
                        array(
                            'valueDecimals' => $decimals
                        )
                    );
                } // if ($data_description->display_type == 'pie')

                $values_count = count($values);
                if ($dataSeriesSummarized && $values_count > 0) {
                    $values[$values_count - 1]['isRemainder'] = true;
                }

                $zIndex = isset($data_description->z_index) ? $data_description->z_index : $data_description_index;

                $dataSeriesName = $yAxisDataObject->getName();
                if ($data_description->restrictedByRoles && $this->_showWarnings) {
                    $dataSeriesName .= $this->roleRestrictionsStringBuilder->registerRoleRestrictions($data_description->roleRestrictionsParameters);
                }
                if($this->_multiCategory) {
                    $dataSeriesName = (
                        DataWarehouse::getCategoryForRealm($data_description->realm)
                        . ': '
                        . $dataSeriesName
                    );
                }

                $formattedDataSeriesName = str_replace('style=""', 'style="color:'.$yAxisColor.'"', $dataSeriesName);
                $formattedDataSeriesName = str_replace('y_axis_title', $yAxisLabel, $formattedDataSeriesName);
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
                $seriesClick = 'function(event){'.($this->_showContextMenu?'XDMoD.Module.MetricExplorer.seriesContextMenu(this,false,'.
                            $data_description->id.');':'').'}';
                $legendItemClick = $this->_showContextMenu?'function(event){XDMoD.Module.MetricExplorer.seriesContextMenu(this,true,'.
                            $data_description->id.'); return false;}':'function(event){return true;}';
                $pointClick = 'function(event){'.($this->_showContextMenu?'XDMoD.Module.MetricExplorer.pointContextMenu(this,'.
                            $data_description->id.');':'').'}';

                $data_series_desc = array(
                    'name' => $lookupDataSeriesName,
                    'otitle' => $formattedDataSeriesName,
                    'datasetId' => $data_description->id,
                    'zIndex' => $zIndex,
                    'color'=> $data_description->display_type == 'pie' ?
                                                        null : $color,
                    'type' => $data_description->display_type,
                    'trackByArea'=>  $data_description->display_type == 'area' ||
                                                        $data_description->display_type == 'areaspline',
                    'dashStyle' => $data_description->line_type,
                    'shadow' => $data_description->shadow,
                    'groupPadding' => 0.05,
                    'pointPadding' => 0,
                    'borderWidth' => 0,
                    'yAxis' => $yAxisIndex,
                    'lineWidth' => $data_description->display_type !== 'scatter' ?
                                                        $data_description->line_width + $font_size / 4 : 0,
                    'marker' => array(
                        'enabled' => $data_description->display_type == 'scatter' ||
                                                        ($values_count < 21 &&
                                                        $this->_width > \DataWarehouse\Visualization::$thumbnail_width),
                        'lineWidth' => 1,
                        'lineColor' => $lineColor,
                        'radius' => $font_size / 4 + 5
                    ),
                    'tooltip' => $tooltipConfig,
                    'showInLegend' => true,
                    'dataLabels' => $dataLabelsConfig,
                    'data' => $values,
                    'cursor' => 'pointer',
                    'visible' => $visible,
                    'events' => array(
                        'legendItemClick' => $legendItemClick
                    ),
                    'point' => array(
                        'events' => array(
                            'click' => $pointClick
                        )
                    ),
                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                ); // $data_series_desc

                                // set stacking
                if($data_description->display_type!=='line')
                {
                    if(     $data_description->combine_type=='stack')
                    {
                        // ask the highcharts library to connect nulls for stacking
                        $data_series_desc['stacking'] = 'normal';
                        $data_series_desc['connectNulls'] = true;
                    }
                    elseif($data_description->combine_type=='percent' && !$data_description->log_scale)
                    {
                        $data_series_desc['stacking'] = 'percent';
                    }
                }
                $this->_chart['series'][] = $data_series_desc;

                // build error data series and add it to chart
                $this->buildErrorDataSeries(
                    $data_description,
                    $legend,
                    $color_value,
                    $yAxisDataObject,
                    $formattedDataSeriesName,
                    $yAxisIndex,
                    $semDecimals,
                    $zIndex,
                    $legendItemClick,
                    $pointClick
                );

            } // foreach($yAxisObject->series as $data_description_index => $yAxisDataObjectAndDescription)
        }

        if ($this->_showWarnings) {
            $this->addRestrictedDataWarning();
        }

        $this->addChartExtensions();

        // set title and subtitle for chart
        $this->setChartTitleSubtitle($font_size);

    } // configure()

    // ---------------------------------------------------------
    // buildErrorDataSeries()
    //
    // @param ...
    //
    // for Timeseries, questions include: drilldown?
    // ---------------------------------------------------------
    protected function buildErrorDataSeries(
        &$data_description,
        &$legend,
        $color_value,
        &$yAxisDataObject,
        $formattedDataSeriesName,
        $yAxisIndex,
        $semDecimals,
        $zIndex,
        $legendItemClick,
        $pointClick
    ) {

        // build error data series and add it to chart
        if($data_description->std_err == 1 && $data_description->display_type != 'pie')
        {
            $error_color_value = \DataWarehouse\Visualization::alterBrightness($color_value, -70);
            $error_color = '#'.str_pad(dechex($error_color_value), 6, '0', STR_PAD_LEFT);

            $error_series = array();
            $errorCount = $yAxisDataObject->getErrorCount();
            for($i = 0; $i < $errorCount; $i++)
            {
                // build the error bar and set for display
                $v = $yAxisDataObject->getValue($i);
                $e = $yAxisDataObject->getError($i);
                $has_value = isset($v) && $v != 0;
                $error_series[] = array(
                            'x' => $i,
                            'low' => $has_value ? $v-$e : null,
                            'high' => $has_value ? $v+$e : null,
                            'stderr' => $e
                );
            }

            // -- set error dataseries name and visibility --
            $dsn = 'Std Err: '.$formattedDataSeriesName;

            // update the name of the dataseries:
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

            $err_series_tooltip = "function() { ".
                "var fErr = Highcharts.numberFormat(this.stderr, $semDecimals); ".
                "return ".
                "'<span style=\"color:{$error_color}\">\u25CF</span> {$lookupDataSeriesName}: ".
                    "<b>+/-' + fErr + '</b><br/>';".
                "}";

            // create the data series description:
            $err_data_series_desc = array(
                //'name' => $dsn,
                'name' => $lookupDataSeriesName,
                'showInLegend' => true,
                'otitle' => $dsn,
                'datasetId' => $data_description->id,
                'zIndex' => $zIndex,
                'color'=> $error_color,
                'type' => 'errorbar',
                'shadow' => $data_description->shadow,
                'groupPadding' => 0.05,
                'pointPadding' => 0,
                'lineWidth' => 2,
                'yAxis' => $yAxisIndex,
                'tooltip' => array(
                        'pointFormatter' => $err_series_tooltip
                    ),
                'data' => $error_series,
                'cursor' => 'pointer',
                'visible' => $visible,
                'events' => array(
                    'legendItemClick' => $legendItemClick
                ),
                'point' => array(
                    'events' => array(
                        'click' => $pointClick
                        )
                    ),
                'isRestrictedByRoles' => $data_description->restrictedByRoles,
                );
            if(! $data_description->log_scale)
            {
                $this->_chart['series'][] = $err_data_series_desc;
            }
        } // if not pie
    } // function buildErrorDataSeries

        /**
         * Add extension functions to the chart.
         */
    protected function addChartExtensions() {
        // Allow for multiple event handlers for certain events.
        // Based on: https://stackoverflow.com/a/22337004
        $this->_chart['chart']['events']['load'] = 'function () {
					var eventHandlers = this.options.chart.events.loadHandlers;
					if (!eventHandlers) {
							return;
					}
					for (var i = 0; i < eventHandlers.length; i++) {
							eventHandlers[i].apply(this, arguments);
					}
			}';
        $this->_chart['chart']['events']['redraw'] = 'function () {
					var eventHandlers = this.options.chart.events.redrawHandlers;
					if (!eventHandlers) {
							return;
					}
					for (var i = 0; i < eventHandlers.length; i++) {
							eventHandlers[i].apply(this, arguments);
					}
			}';

        // Add function to add a background color to a chart element.
        // (This is for chart elements that don't support this natively.)
        // Idea for using rectangles as background color from: https://stackoverflow.com/a/21625239
        $this->_chart['chart']['events']['helperFunctions']['addBackgroundColor'] = 'function (element, color) {
					var xPadding = 3;
					var yPadding = 2;
					var rectCornerRadius = 1;

					var elementBBox = element.getBBox();
					return element.renderer.rect(
							elementBBox.x - xPadding,
							elementBBox.y - yPadding,
							elementBBox.width + (xPadding * 2),
							elementBBox.height + (yPadding * 2),
							rectCornerRadius
					).attr({
							fill: color,
							zIndex: (element.zIndex ? element.zIndex : 0) - 1
					}).add(element.parentGroup);
			}';

        // Add functions to handle aligned labels.
        // Based on: https://stackoverflow.com/a/19326076
        $this->_chart['chart']['events']['helperFunctions']['alignAlignedLabels'] = 'function (chart) {
					var alignedLabels = chart.alignedLabels;
					if (!alignedLabels) {
							return;
					}
					for (var i = 0; i < alignedLabels.length; i++) {
							var alignedLabel = alignedLabels[i];
							var labelObject = alignedLabel.label;
							var labelBackground = alignedLabel.background;

							labelObject.align(Highcharts.extend(
									labelObject.getBBox(),
									chart.options.alignedLabels.items[i]
							), null, chart.renderer.spacingBox);
							if (labelBackground) {
									labelBackground.align(Highcharts.extend(
											labelObject.getBBox(),
											chart.options.alignedLabels.items[i]
									), null, chart.renderer.spacingBox);
							}
					}
			}';
        $this->_chart['chart']['events']['loadHandlers'][] = 'function () {
					var chart = this;
					var alignedLabelsOptions = chart.options.alignedLabels;
					if (!alignedLabelsOptions) {
							return;
					}

					chart.alignedLabels = [];
					for (var i = 0; i < alignedLabelsOptions.items.length; i++) {
							var alignedLabel = {};
							var alignedLabelOptions = alignedLabelsOptions.items[i];
							alignedLabel.group = chart.renderer.g().add();
							alignedLabel.label = chart.renderer.label(alignedLabelOptions.html).add(alignedLabel.group);

							if (alignedLabelOptions.backgroundColor) {
									alignedLabel.background = chart.options.chart.events.helperFunctions.addBackgroundColor(alignedLabel.label, alignedLabelOptions.backgroundColor);
									alignedLabel.label.toFront();
							}
							chart.alignedLabels.push(alignedLabel);
					}
					chart.options.chart.events.helperFunctions.alignAlignedLabels(chart);
			}';
        $this->_chart['chart']['events']['redrawHandlers'][] = 'function () {
					var chart = this;
					chart.options.chart.events.helperFunctions.alignAlignedLabels(chart);
			}';

        // Add functions to style labels for data series restricted by roles.
        $this->_chart['chart']['events']['loadHandlers'][] = 'function () {
					var chart = this;
					if (!chart.series || chart.options.chart.showWarnings === false) {
							return;
					}

					for (var i = 0; i < chart.series.length; i++) {
							var series = chart.series[i];
							if (!series.options.isRestrictedByRoles) {
									continue;
							}

							chart.options.chart.events.helperFunctions.addBackgroundColor(series.legendItem, "#DFDFDF");
					}
			}';
    }

        /**
         * Add a warning to the chart that data may be restricted.
         */
    protected function addRestrictedDataWarning() {
        $roleRestrictionsStrings = $this->roleRestrictionsStringBuilder->getRoleRestrictionsStrings();
        if (empty($roleRestrictionsStrings)) {
                return;
        }

        $this->_chart['alignedLabels']['items'][] = array(
            'html' => implode('<br />', $roleRestrictionsStrings),

            'align' => 'left',
            'backgroundColor' => '#DFDFDF',
            'verticalAlign' => 'bottom',
            'y' => 15,
        );
    }

    // ---------------------------------------------------------
    // setChartTitleSubtitle()
    //
    // @param font_size
    //
    // ---------------------------------------------------------
    protected function setChartTitleSubtitle($font_size)
    {
        // set title and subtitle for chart
        if($this->_chart['title']['text'] == '' && $this->_chart['subtitle']['text'] != '')
        {
            $this->setTitle($this->_chart['subtitle']['text'], $font_size);
            $this->setSubtitle('', $font_size);
        }
    } // function setChartTitleSubtitle

    // ---------------------------------------------------------
    // exportJsonStore()
    //
    // Export chart object as JSON, indicate count.
    //
    // Note that $limit is not in use. Note also that I've made
    // it an instance variable here  --JMS, 1 Sep 15
    // ---------------------------------------------------------
    public function exportJsonStore(
        $limit = null,
        $offset = null
    ) {

        $returnData = array(
            'totalCount' => $this->_total,
            'success' => true,
            'message' => 'success',
            'data' => array(
                $this->_chart
            )
        );

        return $returnData;
    } // exportJsonStore()

    /**
     * @function getOriginalAxisDefn
     * @param $axis axis definition object
     * @param $origLabel The default value of the axis label
     * @param $axisIndex The axis index
     */
    protected function getAxisOverrides($axis, $origLabel, $axisIndex) {
        // XDMoD >= 5.6 overrides are keyed to the axis index
        if(isset($axis->{'original'.$axisIndex})) {
            return $axis->{'original'.$axisIndex};
        }
        // XDMoD < 5.6 stored config overrides indexed on the text string
        // of the axis label
        if(isset($axis->{$origLabel})) {
            return $axis->{$origLabel};
        }
        return null;
    }
} // class HighChart2
