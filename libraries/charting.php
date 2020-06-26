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

    $base_filename = sys_get_temp_dir() . '/' . md5(rand() . microtime());

    // These files must have the proper extensions for PhantomJS.
    $output_image_filename = $base_filename . '.' . $format;
    $tmp_html_filename     = $base_filename . '.html';

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

    if ($format == 'png') {
        \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js png $tmp_html_filename $output_image_filename $effectiveWidth $effectiveHeight");
        $data = file_get_contents($output_image_filename);
        @unlink($output_image_filename);
        @unlink($tmp_html_filename);
        return $data;
    }

    if ($format == 'svg') {
        $svgContent = \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js svg $tmp_html_filename null ".$effectiveWidth." ".$effectiveHeight);
        @unlink($tmp_html_filename);
        return $svgContent;
    }

    if ($format == 'pdf') {
        \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js png $tmp_html_filename $output_image_filename $effectiveWidth $effectiveHeight");
        $data = getPdfWithMetadata($output_image_filename, round($width / 90.0 * 72.0), round($height / 90.0 * 72.0), $fileMetadata);
        @unlink($output_image_filename);
        @unlink($tmp_html_filename);
        return $data;
    }
}

/**
 * @param array $docmenta array with optional author, subject and title elements
 * @return string valid pdfmark postscript for use by ghostscript
 */
function getPdfMark($docmeta)
{
    $author = isset($docmeta['author']) ? addcslashes($docmeta['author'], "()\n\\") : 'XDMoD';
    $subject = isset($docmeta['subject']) ? addcslashes($docmeta['subject'], "()\n\\") : 'XDMoD chart';
    $title = isset($docmeta['title']) ? addcslashes($docmeta['title'], "()\n\\") :'XDMoD PDF chart export';
    $creator = addcslashes('XDMoD ' . OPEN_XDMOD_VERSION, "()\n\\");

    $pdfmark = <<<EOM
[ /Title ($title)
  /Author ($author)
  /Subject ($subject)
  /Creator ($creator)
  /DOCINFO pdfmark
EOM;
    return $pdfmark;
}

/**
 * Use ghostscript to add metadata to the PDF file and set the paper size correctly.
 *
 * @param string $pdfFilename of PDF file
 * @param int $widthPsPoints the new paper size in postscript points (72 ppi).
 * @param int $heightPsPoints the new paper size in postscript points (72 ppi).
 * @param array $docmeta array containing metadata fields
 *
 * @return string PDF document
 */
function getPdfWithMetadata($pdfFilename, $widthPsPoints, $heightPsPoints, $docmeta)
{
    $command = <<<EOC
gs -o- -sstdout=/dev/stderr -sDEVICE=pdfwrite \
    -dDEVICEWIDTHPOINTS=$widthPsPoints -dDEVICEHEIGHTPOINTS=$heightPsPoints \
    -dPDFFitPage -dFIXEDMEDIA -dAutoRotatePages=/None -dCompatibilityLevel=1.4 \
    $pdfFilename -
EOC;

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

    fwrite($pipes[0], getPdfMark($docmeta));
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
