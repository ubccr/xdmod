<?php

   namespace xd_charting;
   
   // --------------------------------
   
   /*
   * @function convertPNGStreamToEPSDownload
   *
   * Takes a png-formatted image stream and transforms it into an EPS-formatted stream presented
   * as a downloadable attachment
   *
   * @param $png_stream (binary content which comprises a PNG-formatted image
   *
   */

function convertPNGStreamToEPSDownload($png_stream, $eps_file_name = 'xdmod_chart')
{
      
   // EPS filenames have an inherit limit of 76 characters
    $eps_file_name = substr($eps_file_name, 0, 76).'.eps';

    $png_stream_file = tempnam(sys_get_temp_dir(), "png_stream_saved");
    $eps_file = tempnam(sys_get_temp_dir(), "generated_eps.eps");
   
    $handle = fopen($png_stream_file, "w");
    fwrite($handle, $png_stream);
    fclose($handle);
   
    $im = new \Imagick($png_stream_file);
      
    $im->setImageFormat("eps");
    $im->writeImage($eps_file);
   
    unlink($png_stream_file);
      
   // fix for IE catching or PHP bug issue
    header("Pragma: public");
    header("Expires: 0"); // set expiration time
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
   // browser must download file from server instead of cache
      
   // force download dialog
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
      
    header("Content-Disposition: attachment; filename=".$eps_file_name.";");
      
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($eps_file));
      
    readfile($eps_file);
   
    unlink($eps_file);
}//convertPNGStreamToEPS

   // --------------------------------------------------

   // @function processForReport
      
function processForReport(&$highchart_config)
{
   
    $data = json_encode($highchart_config);
    $highchart_config = json_decode($data, true);
      
    if (isset($highchart_config['data']) && isset($highchart_config['data'][0])) {
        $highchart_config = $highchart_config['data'][0];
    }
   
   /*
   $highchart_config['chart']['width'] = 148;
   $highchart_config['chart']['height'] = 69;
   */
      
   //$highchart_config['legend']['enabled'] = false;
      
    $highchart_config['credits']['text'] = '';
      
   //$highchart_config['title']['text'] = '';
   //$highchart_config['subtitle']['text'] = '';
   
   //$highchart_config['xAxis']['title']['text'] = '';
   ///$highchart_config['xAxis']['labels']['enabled'] = false;
   //$highchart_config['xAxis']['gridLineColor'] = '#ffffff';
   //$highchart_config['xAxis']['tickColor'] = '#ffffff';
   //$highchart_config['xAxis']['lineColor'] = '#ffffff';
      
   //$highchart_config['yAxis']['title']['text'] = '';
   //$highchart_config['yAxis']['labels']['enabled'] = false;
   //$highchart_config['yAxis']['gridLineColor'] = '#ffffff';
   //$highchart_config['yAxis']['lineColor'] = '#ffffff';
      
   //$highchart_config['plotOptions']['series'] = array('marker' => array('enabled' => false));
}//processForReport
      
   // --------------------------------------------------

   // @function processForThumbnail
      
function processForThumbnail(&$highchart_config)
{
   
    if (isset($highchart_config['data']) && isset($highchart_config['data'][0])) {
        $highchart_config = $highchart_config['data'][0];
    }
   
   /*
   $highchart_config['chart']['width'] = 148;
   $highchart_config['chart']['height'] = 69;
   */
      
    $highchart_config['legend']['enabled'] = false;
      
    $highchart_config['credits']['text'] = '';
      
    $highchart_config['title']['text'] = '';
    $highchart_config['subtitle']['text'] = '';
   
    $highchart_config['xAxis']['title']['text'] = '';
    $highchart_config['xAxis']['labels']['enabled'] = false;
   //$highchart_config['xAxis']['gridLineColor'] = '#ffffff';
   //$highchart_config['xAxis']['tickColor'] = '#ffffff';
   //$highchart_config['xAxis']['lineColor'] = '#ffffff';
      
    $highchart_config['yAxis']['title']['text'] = '';
    $highchart_config['yAxis']['labels']['enabled'] = false;
   //$highchart_config['yAxis']['gridLineColor'] = '#ffffff';
   //$highchart_config['yAxis']['lineColor'] = '#ffffff';
      
   //$highchart_config['plotOptions']['series'] = array('marker' => array('enabled' => false));
}//processForThumbnail
   
     // --------------------------------------------------

   // @function encodeJSON
function encodeJSON($data)
{
    $value_arr = array();
    $replace_keys = array();
    $start_index = 1;
    \xd_charting\replace_functions($data, $value_arr, $replace_keys, $start_index);
    $json = json_encode($data);
    $json = str_replace($replace_keys, $value_arr, $json);
    return $json;
}
   // --------------------------------------------------

   // @function exportHighchart
         
function exportHighchart($chartConfig, $width, $height, $scale, $format, $globalChartConfig = null)
{
    $effectiveWidth = (int)($width*$scale);
    $effectiveHeight = (int)($height*$scale);

    $base_filename = sys_get_temp_dir() . '/' . md5(rand() . microtime());

   // These files must have the proper extensions for PhantomJS.
    $output_image_filename = $base_filename . '.' . $format;
    $tmp_html_filename     = $base_filename . '.html';
   
    $html_dir = __DIR__ . "/../html";
    $template = file_get_contents($html_dir . "/highchart_template.html");
   
    $template = str_replace('_html_dir_', $html_dir, $template);
    $template = str_replace('_chartOptions_', encodeJSON($chartConfig), $template);
    if ($globalChartConfig !== null) {
        $template = str_replace('_globalChartOptions_', encodeJSON($globalChartConfig), $template);
    } else {
        $template = str_replace('_globalChartOptions_', 'null', $template);
    }
    $template = str_replace('_width_', $effectiveWidth, $template);
    $template = str_replace('_height_', $effectiveHeight, $template);
    file_put_contents($tmp_html_filename, $template);
   
   
         
    if ($effectiveWidth <  \DataWarehouse\Visualization::$thumbnail_width) {
        //$effectiveHeight = (int)($effectiveHeight * 600/$effectiveWidth);
        //$effectiveWidth = 600;
    }
         
    if ($format == 'png') {
        \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js png $tmp_html_filename $output_image_filename $effectiveWidth $effectiveHeight");
      
        $data = file_get_contents($output_image_filename);
   
        @unlink($output_image_filename);
        @unlink($tmp_html_filename);
      
        return $data;
    }
      
    if ($format == 'svg') {
        // $effectiveWidth = 1660;
        // $effectiveHeight = 1245;
         
        $svgContent = \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js svg $tmp_html_filename null ".$effectiveWidth." ".$effectiveHeight);
      
        @unlink($tmp_html_filename);
      
        return $svgContent;
    }
}//exportHighchart
   
   
function replace_functions(&$object, array &$value_arr, array &$replace_keys, &$xxx = 1)
{
    foreach ($object as $key => &$__value) {
        if (is_array($__value)) {
            replace_functions($__value, $value_arr, $replace_keys, $xxx);
        } elseif (is_object($__value)) {
            replace_functions($__value, $value_arr, $replace_keys, $xxx);
        } else {       // Look for values starting with 'function('
      //if(strpos($__value, 'function(')===0){
            if (preg_match('/^(\s*)function(.*)/', $__value, $matches)==1) {
                // Store function string.
                $value_arr[] = $__value;
                // Replace function string in $foo with a 'unique' special key.
                $__value = '%' . $key. '_' . $xxx . '%';
                // Later on, we'll look for the value, and replace it.
                $replace_keys[] = '"' . $__value . '"';
                $xxx++;
            }
        }
    }
}
