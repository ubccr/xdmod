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

    protected $_symbolStyles = array(
        'circle',
        'diamond',
        'square',
        'triangle-up',
        'triangle-down',
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
            ),
            'layout' => array(
                'annotations' => array(
                    array(
                        'name' => 'title',
                        'text' => '',
                        'xref' => 'paper',
                        'yref' => 'paper',
                        'xanchor' => 'center',
                        'yanchor' => 'bottom',
                        'x' => 0.5,
                        'y' => 1.0,
                        'font' => array(
                            'color' => '#000000', 
                            'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                        ),
                        'showarrow' => false,
                        'captureevents' => true,
                    ),
                    array(
                        'name' => 'subtitle',
                        'text' => '',
                        'xref' => 'paper',
                        'yref' => 'paper',
                        'xanchor' => 'center',
                        'yanchor' => 'bottom',
                        'x' => 0.5,
                        'y' => 1.0,
                        'showarrow' => false,
                        'captureevents' => true,
                    ),
                    array(
                        'name' => 'credits',
                        'text' => $this->_startDate.' to '. $this->_endDate.'. Powered by XDMoD/Plotly.',
                        'font' => array(
                            'color' => '#909090',
                            'size' => 10,
                            'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                        ),
                        'xref' => 'paper',
                        'yref' => 'paper',
                        'xanchor' => 'right',
                        'yanchor' => 'bottom',
                        'x' => 1.0,
                        'y' => 1.0,
                        'xshift' => 25,
                        'showarrow' => false
                    ),
                ),
                'legend' => array(
                    'itemwidth' => 40,
                    'itemsizing' => 'constant',
                    'bgcolor' => '#ffffff',
                    'borderwidth' => 0,
                    'xref' => 'container',
                    'yref' => 'container',
                    'traceorder' => 'normal'
                ),
                'hovermode' => $this->_hideTooltip ? false : 'x unified',
                'hoverlabel' => array(
                    'bgcolor' => 'rgba(255, 255, 255, 0.8)',
                    'font' => array(
                        'color' => '#000000',
                        'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                    ),
                    'namelength' => -1,
                ),
                'barmode' => 'group',
                'images' => array(),
                'margin' => array(
                    't' => 45,
                    'r' => 25,
                    'b' => 25,
                    'l' => 80,
                ),
                'swapXY' => $this->_swapXY,
            ),
            'data' => array(),
            'dimensions' => array(),
            'metrics' => array(),
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
                $pointInterval = (365.25) * (1/12);
                break;
            case 'Quarter':
            case 'quarter':
                $pointInterval = (365.25) * (1/4);
                break;
            case 'Year':
            case 'year':
                $pointInterval = (365.25);
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
                $format = 'Q%q %Y';
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
        $this->_chart['layout']['annotations'][2]['text'] = $this->_startDate.' to '.
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
        return $this->_chart['layout']['annotations'][0]['text'];
    } // getTitle()

    // ---------------------------------------------------------
    // setTitle()
    //
    // Set chart's title text and highcharts-specific title formatting.
    //
    // ---------------------------------------------------------
    public function setTitle($title, $font_size = 3)
    {
        $this->_chart['layout']['annotations'][0]['text'] = $title;
        $this->_chart['layout']['annotations'][0]['font']['size'] = $font_size + 16;
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
        if (preg_match('/floating/i' ,$legend_location)) {
            $this->_chart['layout']['legend']['xref'] = 'paper';
            $this->_chart['layout']['legend']['yref'] = 'paper';
        }
        switch($legend_location)
        {
            case 'top_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                $this->_chart['layout']['annotations'][0]['y'] = '1.05';
                break;
            //case 'bottom_right':
            //break;
            //case 'bottom_left':
            //break;
            case 'left_center':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;

            case 'left_top':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'left_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 0.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_center':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_top':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'right_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 0.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_bottom_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 0.0;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                break;
            case 'floating_top_center':
                $this->_chart['layout']['legend']['xanchor'] = 'center';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.5;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'h';
                break;
            case 'floating_left_center':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_left_top':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_left_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'left';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.0;
                $this->_chart['layout']['legend']['y'] = 0.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_center':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'center';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 0.5;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_top':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'top';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 1.0;
                $this->_chart['layout']['legend']['orientation'] = 'v';
                break;
            case 'floating_right_bottom':
                $this->_chart['layout']['legend']['xanchor'] = 'right';
                $this->_chart['layout']['legend']['yanchor'] = 'bottom';
                $this->_chart['layout']['legend']['x'] = 0.99;
                $this->_chart['layout']['legend']['y'] = 0.0;
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
                $this->_chart['layout']['legend']['y'] = 0.02;
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
        $this->_chart['layout']['xaxis'] = array(
            'automargin' => true,
            'cliponaxis' => false,
            'layer' => 'below traces',
            'title' => array(
                'text' => '<b>' . $xAxisLabel . '</b>',
                'standoff' => 5,
                'font' => array(
                    'size' => ($font_size + 12),
                    'color' => '#000000',
                    'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif'
                ),
            ),
            'otitle' => $originalXAxisLabel,
            'dtitle' => $defaultXAxisLabel,
            'tickfont' => array(
                'size' => ($font_size + 11),
                'color' => '#606060',
            ),
            'type' => 'category',
            'linewidth' => 2 + $font_size / 4,
            'linecolor' => '#c0d0e0',
            'rangemode' => 'tozero',
            'showgrid' => false,
            'spikedash' => 'solid',
            'spikethickness' => 1,
            'spikecolor' => '#c0c0c0',
            'standoff' => 25,
            'categoryarray' => $this->_xAxisDataObject->getValues()
        );

        if (isset($this->_chart['layout']['xaxis']['categoryarray']))
        {
            $this->_chart['layout']['xaxis']['categoryorder'] = 'array';
        }

        $count = $this->_xAxisDataObject->getCount();
        $this->_chart['layout']['xaxis']['dtick'] = $count < 20 ? 0 : round($count / 20);
        if (!$this->_swapXY)
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
            $yIndex = $yAxisIndex + 1;
            $yAxisName = $yAxisIndex == 0 ? 'yaxis' : "yaxis{$yIndex}";
            $xAxisName = substr_replace($yAxisName, 'xaxis', 0, 5);
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
                if(isset($config->chartType))
                {
                    $yAxisType = $config->chartType;
                }
            }
            if($yAxisLabel == $defaultYAxisLabel)
            {
                $yAxisLabel = '';
            }

            // populate the yAxis:
            $yAxis = array(
                'automargin' => true,
                'cliponaxis' => false,
                'layer' => 'below traces',
                'title' => array(
                    'text' => '<b>' . $yAxisLabel . '</b>',
                    'standoff' => 5, 
                    'font' => array(
                        'size' => ($font_size + 12),
                        'color' => $yAxisColor,
                        'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                    ) 
                ),
                'otitle' => $originalYAxisLabel,
                'dtitle' => $defaultYAxisLabel,
                'exponentformat' => 'SI',
                'tickfont' => array(
                    'size' => ($font_size + 11),
                    'color' => '#606060',
                ),
                'ticksuffix' => ' ',
                'tickmode' => 'auto',
                'nticks' => 10,
                'index' => $yAxisIndex,
                'type' => ($yAxisObject->log_scale || $yAxisType == 'log') ? 'log' : 'linear',
                'rangemode' => 'tozero',
                'range' => [$yAxisMin, $yAxisMax],
                'gridwidth' => $yAxisCount > 1 ? 0 : 1 + ($font_size / 6),
                'linewidth' => 2 + $font_size / 4,
                'linecolor' => '#c0d0e0',
                'separatethousands' => $yAxisObject->decimals > 0,
                'side' => 'left',
                'anchor' => 'x',
                'overlaying' => $yAxisIndex == 0 ? null : 'y',
            );

            if ($yAxisIndex > 0){
                if ($yAxisIndex % 2 == 0) {
                    $yAxis = array_merge($yAxis, array(
                        'side' => 'left',
                        'anchor' => 'free',
                        'autoshift' => true,
                    ));
                }
                else {
                    $yAxis = array_merge($yAxis, array(
                        'side' => 'right',
                        'anchor' => $yAxisIndex > 1 ? 'free' : 'x',
                        'autoshift' => true,
                    ));
                }
            }

            $this->_chart['layout']["{$yAxisName}"] = $yAxis;

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
                        $valuesCount = $yAxisDataObject->getCount(true);

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
                $drilldown = array();
                $xValues = array();
                $yValues = array();
                $drillable = array();
                $text = array();
                $colors = array();

                // to display as pie chart:
                if($data_description->display_type == 'pie')
                {
                    foreach( $yAxisDataObject->getValues() as $index => $value)
                    {
                        // If the first value, give it the yAxisColor so we don't skip
                        // that color in the dataset. Otherwise, pick the next color.
                        $yValues[] = $value;
                        $xValues[] = $yAxisDataObject->getXValue($index);
                        $colors[] = ($index == 0) ? $yAxisColor
                            : '#'.str_pad(dechex($this->_colorGenerator->getColor() ), 6, '0', STR_PAD_LEFT);
                        $drillable[] = true;
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
                        );
                        
                    } // foreach

                    // Dont add data labels for small pie slices. Plotly will render all labels otherwise,
                    // which causes the margin on pie charts with many slices to break
                    $sum = array_sum($yValues);
                    for ($i = 0; $i < count($xValues); $i++) {
                        if ((($yValues[$i]/$sum) * 100) > 2.0) {
                            $text[] = '<b>' . $xValues[$i] . '</b><br>' . number_format($yValues[$i], 2, '.', ',');
                        }
                        else {
                            $text[] = '';
                        }
                        
                    }

                    $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'closest';

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
                        $text[] = number_format($value, 2, '.', ',');
                        $drillable[] = true;
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
                } // if ($data_description->display_type == 'pie')

                $values_count = count($yValues);
                if ($dataSeriesSummarized && $values_count > 0) {
                    $drillable[$values_count - 1] = false;
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
                    $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'closest';   
                }

                // Display markers for scatter plots, non-thumbnail plots
                // with fewer than 21 points, or for plots with a single y value.
                $showMarker = $data_description->display_type == 'scatter' ||
                    ($values_count < 21 && $this->_width > \DataWarehouse\Visualization::$thumbnail_width) ||
                    $values_count == 1;
                if ($data_description->display_type == 'pie') 
                {
                    $tooltip = '%{label}' . '<br>' . $lookupDataSeriesName 
                               . ': %{value:,.2f} <b>(%{percent})</b> <extra></extra>';
                }
                else
                {
                    $tooltip = '%{hovertext} <br>' . "<span style=\"color:$color\";> ●</span> " 
                               . $lookupDataSeriesName . ': <b>%{y:,.2f}</b> <extra></extra>';
                }
                $this->_chart['layout']['hoverlabel']['bordercolor'] = $yAxisColor;
                if ($yAxisCount > 1) {
                    $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'x unified';
                    $tooltip = $lookupDataSeriesName . ': <b>%{y:,.2f}</b> <extra></extra>';
                    $this->_chart['layout']["{$yAxisName}"]['showgrid'] = false;
                }
                else {
                    $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'closest';
                }

                $trace = array_merge($trace, array(
                    'automargin'=> $data_description->display_type == 'pie' ? true : null,
                    'name' => $lookupDataSeriesName,
                    'zIndex' => $zIndex,
                    'cliponaxis' => false,
                    'otitle' => $formattedDataSeriesName,
                    'datasetId' => $data_description->id,
                    'drilldown' => $drilldown,
                    'marker' => array(
                        'size' => $font_size + 6,
                        'color' => $color,
                        'colors' => $data_description->display_type == 'pie' ? $colors : null,
                        'line' => array(
                            'width' => 1,
                            'color' => $lineColor,
                        ),
                        'symbol' => $this->_symbolStyles[$traceIndex % 5],
                    ),
                    'line' => array(
                        'color' => $data_description->display_type == 'pie' ?
                                                        null : $color,
                        'dash' => $data_description->line_type,
                        'width' => $data_description->display_type !== 'scatter' ? $data_description->line_width + $font_size / 4 : 0,
                        'shape' => $data_description->display_type == 'spline' || $data_description->display_type == 'areaspline' ? 'spline' : 'linear',
                    ),
                    'type' => $data_description->display_type == 'h_bar' || $data_description->display_type == 'column' ? 'bar' : $data_description->display_type,
                    'mode' => $data_description->display_type == 'scatter' ? 'markers' : 'lines+markers',
                    'hovertext' => $xValues,
                    'hoveron'=>  $data_description->display_type == 'area' || $data_description->display_type == 'areaspline' ? 'points+fills' : 'points',
                    'hovertemplate' => $tooltip,
                    'showlegend' => true,
                    'text' => $data_description->display_type == 'pie' && $data_description->value_labels ? $text : array(),
                    'textposition' => 'outside',
                    'textfont' => array(
                        'color' => $data_description->display_type == 'pie' ? '#000000' : $color,
                        'size' => ($font_size + 8),
                        'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif' 
                    ),
                    'textangle' => $data_description->display_type == 'h_bar' ? 0 : 90,
                    'textinfo' => $data_description->display_type == 'pie' ? 'text' : null,
                    'x' => $this->_swapXY ? $yValues : $xValues,
                    'y' => $this->_swapXY ? $xValues : $yValues,
                    'drillable' => $drillable,
                    'yaxis' => "y{$yIndex}",
                    'offsetgroup' => "y{$yIndex}",
                    'legendgroup' => $traceIndex,
                    'cursor' => 'pointer',
                    'visible' => $visible,
                    'sort' => false,
                    'direction' => 'clockwise',
                    'isRestrictedByRoles' => $data_description->restrictedByRoles,
                )); // $data_series_desc

                if ($data_description->display_type == 'areaspline') {
                    $trace['type'] = 'area';
                }

                // set stacking
                if($data_description->display_type!=='line')
                {
                    if ($trace['type']=='area' && $traceIndex == 0) {
                        $hidden_trace = array(
                            'name' => 'area fix',
                            'x' => $this->_swapXY ? array_fill(0, count($xValues) , 0) : $xValues,
                            'y' => $this->_swapXY ? $xValues : array_fill(0, count($xValues) , 0),
                            'showlegend' => false,
                            'zIndex' => 0,
                            'marker' => array(
                                'color' => '#ffffff'
                            ),
                            'line' => array(
                                'color' => '#ffffff'
                            ),
                            'hoverinfo' => 'skip',
                            'yaxis' => "y{$yIndex}",
                            'type' => 'scatter',
                        );

                        if ($this->_swapXY) {
                            $null_trace['xaxis'] = "x{$yIndex}";
                            unset($null_trace['yaxis']); 
                        }

                        $this->_chart['data'][] = $hidden_trace;
                    }

                    if ($trace['type'] == 'bar' && $data_description->display_type != 'h_bar') {
                        $trace['textposition'] = 'outside';
                        $trace['textangle'] = -90;
                    } 

                    if ($data_description->combine_type=='side' && $trace['type']=='area'){
                        if ($this->_swapXY) {
                            $trace['fill'] = $traceIndex == 0 ? 'tozerox' : 'tozerox';
                        }
                        else {
                            $trace['fill'] = $traceIndex == 0 ? 'tozeroy' : 'tozeroy';
                        }
                    }
                    elseif($data_description->combine_type=='stack')
                    {
                        $trace['stackgroup'] = 'one';
                        $this->_chart['layout']['barmode'] = 'stack'; 
                        $trace['stackgaps'] = 'interpolate'; //connect nulls
                    }
                    elseif($data_description->combine_type=='percent' && !$data_description->log_scale)
                    {
                        $trace['stackgroup'] = 'one';
                        $trace['stackgaps'] = 'interpolate';
                        $trace['groupnorm'] = 'percent';
                        if ($trace['type'] == 'bar') {
                            $this->_chart['layout']['barmode'] = 'stack';
                            $this->_chart['layout']['barnorm'] = 'percent';
                        }
                    }
                }

                if ($this->_swapXY && $data_description->display_type!='pie') {
                    if ($trace['type'] == 'bar') {
                        $trace = array_merge($trace, array('orientation' => 'h'));
                        $trace['hovertemplate'] = '%{hovertext}' . '<br>'. "<span style=\"color:$color\";> ●</span> "
                            . $lookupDataSeriesName . ': <b>%{x:,.2f}</b> <extra></extra>';
                        if ($data_description->std_err) {
                            $trace['hovertemplate'] = $lookupDataSeriesName . ': <b>%{x:,.2f}</b> <extra></extra>';
                        }
                        $trace['textangle'] = 0;
                    } else {
                        if ($this->_chart['layout']['hovermode'] != 'closest') {
                            $this->_chart['layout']['hovermode'] = 'y unified';
                            $trace['hovertemplate'] = $lookupDataSeriesName . ': <b>%{x:,.2f}</b> <extra></extra>';
                        }
                        else {
                            $trace['hovertemplate'] = '%{hovertext} <br>' . "<span style=\"color:$color\";> ●</span> " 
                               . $lookupDataSeriesName . ': <b>%{x:,.2f}</b> <extra></extra>';
 
                        }
                    }
                    unset($trace['yaxis']);
                    $this->_chart['layout']['yaxis'] = $this->_chart['layout']['xaxis'];
                    $this->_chart['layout']["{$xAxisName}"] = $yAxis;
                    $trace['xaxis'] = "x{$yIndex}";
                    if ($yAxisIndex > 1) {
                        unset($this->_chart['layout']["{$yAxisName}"]);
                    }

                    $this->_chart['layout']["{$xAxisName}"]['side'] = ($yAxisIndex % 2 != 0) ? 'top' : 'bottom';
                    if ($this->_chart['layout']["{$xAxisName}"]['side'] == 'top') {
                        $this->_chart['layout']["{$xAxisName}"]['title']['standoff'] = 0;
                    }
                    $this->_chart['layout']["{$xAxisName}"]['anchor'] = 'free';
                    if (isset($this->_chart['layout']["{$xAxisName}"]['overlaying'])) {
                        $this->_chart['layout']["{$xAxisName}"]['overlaying'] = 'x';
                    }
                    
                    $xAxisStep = 0.115;
                    $xAxisBottomBoundStart = 0 + ($xAxisStep * ceil($yAxisCount/2));
                    $xAxisTopBoundStart = 1 - ($xAxisStep * floor($yAxisCount/2));
                    $topShift = floor($yAxisCount/2) - floor($yAxisIndex/2);
                    $bottomShift = ceil($yAxisCount/2) - ceil($yAxisIndex/2);
                    
                    $this->_chart['layout']["{$xAxisName}"]['position'] = $this->_chart['layout']["{$xAxisName}"]['side'] == 'top' ? min(1 - ($xAxisStep * $topShift), 1) :
                        max(0 + ($xAxisStep * $bottomShift), 0);
                    
                    $this->_chart['layout']["{$xAxisName}"]['domain'] = array(0,1);
                    $this->_chart['layout']["{$xAxisName}"]['title']['standoff'] = 0;
                    $this->_chart['layout']["{$xAxisName}"]['type'] = $yAxisObject->log_scale ? 'log' : '-';

                    $this->_chart['layout']['yaxis']['domain'] = array($xAxisBottomBoundStart, $xAxisTopBoundStart);
                    $this->_chart['layout']['yaxis']['autorange'] = 'reversed';
                    $this->_chart['layout']['yaxis']['showgrid'] = false;
                    unset($this->_chart['layout']['yaxis']['position']);
                }

               if ($data_description->display_type == 'line') {
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
                        'legendgroup' => $traceIndex,
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

               if(!($data_description->display_type == 'pie')) {
                   $categoryLabels = array();
                   for ($i = 0; $i < count($xValues); $i++) {
                       if (strlen($xValues[$i]) > 80) {
                           $categoryLabels[] = substr($xValues[$i], 0, 25) . '...';
                       }
                       else if ($data_description->display_type == 'h_bar') {
                           $categoryLabels[] = wordwrap($xValues[$i], 40, '<br>');
                       } else {
                           $categoryLabels[] = wordwrap($xValues[$i], 25, '<br>');
                       }
                   }
                   if ($this->_swapXY) {
                       $this->_chart['layout']['yaxis']['tickmode'] = 'array';
                       $this->_chart['layout']['yaxis']['tickvals'] = $xValues;
                       $this->_chart['layout']['yaxis']['ticktext'] = $categoryLabels;
                   } else {
                       $this->_chart['layout']['xaxis']['tickmode'] = 'array';
                       $this->_chart['layout']['xaxis']['tickvals'] = $xValues;
                       $this->_chart['layout']['xaxis']['ticktext'] = $categoryLabels;
                   }
              }
               
              $this->_chart['data'][] = $trace;

                $error_info = $this->buildErrorDataSeries(
                    $trace,
                    $data_description,
                    $legend,
                    $lineColor,
                    $yAxisDataObject,
                    $formattedDataSeriesName,
                    $yAxisIndex,
                    $semDecimals,
                    $zIndex
                );
                $isThumbnail = !($this->_width > \DataWarehouse\Visualization::$thumbnail_width);
                if(($data_description->value_labels || $data_description->std_err_labels) && $data_description->display_type != 'pie') {
                    for ($i = 0; $i < count($xValues); $i++) {
                        $yPosition = $yValues[$i];
                        if ($data_description->log_scale) {
                            $yPosition = isset($yPosition) ? log10($yPosition) : null;
                        }
                        $data_label = array(
                            'name' => 'data_label',
                            'x' => $this->_swapXY ? $yPosition : $xValues[$i],
                            'y' => $this->_swapXY ? $xValues[$i] : $yPosition, 
                            'xref' => 'x',
                            'yref' => 'y',
                            'showarrow' => false,
                            'captureevents' => false,
                            'text' => isset($yValues[$i]) ? number_format($yValues[$i], 2, '.', ',') : '',
                            'font' => array(
                                'size' => 10 + $font_size,
                                'color' => $color,
                                'family' => 'Lucida Grande, Lucida Sans Unicode, Arial, Helvetica, sans-serif',
                            ),
                            'textangle' => -90,
                            'yshift' => $isThumbnail ? 45 : 70,
                        );

                        if ($this->_swapXY) {
                            $data_label['textangle'] = 0;
                            $data_label['yshift'] = $isThumbnail ? -5 : 0;
                            $data_label['xshift'] = $isThumbnail ? 35 : 70;
                        }

                        if ($data_description->std_err == 1) {
                            if ($this->_swapXY) {
                                $data_label['yshift'] = -7;
                            }
                            else {
                                $data_label['xshift'] = -7;
                            }
                        }

                        if ($data_description->value_labels && $data_description->std_err_labels) {
                            $data_label['text'] = $error_info['data_labels'][$i];
                            if ($this->_swapXY) {
                                $data_label['xshift'] = $isThumbnail ? 60 : 90;
                            }
                            else {
                                $data_label['yshift'] = $isThumbnail ? 60 : 90;
                            }
                        }
                        else if (!$data_description->value_labels && $data_description->std_err_labels) {
                            $data_label['text'] = $error_info['error_labels'][$i];
                        }
                            

                        array_push($this->_chart['layout']['annotations'], $data_label);
                    }
                }

                // build error data series and add it to chart

            } // foreach($yAxisObject->series as $data_description_index => $yAxisDataObjectAndDescription)
        }

        // Add restricted data warning
        if ($this->_showWarnings) {
            $this->addRestrictedDataWarning();
        }

        // Adjust Layering
        // Idea referenced by https://stackoverflow.com/questions/1597736/sort-an-array-of-associative-arrays-by-column-value
        usort($this->_chart['data'], function($trace1, $trace2) { 
            return $trace1['zIndex'] <=> $trace2['zIndex'];
        }); 


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
        $trace,
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
        if(($data_description->std_err == 1 || $data_description->std_err_labels) && $data_description->display_type != 'pie')
        {
            $stderr = array();
            $dataLabels = array();
            $errorLabels = array();
            $hoverText = array();
            $errorCount = $yAxisDataObject->getErrorCount();
            for($i = 0; $i < $errorCount; $i++)
            {
                // build the error bar and set for display
                $v = $yAxisDataObject->getValue($i);
                $e = $yAxisDataObject->getError($i);
                $stderr[] = $e;
                $hoverText[] = number_format($e, 2, '.', ',');
                $dataLabels[] = number_format($v, 2, '.', ',') . ' [+/- ' . number_format($e, 2, '.', ',') . ']';
                $errorLabels[] = '+/- ' . number_format($e, 2, '.', ',');;
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

            $std_err_labels_enabled = property_exists($data_description, 'std_err_labels') && $data_description->std_err_labels;

            $error_trace = $trace;

            // create the data series description:
            $error_trace = array_merge($error_trace, array(
                'name' => $dsn,
                'otitle' => $dsn,
                'datasetId' => $data_description->id,
                'zIndex' => $zIndex,
                'color'=> $error_color,
                'marker' => array(
                    'color' => $error_color, 
                ),
                'text' => array(),
                'hovertext' => $hoverText,
                'mode' => 'lines',
                'hovertemplate' => '<b> +/- ' . '%{hovertext}</b>',
                'error_y' => array(
                    'type' => 'data',
                    'array' => $stderr,
                    'arrayminus' => $stderr,
                    'symmetric' => false,
                    'color' => $error_color
                ),
                'legendgroup' => null,
                'visible' => $visible,
                'isRestrictedByRoles' => $data_description->restrictedByRoles,
            ));

            if ($trace['type'] == 'bar') {
                $error_trace['marker']['color'] = $trace['marker']['color'];
                $this->_chart['layout']['barmode'] = 'overlay';
                $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'x unified';
                if ($this->_swapXY) {
                    $error_trace['error_x'] = $error_trace['error_y'];
                    $this->_chart['layout']['hovermode'] = $this->_hideTooltip ? false : 'y unified';
                    unset($error_trace['error_y']);
                }
            }

            if(!$data_description->log_scale && $data_description->std_err)
            {
                $this->_chart['data'][] = $error_trace;
            }

            return Array('data_labels' => $dataLabels, 'error_labels' => $errorLabels);
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

        $this->_chart['layout']['annotations'][] = array(
            'text' => implode('<br />', $roleRestrictionsStrings),
            'xref' => 'paper',
            'yref' => 'paper',
            'xanchor' => 'center',
            'yanchor' => 'bottom',
            'bgcolor' => '#DFDFDF',
            'x' => 0.0,
            'y' => 1.0,
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
        if($this->_chart['layout']['annotations'][0]['text'] == '' && $this->_chart['layout']['annotations'][1]['text'] != '')
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