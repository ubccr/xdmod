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
* Base class for server side generated plotly charts
*/
class Plotly2
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

    protected $user;

    protected $_colorGenerator;
    /**
     * The role restrictions string builder used by this chart.
     *
     * @var DataWarehouse\RoleRestrictionsStringBuilder
     */
    protected $roleRestrictionsStringBuilder;

    // ---------------------------------------------------------
    // __construct()
    //
    // Constructor for Plotly2 class.
    //
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
                'showWarnings' => $this->_showWarnings,
                'dateFormat' => $this->getDateFormat()
            ),
            'layout' => array(
                'title' => array(
                    'text' => '',
                ),
                'annotations' => array(
                // Credits
                    array(
                        'text' => $this->_startDate.' to '. $this->_endDate.'. Powered by XDMoD/Plotly. This is test',
                        'font' => array(
                        'color' => '#909090',
                        'size' => 10,
                        'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif'
                    ),
                    'xref' => 'paper',
                    'yref' => 'paper',
                    'xanchor' => 'right',
                    'yanchor' => 'bottom',
                    'x' => 1,
                    'y' => 0,
                    'yshift' => -80,
                    'showarrow' => false
                    ),
                    // Subtitle
                    array(
                        'text' => '',
                        'xref' => 'paper',
                        'yref' => 'paper',
                        'xanchor' => 'center',
                        'yanchor' => 'bottom',
                        'x' => 0.5,
                        'y' => 1.035,
                        'showarrow' => false
                    ),
                ),
                'legend' => array(
                    'itemwidth' => 40,
                    'bgcolor' => '#FFFFFF',
                    'borderwidth' => 0,
                    'xref' => 'paper',
                    'yref' => 'paper'
                ),
                'xaxis' => array(
                    'automargin' => true
                ),
                'hovermode' => $this->_hideTooltip ? false : 'x unified',
                'bargap' => 0.05,
                'bargroupgap' => 0,
                'images' => array(),
            ),
            'data' => array(),
            'dimensions' => array(),
            'metrics' => array(),
            'exporting' => array(
                'enabled' => false
            )
        );

        $this->_colorGenerator = new \DataWarehouse\Visualization\ColorGenerator();

        $this->roleRestrictionsStringBuilder = new RoleRestrictionsStringBuilder();
        $this->user = $user;
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

    /**
     * Format and adjust start and end date for chart, given selected aggregation unit.
     *
     * The XDMoD UI presents data aggregated at particular units (day, month, quarter, year). It is
     * sometimes necessary to adjust the user-specified start or end date to align with aggregation
     * unit boundaries as we do not display partial aggregation units (the user must switch to a
     * finer-grained unit for this). This means that the start date may need to be adjusted to the
     * start of the unit that it falls into, similarly the end date may need to be adjusted to align
     * with the end date of the unit. For example, viewing a monthly aggregation unit the date range
     * 2017-1-15 - 2017-08-20 must be adjusted to 2017-01-01 - 2017-08-31 to include entire months
     * since that is the data that will be returned.
     *
     * NOTE: XDMoD does not currently provide data for the future. For end dates that fall on the
     * current date or in the future, do not automatically adjust the date to the end of the
     * aggregation period that it falls into. Rather, adjust it to the end of the current day as
     * this is the data that will be displayed.
     *
     * @param string $startDate            Start date for the plot in a valid PHP format
     * @param string $endDate              End date for the plot in a valid PHP format
     * @param string $aggregationUnit      Selected aggregation unit (month, year, etc.)
     * @param string $min_aggregation_unit The smallest aggregation unit allowed when automatically
     *                                     determining the unit or null if no limit.
     *
     * @return Nothing
     */

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
                // no need to adjust
                break;
        }

        // XDMoD does not currently handle future data, so if the end date is the current day or in
        // the future (e.g., we are looking at a "month to date" plot or the user has entered a
        // future date), adjust the end date to the end of the current day rather than the end of
        // the last aggregation period so the date is not misleading to the user.

        $this->_startDate = $startDate;
        $this->_endDate = (
            strtotime($endDate) >= mktime(23, 59, 59)
            ? date('Y-m-d', mktime(23, 59, 59))
            : $endDate
        );

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
        $this->_chart['layout']['annotations'][0]['text'] = $this->_startDate.' to '.
            $this->_endDate.' '.$src.' Powered by XDMoD/Plotly';
    } // setDataSource()

    // ---------------------------------------------------------
    // getTitle()
    //
    // Get and return chart title as text.
    //
    // ---------------------------------------------------------
    public function getTitle()
    {
        return $this->_chart['layout']['title']['text'];
    } // getTitle()

    // ---------------------------------------------------------
    // setTitle()
    //
    // Set chart's title text and highcharts-specific title formatting.
    //
    // ---------------------------------------------------------
    public function setTitle($title, $font_size = 3)
    {
        $this->_chart['layout']['title']['text'] = $title;
        $this->_chart['layout']['title']['font'] = array(
            'color'=> '#000000',
            'size' => (16 + $font_size)
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
            $this->_chart['layout']['annotations'][1]['text'] = $subtitle_html;
        } else {
            $this->_subtitleText = '';
            $this->_chart['layout']['annotations'][1]['text'] = '';
        }
        $this->_chart['layout']['annotations'][1]['font']['color'] = '#5078a0';
        $this->_chart['layout']['annotations'][1]['font']['size'] = (12 + $font_size);
        //$this->_chart['layout']['annotations'][1]['y'] = 28 + (2 * $font_size);
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
        $this->_chart['layout']['legend']['font'] = array(
            'color' => '#274b6d',
            'size' => (12  + $font_size)
        );
        $this->_legend_location = $legend_location;
        switch($legend_location)
        {
            case 'top_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 1.2;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                $pad = 1.2;
                if($this->_chart['layout']['annotations'][1]['text'] != '')
                {
                    $pad = 1.0;
                }
                $this->_chart['layout']['legend']['y'] = $pad;

                break;
            //case 'bottom_right':
            //break;
            //case 'bottom_left':
            //break;
            case 'left_center':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = -0.1;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;

            case 'left_top':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = -0.1;
                $this->_chart['layout']['legend']['y'] = 1.1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'left_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = -0.1;
                $this->_chart['layout']['legend']['y'] = -0.1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_center':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 1.1;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_top':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 1.1;
                $this->_chart['layout']['legend']['y'] = 1.1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 1.1;
                $this->_chart['layout']['legend']['y'] = -0.1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_bottom_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 0.1;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                break;
            case 'floating_top_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 0.9;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                break;
            case 'floating_left_center':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 0.075;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_left_top':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.075;
                $this->_chart['layout']['legend']['y'] = 1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_left_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.075;
                $this->_chart['layout']['legend']['y'] = 0.1;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_center':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 1;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_top':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 1;
                $this->_chart['layout']['legend']['y'] = 0.925;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 1;
                $this->_chart['layout']['legend']['y'] = 0.075;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case '':
            case 'none':
            case 'off':
                $this->_legend_location = 'off';
                $this->_chart['layout']['legend']['visible'] = false;
                break;
            case 'bottom_center':
            default:
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = -0.5;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                break;
        }

        /* Look into where this should go for Plotly or if we even need it
        if($legend_location != 'bottom_center')
        {
            $this->_chart['chart']['spacingBottom'] = 25;
        }
        if($legend_location != 'right_center' &&
             $legend_location != 'right_top' &&
             $legend_location != 'right_bottom')
        {
            $this->_chart['chart']['spacingRight'] = 20;
        }*/
        $this->_hasLegend = $this->_legend_location != 'off';

    } // setLegend()

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
        $this->_chart['layout']['xaxis'] = array_merge($this->_chart['layout']['xaxis'] ,array(
                'title' => array(
                        'text' => '<b>'. $xAxisLabel . '</b>',
                        'standoff' => 15 + $font_size,
                        'font' => array(
                                'color'=> '#000000',
                                'size' => (12 + $font_size)
                        )
                ),
                'automargin' => true,
                'otitle' => $originalXAxisLabel,
                'dtitle' => $defaultXAxisLabel,
                'tickfont' => array(
                    'size' => (11 + $font_size)
                ),
                'linewidth' => 2 + $font_size / 4,
                'categoryarray' => $this->_xAxisDataObject->getValues()
        ));

        if (isset($this->_chart['layout']['xaxis']['categoryarray']))
        {
            $this->_chart['layout']['xaxis']['categoryorder'] = 'array';
        }

        $count = $this->_xAxisDataObject->getCount();
        $this->_chart['layout']['xaxis']['dtick'] = $count < 20 ? 0 : round($count / 20);
        if ($this->_swapXY)
        {
            /*$this->_chart['layout']['xaxis']['settings'] = array(
                'maxL' => floor($this->_width * 20 / 580); // need support
            );*/
        }
        else
        {
            $this->_chart['layout']['xaxis']['tickangle'] = -90;
            $this->_chart['layout']['xaxis']['ticklabelposition'] = 'outside left';
        }
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

        //throw new \Exception('hi');
        $this->limit = $limit;
        $this->show_filters = $show_filters;
        $this->setMultiCategory($data_series);

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
            $this->_queryType,
            $this->user
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
                        continue 2; // bug? comment above mentions break but uses continue
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

            list($yAxisColor, $yAxisLineColor) = $this->getColorFromDataDescription($first_data_description);

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
                'automargin' => true,
                'title' => '<b>' . $yAxisLabel . '<\b>',
                'titlefont' => array(
                    'size' => (12 + $font_size),
                    'color' => $yAxisColor
                ),
                'otitle' => $originalYAxisLabel,
                'dtitle' => $defaultYAxisLabel,
                'tickfont' => array(
                    'size' => (11 + $font_size)
                ),
                'type' => $yAxisObject->log_scale ? 'log' : 'linear',
                'gridwidth' => $yAxisCount > 1 ? 0 : 1 + ($font_size / 8),
                'linewidth' => 2 + $font_size / 4,
                'separatethousands' => $yAxisObject->decimals > 0,
                'overlaying' => $yAxisIndex == 0 ? null : 'y',
            );

            $yIndex = $yAxisIndex + 1;
            $this->_chart['layout']["yaxis{$yIndex}"] = $yAxis;

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
                if($data_description_index == 0) {
                    $color = $yAxisColor;
                    $lineColor = $yAxisLineColor;
                } else {
                    list($color, $lineColor) = $this->getColorFromDataDescription($data_description);
                }

                $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;
                $data_labels_enabled = $data_description->value_labels || $std_err_labels_enabled;
                $trace = array();
                $tooltipConfig = array();
                $drilldown = array();
                $xValues = array();
                $yValues = array();
                $colors = array();

                // to display as pie chart:
                if($data_description->display_type == 'pie')
                {
                    $this->_chart['chart']['inverted'] = false; // Need to confirm if same as 'autorange: reversed'

                    foreach( $yAxisDataObject->getValues() as $index => $value)
                    {
                        // If the first value, give it the yAxisColor so we don't skip
                        // that color in the dataset. Otherwise, pick the next color.
                        $yValues[] = $value;
                        $xValues[] = $yAxisDataObject->getXValue($index);
                        $colors[] = ($index == 0) ? $yAxisColor
                                     : '#'.str_pad(dechex($this->_colorGenerator->getColor() ), 6, '0', STR_PAD_LEFT);
                        // N.B.: These are drilldown labels.
                        // X axis labels will be the same, but are plotted
                        // from the x axis object instance variable.
                        // See setXAxis() and _xAxisDataObject.
                        $drilldown[] = array(
                            'id' => $yAxisDataObject->getXId($index),
                            'label' => $yAxisDataObject->getXValue($index)
                        );

                        $trace = array(
                            'labels' => $xValues,
                            'values' => $yValues,
                            'text' => array_map(function($label, $value) {
                                $text = '<b>' . $label . '</b><br>' . $value;
                                return $text;
                            }, $xValues, $yValues),
                            'textinfo' => 'text',
                        );

                    } // foreach


                    $this->_chart['layout']['hovermode'] = 'closest';

                }
                else // ($data_description->display_type !== 'pie')
                {
                    if($this->_swapXY)
                    {
                        //TODO: Ajust data labels
                        $trace['textangle'] = 90;
                        $this->_chart['layout']['xaxis']['tickangle'] = 0;
                    }
                    else // (!$this->_swapXY)
                    {
                        //TODO: Normal data labels
                    } // _swapXY

                    // set the label for each value:
                    foreach( $yAxisDataObject->getValues() as $index => $value)
                    {
                        $yValues[] = $value;
                        $xValues[] = $yAxisDataObject->getXValue($index);

                        // N.B.: The following are drilldown labels.
                        // Labels on the x axis come from the x axis object
                        // (Though they are the same labels...)
                        $drilldown[] = array(
                            'id' => $yAxisDataObject->getXId($index),
                            'label' => $yAxisDataObject->getXValue($index)
                        );

                        try {
                            $point['percentage'] = $yAxisDataObject->getError($index);
                        } catch (\Exception $e) {

                        }

                    } // foreach
                    $trace = array_merge($trace, array('text' => $yValues));
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
                        $data_description->category
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

                if (in_array($data_description->display_type, array('h_bar', 'bar', 'pie')))
                {
                    $this->_chart['layout']['hovermode'] = 'closest';   
                }

                // Display markers for scatter plots, non-thumbnail plots
                // with fewer than 21 points, or for plots with a single y value.
                $showMarker = $data_description->display_type == 'scatter' ||
                    ($values_count < 21 && $this->_width > \DataWarehouse\Visualization::$thumbnail_width) ||
                    $values_count == 1;
                if ($data_description->display_type == 'pie') 
                {
                    $tooltip = '%{label}' . '<br>' . $lookupDataSeriesName . 
                               ': %{value:,} <b>(%{percent})</b> <extra></extra>';
                }
                else
                {
                    $tooltip = '%{x}' . '<br> <span style="color:' . $color
                            . ';">●</span> ' . $lookupDataSeriesName . ': <b>%{y:,}</b> <extra></extra>';
                }

                $trace = array_merge($trace, array(
                    'name' => $lookupDataSeriesName,
                    'otitle' => $formattedDataSeriesName,
                    'datasetId' => $data_description->id,
                    'drilldown' => $drilldown,
                    'marker' => array(
                        'size' => 10, 
                        'color' => $color,
                        'line' => array(
                            'width' => 1,
                            'color' => $lineColor
                        ),
                    ),
                    'line' => array(
                        'color' => $data_description->display_type == 'pie' ?
                                                        null : $color,
                        'dash' => $data_description->line_type,
                        'width' => $data_description->display_type !== 'scatter' ?
                                                        $data_description->line_width + $font_size / 4 : 0,
                    ),
                    'type' => $data_description->display_type == 'h_bar' ? 'bar' : $data_description->display_type,
                    'mode' => $showMarker ? 'lines+marker' : 'lines',
                    'hoveron'=>  $data_description->display_type == 'area' ||
                                                        $data_description->display_type == 'areaspline' ? 'points+fills' : 'points',
                    'hovertemplate' => $tooltip,
                    'hoverlabel' => array(
                        'bgcolor' => '#FFF'
                    ),
                    'showlegend' => true,
                    'textposition' => $data_description->display_type == 'pie' ? 'outside' : 'top center',
                    'x' => $data_description->display_type == 'pie' ? null : $xValues,
                    'y' => $data_description->display_type == 'pie' ? null : $yValues,
                    'yaxis' => "y{$yIndex}",
                    'cursor' => 'pointer',
                    'visible' => $visible,
                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                )); // $data_series_desc

                if (!$data_labels_enabled){
                    unset($trace['text']);
                }

                if ($data_description->display_type == 'h_bar') {
                    $trace = array_merge($trace, array('orientation' => 'h'));
                    $tmp = $trace['x'];
                    $trace['x'] = $trace['y'];
                    $trace['y'] = $tmp;
                    $trace['hovertemplate'] = '%{y}' . '<br>'. $lookupDataSeriesName . ': <b>%{x:,}</b> <extra></extra>'; 
                    $this->_chart['layout']['margin'] = array(
                        'l' => 200
                    );
                }
                // set stacking
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

                    if ($data_description->combine_type=='side' && $data_description->display_type=='area') {
                        $trace['fill'] = 'tonexty';
                    }
                    elseif($data_description->combine_type=='stack')
                    {
                        $trace['stackgroup'] = 'one';
                        $trace['stackgaps'] = 'interpolate'; //connect nulls
                    }
                    elseif($data_description->combine_type=='percent' && !$data_description->log_scale)
                    {
                        $trace['groupnorm'] = 'percent';
                    }
                }
                $this->_chart['data'][] = $trace;
                //throw new \Exception(json_encode($trace));
                // build error data series and add it to chart
                /*$trace['error_y'] = $this->buildErrorDataSeries(
                    $data_description,
                    $legend,
                    $lineColor,
                    $yAxisDataObject,
                    $formattedDataSeriesName,
                    $yAxisIndex,
                    $semDecimals,
                    $zIndex
                );*/

            } // foreach($yAxisObject->series as $data_description_index => $yAxisDataObjectAndDescription)
        }

        /*if ($this->_showWarnings) {
            $this->addRestrictedDataWarning();
        }*/

        // set title and subtitle for chart
        $this->setChartTitleSubtitle($font_size);

    } // configure()

    // ---------------------------------------------------------
    // buildErrorDataSeries()
    //
    // @param ...
    //
    // TODO: Two main implementations, need to choose which one would be better.
    // Most likely will be to create line plot and configure it into an error plot
    // for Timeseries, questions include: drilldown?
    // ---------------------------------------------------------
    protected function buildErrorDataSeries(
        &$data_description,
        &$legend,
        $error_color,
        &$yAxisDataObject,
        $formattedDataSeriesName,
        $yAxisIndex,
        $semDecimals,
        $zIndex
    ) {

        // build error data series and add it to chart
        if($data_description->std_err == 1 && $data_description->display_type != 'pie')
        {
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

            // create the data series description:
            $err_data_y = array(
                //'name' => $dsn,
                'name' => $lookupDataSeriesName,
                'showInLegend' => true,
                'otitle' => $dsn,
                'datasetId' => $data_description->id,
                'zIndex' => $zIndex,
                'color'=> $error_color,
                'type' => 'data',
                'symmetric' => false,
                'shadow' => $data_description->shadow,
                'groupPadding' => 0.05,
                'pointPadding' => 0,
                'lineWidth' => 2,
                'yaxis' => $yAxisIndex == 0 ? null : $yAxisIndex,
                'tooltip' => array(
                        'valueDecimals' => $semDecimals
                    ),
                'data' => $error_series,
                'cursor' => 'pointer',
                'visible' => $visible,
                'isRestrictedByRoles' => $data_description->restrictedByRoles,
                );
            if(! $data_description->log_scale)
            {
                return $err_data_y;
                //$this->_chart['series'][] = $err_data_series_desc;
            }
        } // if not pie
    } // function buildErrorDataSeries

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
        if($this->_chart['layout']['title']['text'] == '' && $this->_chart['layout']['annotations'][1]['text'] != '')
        {
            $this->setTitle($this->_chart['layout']['annotations'][1]['text'], $font_size);
            $this->setSubtitle('', $font_size);
        }
    } // function setChartTitleSubtitle

    // ---------------------------------------------------------
    // exportJsonStore()
    //
    // Export chart object aoverlayings JSON, indicate count.
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

    protected function getColorFromDataDescription($data_description) {
        $color_value = null;
        if ($data_description->color == 'auto' || $data_description->color == 'FFFFFF') {
            $color_value = $this->_colorGenerator->getColor();
        } else {
            $color_value = $this->_colorGenerator->getConfigColor(hexdec($data_description->color));
        }

        $color = '#'.str_pad(dechex($color_value), 6, '0', STR_PAD_LEFT);
        $lineColor = '#'.str_pad(dechex(\DataWarehouse\Visualization::alterBrightness($color_value, -70)), 6, '0', STR_PAD_LEFT);

        return array($color, $lineColor);
    }
} // class Plotly2
