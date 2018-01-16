<?php

namespace DataWarehouse\Access;

class Common
{
    protected $request = null;
    /*
        Contents of $request may include the following (and lots of other stuff)
        
        $this->request = array(
            'start_date'
            'end_date'
            'realm'
            'show_title'
            'width'
            'height'
            'scale'
            'show_guide_lines'
            'active_role'
            'swap_xy'
            'share_y_axis'
            'hide_tooltip'
            'legend_type'
            'font_size'
            'title'
            'subtitle'
            'limit'
            'start'
            'sort'
            'dir'
            'search_text'
            'inline'
            'data_series'
            'global_filters'
            'show_filters'
            'x_axis'
            'y_axis'
            'legend'
    */

    public function __construct($request) {
        $this->request = $request;
    }

    protected function checkDateParameters()
    {
        $start_date_parsed = date_parse_from_format(
            'Y-m-d',
            $this->request['start_date']
        );

        if ($start_date_parsed['error_count'] !== 0) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException(
                'start_date param is not in the correct format of Y-m-d.'
            );
        }

        $end_date_parsed = date_parse_from_format('Y-m-d', $this->request['end_date']);

        if ($end_date_parsed['error_count'] !== 0) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException(
                'end_date param is not in the correct format of Y-m-d.'
            );
        }

        return array(
            $this->request['start_date'],
            $this->request['end_date'],
            mktime(
                $start_date_parsed['hour'],
                $start_date_parsed['minute'],
                $start_date_parsed['second'],
                $start_date_parsed['month'],
                $start_date_parsed['day'],
                $start_date_parsed['year']
            ),
            mktime(
                23,
                59,
                59,
                $end_date_parsed['month'],
                $end_date_parsed['day'],
                $end_date_parsed['year']
            )
        );
    }

    protected function getShowTitle()
    {
        return (
            isset($this->request['show_title'])
            ? $this->request['show_title'] === 'y'
            : false
        );
    }

    protected function getWidth()
    {
        return (
            isset($this->request['width']) && is_numeric($this->request['width'])
            ? $this->request['width']
            : 740
        );
    }

    protected function getHeight()
    {
        return (
            isset($this->request['height']) && is_numeric($this->request['height'])
            ? $this->request['height']
            : 345
        );
    }

    protected function getScale()
    {
        return (
            isset($this->request['scale']) && is_numeric($this->request['scale'])
            ? $this->request['scale']
            : 1.0
        );
    }

    protected function getShowGuideLines()
    {
        if (isset($this->request['show_guide_lines'])) {
            return $this->request['show_guide_lines'] == 'true'
                || $this->request['show_guide_lines'] === 'y';
        }
    }

    protected function getRealm()
    {
        if (!isset($this->request['realm'])) {
            throw new \Exception('Parameter realm is not set');
        }

        return $this->request['realm'];
    }

    protected function getSwapXY()
    {
        return
            isset($this->request['swap_xy'])
            ? $this->request['swap_xy'] == 'true' || $this->request['swap_xy'] === 'y'
            : false;
    }

    protected function getShareYAxis()
    {
        return
            isset($this->request['share_y_axis'])
            ? $this->request['share_y_axis'] == 'true'
            || $this->request['share_y_axis'] === 'y'
            : false;
    }

    protected function getHideTooltip()
    {
        return
            isset($this->request['hide_tooltip'])
            ? $this->request['hide_tooltip'] == 'true'
            || $this->request['hide_tooltip'] === 'y'
            : false;
    }

    protected function getLegendLocation()
    {
        return
            isset($this->request['legend_type']) && $this->request['legend_type'] != ''
            ? $this->request['legend_type']
            : 'bottom_center';
    }

    protected function getFontSize()
    {
        return
            isset($this->request['font_size']) && $this->request['font_size'] != ''
            ? $this->request['font_size']
            : 'default';
    }

    protected function getTitle()
    {
        return
            isset($this->request['title']) && $this->request['title'] != ''
            ? $this->request['title']
            : null;
    }

    protected function getLimit()
    {
        if (!isset($this->request['limit']) || empty($this->request['limit'])) {
            $limit = 20;
        }
        else {
            $limit = $this->request['limit'];
        }

        return $limit;
    }

    protected function getOffset()
    {
        if (!isset($this->request['start']) || empty($this->request['start'])) {
            $offset = 0;
        }
        else {
            $offset = $this->request['start'];
        }

        return $offset;
    }

    protected function getShowRemainder()
    {
        $showRemainder = false;

        if (isset($this->request['show_remainder'])) {
            $showRemainder = filter_var(
                $this->request['show_remainder'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $showRemainder;
    }

    protected function getSortInfo()
    {
        $sortInfo = array();

        if (isset($this->request['sort']) && $this->request['sort'] != '') {
            $sortRec = array();

            $sortRec['column_name'] = $this->request['sort'];

            $sortRec['direction']
                = isset($this->request['dir'])
                ? $this->request['dir']
                : 'asc';

            $sortInfo[] = $sortRec;
        }

        return $sortInfo;
    }

    protected function getSearchText()
    {
        return
            isset($this->request['search_text']) && $this->request['search_text'] != ''
            ? trim($this->request['search_text'])
            : NULL;
    }

    protected function getInline()
    {
        return
            isset($this->request['inline'])
            ? $this->request['inline'] == 'true' || $this->request['inline'] === 'y'
            : true;
    }

    protected function getTimeseries()
    {
        return
            isset($this->request['timeseries'])
            ? $this->request['timeseries'] == 'true' || $this->request['timeseries'] === 'y'
            : false;
    }

    protected function getFilename()
    {
        return \xd_utilities\array_get($this->request, 'filename');
    }

    protected function exportImage($returnData, $width, $height, $scale, $format, $filename)
    {
        if (isset($this->request['render_thumbnail']))
        {

            \xd_charting\processForThumbnail($returnData);

            $result = array(
                "headers" => array( "Content-Type" => "image/png"),
                "results" => \xd_charting\exportHighchart($returnData, '148', '69', 2, 'png')
            );

            return $result;
        }

        if (isset($this->request['render_for_report']))
        {

            \xd_charting\processForReport($returnData);

            $result = array( 
                "headers" => array( "Content-Type" => "image/png"),
                "results" => \xd_charting\exportHighchart($returnData, $width, $height, $scale, 'png')
            );

            return $result;
        }

        if ($format === 'png' || $format === 'svg' || $format === 'pdf')
        {
            $fileMeta = array(
                'title' => $filename
            );

            $result = array(
                "headers" => \DataWarehouse\ExportBuilder::getHeader( $format, false, $filename),
                "results" => \xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, $format, null, $fileMeta)
            );

            return $result;
        }
        elseif($format === 'png_inline')
        {
            $result = array(
                "headers" => \DataWarehouse\ExportBuilder::getHeader( $format, false, $filename),
                "results" => 'data:image/png;base64,'.base64_encode(\xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'png'))
            );
            return $result;

        }
        elseif($format === 'svg_inline')
        {
            $result = array(
                "headers" => \DataWarehouse\ExportBuilder::getHeader( $format, false, $filename),
                "results" => 'data:image/svg+xml;base64,' . base64_encode(
                    \xd_charting\exportHighchart( $returnData['data'][0], $width, $height, $scale, 'svg'))
                );

            return $result;
        }
        elseif ($format === 'hc_jsonstore' ) {

            $result = array(
                "headers" => \DataWarehouse\ExportBuilder::getHeader( $format ),
                "results" => \xd_charting\encodeJSON( $returnData )
            );

            return $result;
        }
        elseif ($format === '_internal') {
            $result = array(
                'headers' => \DataWarehouse\ExportBuilder::getHeader( 'hc_jsonstore' ),
                'results' => $returnData,
            );
            return $result;
        }

        throw new \Exception("Internal Error: unsupported image format $format");
    }
}
