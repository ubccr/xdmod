<?php

namespace xd_charting;

function processForReport(&$highchart_config)
{
    $data = json_encode($highchart_config);
    $highchart_config = json_decode($data, true);

    if ( isset($highchart_config['data']) && isset($highchart_config['data'][0]) ) {
        $highchart_config = $highchart_config['data'][0];
    }

    $highchart_config['credits']['text'] = '';
}

function processForThumbnail(&$highchart_config)
{
    if ( isset($highchart_config['data']) && isset($highchart_config['data'][0]) ) {
        $highchart_config = $highchart_config['data'][0];
    }

    $highchart_config['legend']['enabled'] = false;

    $highchart_config['credits']['text'] = '';

    $highchart_config['title']['text'] = '';
    $highchart_config['subtitle']['text'] = '';

    $highchart_config['xAxis']['title']['text'] = '';
    $highchart_config['xAxis']['labels']['enabled'] = false;

    $highchart_config['yAxis']['title']['text'] = '';
    $highchart_config['yAxis']['labels']['enabled'] = false;
}

function exportHighchart(
    $chartConfig,
    $width,
    $height,
    $scale,
    $format,
    $globalChartConfig = null,
    $fileMetadata = null
) {
    $effectiveWidth = (int)($width*$scale);
    $effectiveHeight = (int)($height*$scale);

    $base_filename = tempnam(sys_get_temp_dir(), 'exportHighchart');

    $tmp_html_filename  = $base_filename . '.html';

    $html_dir = __DIR__ . "/../html";
    $template = file_get_contents($html_dir . "/highchart_template.html");

    $template = str_replace('_html_dir_', $html_dir, $template);
    $template = str_replace('_chartOptions_', json_encode($chartConfig), $template);

    $globalChartOptions = array('timezone' => date_default_timezone_get());
    if ($globalChartConfig !== null) {
        $globalChartOptions = array_merge($globalChartOptions, $globalChartConfig);
    }
    $template = str_replace('_globalChartOptions_', json_encode($globalChartOptions), $template);
    $template = str_replace('_width_',$effectiveWidth, $template);
    $template = str_replace('_height_',$effectiveHeight, $template);
    file_put_contents($tmp_html_filename,$template);

    $data = getScreenFromChromium($tmp_html_filename, $effectiveWidth, $effectiveHeight, $format ===  'pdf' ? 'svg' : $format);
    @unlink($tmp_html_filename);
    if($format === 'pdf'){
        $data = svg2pdf($data, round($width / 90.0 * 72.0), round($height / 90.0 * 72.0), $fileMetadata);
    }
    return $data;
}

/**
 * Use Chromium to generate png or svg.
 *
 * For svg generation uses chromium repl
 *
 * @param string $file location of html template file to use
 * @param int $width desired width of output
 * @param int $height desired height of output
 * @param string $format of output (png or svg)
 * @param array $pdfExtras extras used for pdf output
 *
 * @returns string contents of desired output
 *
 * @throws \Exception on invalid format, command execution failure, or non zero exit status
 */
function getScreenFromChromium($file, $width, $height, $format){

    $repl = '';
    if ($format == 'svg'){
        $repl = 'chart.getSVG(inputChartOptions);';
        $outputType = '-repl';
    }
    elseif ($format == 'png'){
        $outputFile = $file . '.png';
        $outputType = '--screenshot=' . $outputFile;
    }
    else {
        throw new \Exception('Invalid format "' . $format . '" specified, must be one of svg, pdf, or png.');
    }
    $chromiumPath = \xd_utilities\getConfiguration('reporting', 'chromium_path');
    $chromiumOptions = array (
        '--headless',
        '--no-sandbox',
        '--disable-gpu',
        '--disable-software-rasterizer',
        '--window-size=' . $width . ',' . $height,
        '--disable-extensions',
        '--incognito',
        $outputType,
        $file
    );
    $command = $chromiumPath . ' ' . implode(' ', $chromiumOptions);
    $pipes = array();
    $descriptor_spec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w'),
    );
    $process = proc_open($command, $descriptor_spec, $pipes);
    if (!is_resource($process)) {
        throw new \Exception('Unable execute command: "'. $command . '". Details: ' . print_r(error_get_last(), true));
    }
    else {
        fwrite($pipes[0], $repl);
        fclose($pipes[0]);
    }
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value != 0) {
        throw new \Exception('Unable execute command: "'. $command . '". Details: ' . $err);
    }
    if (!empty($repl)){
        $result = json_decode(substr($out, 4, -6), true);
        $data = $result['result']['value'];
    }
    else{
        $data = file_get_contents($outputFile);
        @unlink($outputFile);
    }
    return $data;
}

/**
 * Use rsvg-convert to convert svg to pdf
 *
 * @param string path to input file
 * @param int $width the new paper size in postscript points (72 ppi).
 * @param int $height the new paper size in postscript points (72 ppi).
 * @param array $metaData array containing metadata fields
 *
 * @return string contents of pdf
 * @throws Exception when unable to execute or non-zero return code
 */

function svg2pdf($svgData, $width, $height, $metaData){
    $command = 'rsvg-convert -w ' .$width. ' -h '.$height.' -f pdf';
    $pipes = array();
    $descriptor_spec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w'),
    );

    $process = proc_open($command, $descriptor_spec, $pipes);
    if (!is_resource($process)) {
        throw new \Exception('Unable execute command: "'. $command . '". Details: ' . print_r(error_get_last(), true));
    }
    fwrite($pipes[0], $svgData);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value != 0) {
        throw new \Exception("$command returned $return_value, stdout: $out stderr: $err");
    }
    return addMetaData2Pdf($out,$metaData);
}

/**
 * use exiftool to add metadata to pdf
 *
 * @param string $pdf pdf as a string
 * @param array $docMeta array containg metadata fields
 * @return string contents of pdf
 * @throws Exception when unable to execute or non-zero return code
 */

function addMetaData2Pdf($pdf, $docmeta){
    $author = isset($docmeta['author']) ? addcslashes($docmeta['author'], "()\n\\") : 'XDMoD';
    $subject = isset($docmeta['subject']) ? addcslashes($docmeta['subject'], "()\n\\") : 'XDMoD chart';
    $title = isset($docmeta['title']) ? addcslashes($docmeta['title'], "()\n\\") :'XDMoD PDF chart export';
    $creator = addcslashes('XDMoD ' . OPEN_XDMOD_VERSION, "()\n\\");

    $command = "exiftool -Title='$title' -Author='$author' -Subject='$subject' -Creator='$creator' -o - -";

    $pipes = array();
    $descriptor_spec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w'),
    );

    $process = proc_open($command, $descriptor_spec, $pipes);
    if (!is_resource($process)) {
        throw new \Exception('Unable execute command: "'. $command . '". Details: ' . print_r(error_get_last(), true));
    }
    fwrite($pipes[0], $pdf);
    fclose($pipes[0]);

    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value != 0) {
        throw new \Exception("$command returned $return_value, stdout: $out stderr: $err");
    }
    return $out;

}
