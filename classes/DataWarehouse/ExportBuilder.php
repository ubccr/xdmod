<?php

namespace DataWarehouse;

use Exception;
use XMLWriter;
use XDUser;
use DataWarehouse\VisualizationBuilder;

/**
 * Singleton class for helping with data export.
 *
 * @author Amin Ghadersohi
 */
class ExportBuilder
{

    /**
     * Singleton instance.
     *
     * @var ExportBuilder
     */
    private static $_self = NULL;

    /**
     * Factory method.
     *
     * @return ExportBuilder
     */
    public static function getInstance()
    {
        if (static::$_self == NULL) {
            static::$_self = new static();
        }

        return static::$_self;
    }

    /**
     * Private constructor for factory pattern.
     */
    private function __construct()
    {
    }

    /**
     * Supported format names along with the content type and whether
     * the output is inline or an attachment.
     *
     * @var array
     */
    public static $supported_formats = array(
        'xls' => array(
            'render_as'   => 'application/vnd.ms-excel',
            'destination' => 'attachment',
        ) ,
        'xml' => array(
            'render_as'   => 'text/xml',
            'destination' => 'attachment',
        ) ,
        'png' => array(
            'render_as'   => 'image/png',
            'destination' => 'attachment',
        ) ,
        'png_inline' => array(
            'render_as'   => 'image/png',
            'destination' => 'inline',
        ) ,
        'svg_inline' => array(
            'render_as'   => 'image/svg+xml',
            'destination' => 'inline',
        ) ,
        'eps' => array(
            'render_as'   => 'image/eps',
            'destination' => 'attachment',
        ) ,
        'svg' => array(
            'render_as'   => 'image/svg+xml',
            'destination' => 'attachment',
        ) ,
        'csv' => array(
            'render_as'   => 'application/xls',
            'destination' => 'attachment',
        ) ,
        'jsonstore' => array(
            'render_as'   => 'text/plain',
            'destination' => 'inline',
        ) ,
        'hc_jsonstore' => array(
            'render_as'   => 'text/plain',
            'destination' => 'inline',
        ) ,
        'json' => array(
            'render_as'   => 'application/json',
            'destination' => 'inline',
        ) ,
        'session_variable' => array(
            'render_as'   => 'text/plain',
            'destination' => 'inline',
        ) ,
        'params' => array(
            'render_as'   => 'text/plain',
            'destination' => 'inline',
        ) ,
        'img_tag' => array(
            'render_as'   => 'text/html',
            'destination' => 'inline',
        ) ,
        'html' => array(
            'render_as'   => 'text/html',
            'destination' => 'inline',
        ) ,
        '_internal' => array(
            'render_as' => 'text/plain',
            'destination' => 'inline',
        ) ,
    );

    /**
     * Supported dataset action formats.
     *
     * @var array
     */
    public static $dataset_action_formats = array(
        'json',
        'xml',
        'xls',
        'csv'
    );

    /**
     * Determine the default format given an array of formats.
     *
     * @param array $arr An array of format names.
     *
     * @return string The default format name.
     *
     * @throws Exception If no default format is found.
     */
    public static function getDefault(array $arr = array())
    {
        $count = count($arr);

        if ($count > 0) {
            return $arr[0];
        }

        throw new Exception('No default format could be assigned');
    }

    /**
     * Get the HTTP header for a given format.
     *
     * @param string $format The name of the format.
     * @param bool $forceInline True if the output must be inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array An array of header strings.
     */
    public static function getHeader(
        $format,
        $forceInline = false,
        $filename = 'data'
    ) {
        $filename = str_replace(
            array(
                ' ',
                '/',
                '\\',
                '?',
                '%',
                '*',
                ':',
                '|',
                '"',
                '<',
                '>',
                '.',
                '\n',
                '\t',
                '\r',
            ),
            '_',
            $filename
        );

        $headers = array();

        if (isset(static::$supported_formats[$format])) {
            $headers['Content-type'] = static::$supported_formats[$format]['render_as'];

            if (static::$supported_formats[$format]['destination'] == 'attachment' && !$forceInline) {
                $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.' . $format . '"';
            }
        } else {
            $headers['Content-type'] = 'text/plain';
            $headers['Content-Disposition'] = 'attachment; filename="'.$filename.'.'.$format.'"';
        }

        return $headers;
    }

    /**
     * Ouput the HTTP header.
     *
     * @param string $format The name of the output format.
     * @param bool $forceInline True if the output must be inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     */
    public static function writeHeader(
        $format,
        $forceInline = false,
        $filename = 'data'
    ) {
        $headers = static::getHeader($format, $forceInline, $filename);

        error_log('Writing headers');

        foreach ($headers as $k => $v) {
            header("$k: $v");
        }
    }

    /**
     * Get the output format from a request.
     *
     * @param array $request The HTTP request data.
     * @param string $default The default output format
     *     (defaults to "jsonstore").
     * @param array $format_subset The allowed subset of formats.  If
     *     the format specified by the request is not in this array,
     *     the default format will be used.
     */
    public static function getFormat(
        array $request,
        $default = 'jsonstore',
        array $formats_subset = array()
    ) {
        $format = $default;

        if (isset($request['format'])) {
            $f = strtolower($request['format']);

            if (
                isset(static::$supported_formats[$f])
                && (
                    count($formats_subset) == 0
                    ||
                    (count($formats_subset) > 0 && array_search($f, $formats_subset) !== false)
                )
            ) {
                $format = $f;
            }
        }

        return $format;
    }

    /**
     * Export data.
     *
     * @param array $exportedDatas The data to export.
     * @param string $format The export format name.
     * @param bool $inline True if the output is inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array(headers => array of http headers, results => the encoded data to send)
     */
    public static function export(
        array $exportedDatas = array(),
        $format,
        $inline = true,
        $filename = 'data'
    ) {
        if(!in_array(strtolower($format), static::$dataset_action_formats)) {
            throw new \Exception("Unsupported export format $format");
        }
        $exportFunction = 'export' . ucfirst($format);
        return static::$exportFunction($exportedDatas, $inline, $filename);
    }

    /**
     * Export data in CSV format.
     *
     * @param array $exportedDatas The data to export.
     * @param bool $inline True if the output is inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array(headers => array of http headers, results => the encoded data to send)
     */
    private static function exportCsv(
        array $exportedDatas = array(),
        $inline = true,
        $filename = 'data'
    ) {
        $fp = fopen('php://temp/maxmemory:104857600', 'w');

        if(!$fp) {
            throw \Exception("Unable to open temporary file for csv file export");
        }

        foreach ($exportedDatas as $exportedData) {
            $headers  = $exportedData['headers'];
            $rows     = $exportedData['rows'];
            $duration = $exportedData['duration'];
            $title    = $exportedData['title'];
            $restrictedByRoles = \xd_utilities\array_get($exportedData, 'restrictedByRoles', false);

            $parameters
                = isset($exportedData['title2'])
                ? $exportedData['title2']
                : array();

            fputcsv($fp, array_keys($title));
            fputcsv($fp, $title);

            if (count($parameters) > 0) {
                fputcsv($fp, array_keys($parameters));

                foreach ($parameters as $parameters_label => $params) {
                    fputcsv($fp, $params);
                }
            }

            if ($restrictedByRoles) {
                fputcsv($fp, array($exportedData['roleRestrictionsMessage']));
            }

            fputcsv($fp, array_keys($duration));
            fputcsv($fp, $duration);
            fputcsv($fp, array('---------'));
            fputcsv($fp, $headers);

            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }

            fputcsv($fp, array('---------'));
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return array(
            "headers" => self::getHeader('csv', $inline, $filename),
            "results" => $csv
        );
    }

    /**
     * Export data in XLS format.
     *
     * Actually exports in the CSV format.
     *
     * @param array $exportedDatas The data to export.
     * @param bool $inline True if the output is inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array(headers => array of http headers, results => the encoded data to send)
     */
    private static function exportXls(
        array $exportedDatas = array(),
        $inline = true,
        $filename = 'data'
    ) {
        $fp = fopen('php://temp/maxmemory:104857600', 'w');

        foreach ($exportedDatas as $exportedData) {
            $headers  = $exportedData['headers'];
            $rows     = $exportedData['rows'];
            $duration = $exportedData['duration'];
            $title    = $exportedData['title'];
            $restrictedByRoles = \xd_utilities\array_get($exportedData, 'restrictedByRoles', false);

            $parameters
                = isset($exportedData['title2'])
                ? $exportedData['title2']
                : array();

            fputcsv($fp, array_keys($title));
            fputcsv($fp, $title);

            if (count($parameters) > 0) {
                fputcsv($fp, array_keys($parameters));

                foreach ($parameters as $parameters_label => $params) {
                    fputcsv($fp, $params);
                }
            }

            if ($restrictedByRoles) {
                fputcsv($fp, array($exportedData['roleRestrictionsMessage']));
            }

            fputcsv($fp, array_keys($duration));
            fputcsv($fp, $duration);
            fputcsv($fp, array('---------'));
            fputcsv($fp, $headers);

            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }

            fputcsv($fp, array('---------'));
        }

        rewind($fp);
        $xls = stream_get_contents($fp);
        fclose($fp);

        return array(
            "headers" => self::getHeader('xls', $inline, $filename),
            "results" => $xls
        );
    }

    /**
     * Export data in XML format.
     *
     * @param array $exportedDatas The data to export.
     * @param bool $inline True if the output is inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array(headers => array of http headers, results => the encoded data to send)
     */
    private static function exportXml(
        array $exportedDatas = array(),
        $inline = true,
        $filename = 'data'
    ) {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument();
        $xml->startElement('xdmod-xml-dataset');

        foreach ($exportedDatas as $exportedData) {
            $headers  = $exportedData['headers'];
            $rows     = $exportedData['rows'];
            $duration = $exportedData['duration'];
            $title    = $exportedData['title'];
            $restrictedByRoles = \xd_utilities\array_get($exportedData, 'restrictedByRoles', false);

            $parameters
                = isset($exportedData['title2'])
                ? $exportedData['title2']
                : array();

            $xml->startElement('header');

            foreach ($title as $title_label => $title_element) {
                $xml->startElement(static::formatElement($title_label));
                $xml->text($title_element);
                $xml->endElement();
            }

            foreach ($parameters as $parameters_label => $params) {
                $xml->startElement(static::formatElement($parameters_label));

                foreach ($params as $parameter) {
                    $xml->startElement('parameter');

                    $parameter_parts = explode('=', $parameter);

                    $xml->startElement('name');
                    $xml->text($parameter_parts[0]);
                    $xml->endElement();

                    $xml->startElement('value');
                    $xml->text($parameter_parts[1]);
                    $xml->endElement();

                    // End parameter.
                    $xml->endElement();
                }

                $xml->endElement();
            }

            if ($restrictedByRoles) {
                $xml->startElement(static::formatElement('restriction'));
                $xml->text($exportedData['roleRestrictionsMessage']);
                $xml->endElement();
            }

            foreach ($duration as $duration_label => $duration_element) {
                $xml->startElement(static::formatElement($duration_label));
                $xml->text($duration_element);
                $xml->endElement();
            }

            $xml->startElement('columns');

            foreach ($headers as $header) {
                $xml->startElement('column');
                $xml->text($header);
                $xml->endElement();
            }

            // End columns.
            $xml->endElement();

            // End header.
            $xml->endElement();

            $xml->startElement('rows');

            foreach ($rows as $row) {
                $xml->startElement('row');

                foreach ($row as $index => $cell) {

                    $xml->startElement('cell');

                    if (isset($headers[$index])) {
                        $xml->startElement('column');
                        $xml->text($headers[$index]);
                        $xml->endElement();
                    }

                    $xml->startElement('value');
                    $xml->text($cell);
                    $xml->endElement();

                    // End cell.
                    $xml->endElement();
                }

                // End row.
                $xml->endElement();
            }

            // End rows.
            $xml->endElement();
        }

        // End xdmod-xml-dataset.
        $xml->endElement();

        $xml->endDocument();

        return array(
            "headers" => self::getHeader('xml', $inline, $filename),
            "results" => $xml->outputMemory(true)
        );
    }

    /**
     * Export data in JSON format.
     *
     * @param array $exportedDatas The data to export.
     * @param bool $inline True if the output is inline.
     * @param string $filename The name of the file if it's an
     *     attachment.
     *
     * @return array(headers => array of http headers, results => the encoded data to send)
     */
    private static function exportJson(
        array $exportedDatas = array(),
        $inline = true,
        $filename = 'data'
    ) {
        $returnData = array();

        foreach ($exportedDatas as $exportedData) {
            $headers  = $exportedData['headers'];
            $rows     = $exportedData['rows'];
            $duration = $exportedData['duration'];
            $title    = $exportedData['title'];
            $restrictedByRoles = \xd_utilities\array_get($exportedData, 'restrictedByRoles', false);
            $roleRestrictionsMessage = \xd_utilities\array_get($exportedData, 'roleRestrictionsMessage', '');

            $parameters
                = isset($exportedData['title2'])
                ? $exportedData['title2']
                : array();

            foreach ($exportedDatas as $exportedData) {
                $returnData[] = array(
                    'title'      => $title,
                    'parameters' => $parameters,
                    'duration'   => json_encode($duration),
                    'headers'    => json_encode($headers),
                    'rows'       => json_encode($rows),
                    'restrictedByRoles' => $restrictedByRoles,
                    'roleRestrictionsMessage' => $roleRestrictionsMessage,
                );
            }
        }

        return array(
            "headers" => self::getHeader('json', $inline, $filename),
            "results" => json_encode($returnData)
        );
    }

    /**
     * Format a string to be used as an XML element name.
     *
     * @param string $name The string to format.
     *
     * @return string The formatted element name.
     */
    private static function formatElement($name)
    {
        $name = str_replace(' ', '_', $name);
        $name = str_replace(',', '', $name);
        $name = str_replace(':', '', $name);
        $name = str_replace('.', '', $name);

        return $name;
    }
}
