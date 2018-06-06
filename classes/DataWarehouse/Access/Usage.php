<?php

namespace DataWarehouse\Access;

use Exception;

use DataWarehouse;
use DataWarehouse\Access\MetricExplorer;
use Models\Services\Acls;
use XDChartPool;
use XDUser;

/**
 * Adapts requests for Usage tab-style charts to use Metric Explorer's back end.
 *
 * This exists for backwards-compatibility reasons and should not be used by
 * any new code. This will be deleted once the Usage tab's controllers are
 * removed and the REST API has been converted to provide Metric Explorer-style
 * charts.
 */
class Usage extends Common
{
    /**
     * The default width of a Usage chart.
     */
    const DEFAULT_WIDTH = 740;

    /**
     * The default height of a Usage chart.
     */
    const DEFAULT_HEIGHT = 345;

    /**
     * The default scale of a Usage chart.
     */
    const DEFAULT_SCALE = 1.0;

    /**
     * Get charts by converting a Usage tab-style request to Metric Explorer
     * requests.
     *
     * @param  XDUser $user    The user making this request.
     * @param  string $chartsKey (Optional) The name of the parameter
     *                           containing chart data. (Defaults to 'data'.)
     * @return array           A response-like array containing:
     *                             results: The data to respond with.
     *                             headers: Headers to set on the response.
     */
    public function getCharts(XDUser $user, $chartsKey = 'data') {
        // Determine which realms are being requested.
        if (empty($this->request['realm'])) {
            throw new Exception('One or more realms must be specified.');
        }
        $requestedRealms = array_map('trim', explode(',', $this->request['realm']));

        // For each realm requested, get the requested charts.
        $usageCharts = array();
        foreach ($requestedRealms as $usageRealm) {
            // Set the realm on the request object for functions that look
            // at the request directly.
            $this->request['realm'] = $usageRealm;

            // Get the realm's query class.
            $usageRealmAggregateClass = "\\DataWarehouse\\Query\\$usageRealm\\Aggregate";

            // If present, move the dimension value into statistic.
            if (array_key_exists('dimension', $this->request)) {
                $this->request['group_by'] = $this->request['dimension'];
                unset($this->request['dimension']);
            }

            // Get the request's group by.
            $usageGroupBy = \xd_utilities\array_get($this->request, 'group_by', 'none');
            $usageGroupByObject = $usageRealmAggregateClass::getGroupBy($usageGroupBy);

            // Get whether or not the request is for a timeseries chart.
            $usageIsTimeseries = \xd_utilities\array_get($this->request, 'dataset_type') === 'timeseries';

            // Get the format to return the results in.
            $usageFormat = \xd_utilities\array_get($this->request, 'format', 'hc_jsonstore');
            $isJsonStoreExport = $usageFormat === 'jsonstore';
            $isCsvExport = $usageFormat === 'csv';
            $isXmlExport = $usageFormat === 'xml';
            $isTextExport =
                $isCsvExport
                || $isXmlExport
                || $usageFormat === 'json'
                || $isJsonStoreExport
            ;

            // If present, move the fact value into statistic.
            if (array_key_exists('fact', $this->request)) {
                $this->request['statistic'] = $this->request['fact'];
                unset($this->request['fact']);
            }

            // Get whether or not the request is for multiple metrics.
            $isSingleMetricQuery = array_key_exists('statistic', $this->request);

            // If this request is asking for timeseries data for multiple metrics
            // for a datasheet, return an error response.
            if (
                $usageIsTimeseries
                && !$isSingleMetricQuery
                && $isJsonStoreExport
            ) {
                return array(
                    'headers' => \DataWarehouse\ExportBuilder::getHeader($usageFormat),
                    'results' => json_encode(array(
                        "metaData" => array(
                            "totalProperty" => "total",
                            "root" => "records",
                            "id" => "id",
                            "fields" => array(
                                array(
                                    "name" => 'Message',
                                    "type" => 'string',
                                ),
                            ),
                        ),
                        "success" => true,
                        "message" => 'Datasheet view is not available for timeseries. Turn off timeseries or use the Export button to get the data.',
                        "total" => 1,
                        "records" => array(
                            array(
                                'Message' => 'Datasheet view is not available for timeseries. Turn off timeseries or use the Export button to get the data.',
                            ),
                        ),
                        "columns" => array(
                            array(
                                "header" => 'Error Message',
                                "width" => 600,
                                "dataIndex" => 'Message',
                                "sortable" => true,
                                'editable' => false,
                                'align' => 'left',
                                'renderer' => "CCR.xdmod.ui.stringRenderer",
                            ),
                        ),
                    )),
                );
            }

            // Convert the request options to a set of Metric Explorer requests.
            //
            // If one statistic was requested, convert it into a single ME request.
            // Otherwise, create one ME request for each available statistic.
            //
            // If the request was for aggregate charts and any statistic can't
            // be provided in that form, quietly change its chart to timeseries.
            $meRequests = array();
            $userStatistics = Acls::getPermittedStatistics($user, $usageRealm, $usageGroupBy);
            if ($isSingleMetricQuery) {
                if (!$usageIsTimeseries) {
                    $userStatisticObject = $usageRealmAggregateClass::getStatistic($this->request['statistic']);
                    if (!$userStatisticObject->usesTimePeriodTablesForAggregate()) {
                        $this->request['dataset_type'] = 'timeseries';
                        $this->request['display_type'] = 'line';
                        $this->request['swap_xy'] = false;
                    }
                }
                $meRequests[] = $this->convertChartRequest($this->request, $isTextExport);
            } else {
                foreach ($userStatistics as $userStatistic) {
                    $userStatisticObject = $usageRealmAggregateClass::getStatistic($userStatistic);
                    if (!$userStatisticObject->isVisible()) {
                        continue;
                    }

                    $statisticRequest = $this->request;
                    $statisticRequest['statistic'] = $userStatistic;
                    if (
                        !$usageIsTimeseries &&
                        !$userStatisticObject->usesTimePeriodTablesForAggregate()
                    ) {
                        // If a text-based export of aggregate data was requested
                        // and not all statistics can be provided as aggregates,
                        // return a failure response.
                        if ($isTextExport) {
                            return array(
                                'results' => json_encode(array(
                                    'success' => false,
                                    'message' => "Aggregate data not available for all metrics. Change to timeseries and try again.",
                                    'totalCount' => 0,
                                    $chartsKey => array(),
                                )),
                                'headers' => array(),
                            );
                        }

                        $statisticRequest['dataset_type'] = 'timeseries';
                        $statisticRequest['display_type'] = 'line';
                        $statisticRequest['swap_xy'] = false;
                    }
                    $meRequests[] = $this->convertChartRequest($statisticRequest, $isTextExport);
                }
            }

            // If this is a special format, condense the list of requests to a
            // single request.
            if ($isTextExport && !empty($meRequests)) {
                $firstMeRequest = null;
                foreach ($meRequests as &$meRequest) {
                    if ($firstMeRequest === null) {
                        $firstMeRequest = $meRequest;
                        continue;
                    }

                    $firstMeRequest['data_series_unencoded'][] = $meRequest['data_series_unencoded'][0];
                }
                $firstMeRequest['data_series'] = urlencode(json_encode($firstMeRequest['data_series_unencoded']));

                // Generate the title now, as we can't use the Metric Explorer
                // response's properties to easily generate the title.
                if ($isSingleMetricQuery) {
                    $specialFormatChartTitle = $usageRealmAggregateClass::getStatistic($this->request['statistic'])->getLabel();
                } else {
                    $specialFormatChartTitle = $usageRealm;
                }
                if ($usageGroupBy !== 'none') {
                    $specialFormatChartTitle .= ': by ' . $usageGroupByObject->getLabel();
                }
                $firstMeRequest['title'] = $specialFormatChartTitle;

                // Generate the filename based on how Usage previously did it.
                $firstMeRequest['filename'] = $this->generateFilename(
                    $specialFormatChartTitle,
                    $this->request['start_date'],
                    $this->request['end_date'],
                    $usageIsTimeseries
                );

                $meRequests = array($firstMeRequest);
            }

            // Run the Metric Explorer chart generator on each request.
            $meResponses = array();
            foreach ($meRequests as $meRequest) {
                $meGenerator = new MetricExplorer($meRequest);
                $meResponses[] = $meGenerator->get_data($user);
            }

            // If no charts were generated, return a failure response.
            if (empty($meResponses)) {
                return array(
                    'results' => json_encode(array(
                        'success' => false,
                        'message' => 'No charts could be generated using the given parameters.',
                        'totalCount' => 0,
                        $chartsKey => array(),
                    )),
                    'headers' => array(),
                );
            }

            // If this is a text export, perform special handling of the response.
            if ($isTextExport) {

                $meResponse = $meResponses[0];

                // If this is a JSON store export...
                if ($isJsonStoreExport) {

                    // Combine multiple results into a single object to return.
                    $meRequest = $meRequests[0];
                    $meRequestIsTimeseries = $meRequest['timeseries'];
                    $emptyResultFound = false;
                    $combinedResult = null;
                    $combinedResultColumns = null;
                    $combinedResultFields = null;
                    $combinedResultRecords = array();
                    $combinedResultFirstColumn = null;
                    $combinedResultRestricted = false;
                    foreach ($meResponse['results'] as $meJsonResult) {
                        $meResult = json_decode($meJsonResult, true);
                        if ($combinedResultFirstColumn === null) {
                            $combinedResultFirstColumn = $meResult['metaData']['fields'][0]['name'];
                        }

                        if ($meResult['records'] == array(array('Message' => 'Dataset is empty'))) {
                            $emptyResultFound = true;
                            continue;
                        }

                        if ($meRequestIsTimeseries) {
                            $combinedResultRecords = $meResult['records'];
                        } else {
                            foreach ($meResult['records'] as $meResultRecord) {
                                $meResultRecordFirstValue = $meResultRecord[$combinedResultFirstColumn];
                                if (array_key_exists($meResultRecordFirstValue, $combinedResultRecords)) {
                                    $combinedResultRecords[$meResultRecordFirstValue] = array_merge(
                                        $combinedResultRecords[$meResultRecordFirstValue],
                                        $meResultRecord
                                    );
                                } else {
                                    $combinedResultRecords[$meResultRecordFirstValue] = $meResultRecord;
                                }
                            }
                        }

                        if (\xd_utilities\array_get($meResult, 'restrictedByRoles', false)) {
                            $combinedResultRestricted = true;
                        }

                        if ($combinedResult === null) {
                            $combinedResult = $meResult;
                            $combinedResultFields = $meResult['metaData']['fields'];
                            $combinedResultColumns = $meResult['columns'];
                            continue;
                        }

                        $combinedResultFields[] = $meResult['metaData']['fields'][1];
                        $combinedResultColumns[] = $meResult['columns'][1];
                    }

                    // If no results were returned, return a failure response.
                    // Otherwise, finish combining the results.
                    if ($combinedResult === null) {
                        if ($emptyResultFound) {
                            $failureResults = reset($meResponse['results']);
                        } else {
                            $failureResults = json_encode(array(
                                'success' => false,
                            ));
                        }

                        return array(
                            'headers' => $meResponse['headers'],
                            'results' => $failureResults,
                        );
                    }

                    if ($meRequestIsTimeseries) {
                        $timeseriesTemplateField = $combinedResultFields[2];
                        $timeseriesTemplateColumn = $combinedResultColumns[2];

                        $timeseriesGroupByColumnName = $combinedResultColumns[1]['dataIndex'];
                        $valueColumnName = $timeseriesTemplateColumn['dataIndex'];

                        $combinedResultFields = array_slice($combinedResultFields, 0, 1);
                        $combinedResultColumns = array_slice($combinedResultColumns, 0, 1);

                        $timeseriesFields = array();
                        $nextFieldNameIndex = 0;
                        $timeseriesColumns = array();
                        $timeseriesRecords = array();
                        foreach ($combinedResultRecords as $resultRecord) {
                            $resultRecordDimension = $resultRecord[$timeseriesGroupByColumnName];

                            if (!array_key_exists($resultRecordDimension, $timeseriesColumns)) {
                                $timeseriesDimensionColumnName = "dimension_column_$nextFieldNameIndex";
                                $nextFieldNameIndex++;

                                $timeseriesColumn = $timeseriesTemplateColumn;
                                $timeseriesColumn['header'] = "[${resultRecordDimension}] " . $timeseriesColumn['header'];
                                $timeseriesColumn['dataIndex'] = $timeseriesDimensionColumnName;
                                $timeseriesColumns[$resultRecordDimension] = $timeseriesColumn;

                                $timeseriesField = $timeseriesTemplateField;
                                $timeseriesField['name'] = $timeseriesDimensionColumnName;
                                $timeseriesFields[$resultRecordDimension] = $timeseriesField;
                            } else {
                                $timeseriesDimensionColumnName = $timeseriesColumns[$resultRecordDimension]['dataIndex'];
                            }

                            $timeseriesRecordTime = $resultRecord[$combinedResultFirstColumn];
                            $timeseriesRecord = array(
                                $combinedResultFirstColumn => $timeseriesRecordTime,
                                $timeseriesDimensionColumnName => $resultRecord[$valueColumnName],
                            );

                            if (array_key_exists($timeseriesRecordTime, $timeseriesRecords)) {
                                $timeseriesRecords[$timeseriesRecordTime] = array_merge(
                                    $timeseriesRecords[$timeseriesRecordTime],
                                    $timeseriesRecord
                                );
                            } else {
                                $timeseriesRecords[$timeseriesRecordTime] = $timeseriesRecord;
                            }
                        }

                        $combinedResultFields = array_merge(
                            $combinedResultFields,
                            array_values($timeseriesFields)
                        );
                        $combinedResultColumns = array_merge(
                            $combinedResultColumns,
                            array_values($timeseriesColumns)
                        );
                        $combinedResultRecords = $timeseriesRecords;
                    }

                    // Sort the value columns by title.
                    $combinedResultKeyColumns = array_slice($combinedResultColumns, 0, 1);
                    $combinedResultValueColumns = array_slice($combinedResultColumns, 1);
                    usort($combinedResultValueColumns, function ($a, $b) {
                        return strcmp($a['header'], $b['header']);
                    });
                    $combinedResultColumns = array_merge(
                        $combinedResultKeyColumns,
                        $combinedResultValueColumns
                    );

                    // Store the combined results in the object to be returned.
                    $combinedResult['metaData']['fields'] = $combinedResultFields;
                    $combinedResult['columns'] = $combinedResultColumns;
                    $combinedResult['records'] = array_values($combinedResultRecords);
                    $combinedResult['restrictedByRoles'] = $combinedResultRestricted;

                    // Get the dimension and metric descriptions.
                    $jsonStoreMessage = '<ul>';
                    $jsonStoreMessage .= '<li>' . $usageGroupByObject->getDescription() . '</li>';

                    $jsonStoreMessageMetricDescriptions = array();
                    foreach ($meRequest['data_series_unencoded'] as $meRequestDataSeries) {
                        $jsonStoreMessageMetricDescriptions[] = '<li>' . $usageRealmAggregateClass::getStatistic($meRequestDataSeries['metric'])->getDescription($usageGroupByObject) . '</li>';
                    }
                    sort($jsonStoreMessageMetricDescriptions);
                    $jsonStoreMessage .= implode('', $jsonStoreMessageMetricDescriptions);

                    $jsonStoreMessage .= '</ul>';
                    $combinedResult['message'] = $jsonStoreMessage;

                    // Sort the datasheet appropriately.
                    if ($meRequestIsTimeseries || !$isSingleMetricQuery) {
                        $sortField = $combinedResultFirstColumn;
                        $sortDirection = 'asc';
                    } else {
                        $meRequestSortType = $meRequest['data_series_unencoded'][0]['sort_type'];

                        if (\xd_utilities\string_begins_with($meRequestSortType, 'value')) {
                            $sortField = $combinedResult['metaData']['fields'][1]['name'];
                        } else {
                            $sortField = $combinedResultFirstColumn;
                        }

                        if (\xd_utilities\string_ends_with($meRequestSortType, 'desc')) {
                            $sortDirection = 'desc';
                        } else {
                            $sortDirection = 'asc';
                        }
                    }

                    $combinedResult['metaData']['sortInfo'] = array(
                        'field' => $sortField,
                        'direction' => $sortDirection,
                    );

                    // Set the results to the combined result.
                    $meResponse['results'] = json_encode($combinedResult);
                }

                return $meResponse;
            }

            // Get attributes common to all charts that will be returned.
            $usageTitle = \xd_utilities\array_get($this->request, 'title');
            $usageSubtitle = \xd_utilities\array_get($this->request, 'subtitle');
            $usageOffset = intval(\xd_utilities\array_get($this->request, 'offset', 0));
            $usageWidth = intval(\xd_utilities\array_get($this->request, 'width', self::DEFAULT_WIDTH));
            $usageHeight = intval(\xd_utilities\array_get($this->request, 'height', self::DEFAULT_HEIGHT));
            $usageFontSize = intval(\xd_utilities\array_get($this->request, 'font_size', 3));
            $usageShowGradient = \xd_utilities\array_get($this->request, 'show_gradient', 'y');
            $usageShowGradient = $usageShowGradient === 'true' || $usageShowGradient === 'y';
            $thumbnailRequested = \xd_utilities\array_get($this->request, 'thumbnail', 'n') === 'y';
            $showTitle = \xd_utilities\array_get($this->request, 'show_title', 'y');

            // Generate the chart settings that will be returned with each chart.
            $usageChartSettings = array(
                'dataset_type' =>           \xd_utilities\array_get($this->request, 'dataset_type', $usageGroupByObject->getDefaultDatasetType()),
                'display_type' =>           \xd_utilities\array_get($this->request, 'display_type', $usageGroupByObject->getDefaultDisplayType($usageGroupByObject->getDefaultDatasetType())),
                'combine_type' =>           \xd_utilities\array_get($this->request, 'combine_type', $usageGroupByObject->getDefaultCombineMethod()),
                'show_legend' =>            \xd_utilities\array_get($this->request, 'show_legend', $usageGroupByObject->getDefaultShowLegend()),
                'show_guide_lines' =>       \xd_utilities\array_get($this->request, 'show_guide_lines', $usageGroupByObject->getDefaultShowGuideLines()),
                'log_scale' =>              \xd_utilities\array_get($this->request, 'log_scale', $usageGroupByObject->getDefaultLogScale()),
                'limit' =>                  \xd_utilities\array_get($this->request, 'limit', $usageGroupByObject->getDefaultLimit()),
                'offset' =>                 \xd_utilities\array_get($this->request, 'offset', $usageGroupByObject->getDefaultOffset()),
                'show_trend_line' =>        \xd_utilities\array_get($this->request, 'show_trend_line', $usageGroupByObject->getDefaultShowTrendLine()),
                'show_error_bars' =>        \xd_utilities\array_get($this->request, 'show_error_bars', $usageGroupByObject->getDefaultShowErrorBars()),
                'show_aggregate_labels' =>  \xd_utilities\array_get($this->request, 'show_aggregate_labels', $usageGroupByObject->getDefaultShowAggregateLabels()),
                'show_error_labels' =>      \xd_utilities\array_get($this->request, 'show_error_labels', $usageGroupByObject->getDefaultShowErrorLabels()),
                'hide_tooltip' =>           \xd_utilities\array_get($this->request, 'hide_tooltip', false),
                'enable_errors' =>          'n',
                'enable_trend_line' =>      \xd_utilities\array_get($this->request, 'enable_trend_line', $usageGroupByObject->getDefaultEnableTrendLine()),
            );

            $usageSubnotes = array();
            if ($usageGroupBy === 'resource' || array_key_exists('resource', $this->request)) {
                $usageSubnotes[] = '* Resources marked with asterisk do not provide processor'
                    . ' counts per job when submitting to the '
                    . ORGANIZATION_NAME . ' Central Database. This affects the'
                    . ' accuracy of the following (and related) statistics: Job'
                    . ' Size and CPU Consumption';
            }

            // Generate the title style that will be used for all charts.
            $usageTitleFontSizeInPixels = 16 + $usageFontSize;
            $usageTitleStyle = array(
                'color' => '#000000',
                'fontSize' => "${usageTitleFontSizeInPixels}px",
            );

            // Get the user's report generator chart pool.
            $chartPool = new XDChartPool($user);

            // Convert each Metric Explorer response into a Usage chart.
            foreach ($meResponses as $meResponseIndex => $meResponse) {
                // If the response indicates failure, skip this response.
                $meResponseContent = $meResponse['results'];
                if (!$meResponseContent['success']) {
                    continue;
                }

                // Get the request for this chart.
                $meRequest = $meRequests[$meResponseIndex];
                $meRequestIsTimeseries = $meRequest['timeseries'];

                // Get the statistic object used by this chart request.
                $meRequestMetric = $usageRealmAggregateClass::getStatistic($meRequest['data_series_unencoded'][0]['metric']);

                $errorstat = 'sem_' . $meRequest['data_series_unencoded'][0]['metric'];
                if (in_array($errorstat, array_keys($usageRealmAggregateClass::getRegisteredStatistics())) ) {
                    $usageChartSettings['enable_errors'] = 'y';
                }

                // Get the chart object from the response.
                $meChart = &$meResponseContent['data'][0];

                // Extract the report generator options.
                $meReportGeneratorMeta = \xd_utilities\array_extract($meChart, 'reportGeneratorMeta', array());

                // Replace the in-chart descriptions with empty arrays.
                $meChart['dimensions'] = array();
                $meChart['metrics'] = array();

                // Grab the dimension and metric and generate a formatted group
                // and metric description.
                $usageChartDimensionDescription = $usageGroupByObject->getDescription();
                $usageChartMetricDescription = $meRequestMetric->getDescription($usageGroupByObject);

                // If specified, use the given subtitle. Otherwise, get the
                // subtitle from the title of the resulting chart.
                //
                // Because the function doesn't receive a custom title, if any,
                // from this adapter, the chart's subtitle is placed in the title.
                $usageChartSubtitle = $usageSubtitle !== null ? $usageSubtitle : $meChart['title']['text'];

                // Generate the title and short title of this chart.
                $usageChartShortTitle = $meRequestMetric->getLabel();
                if ($usageTitle !== null) {
                    $usageChartTitle = $usageTitle;
                } else {
                    $usageChartTitle = $usageChartShortTitle;
                    if ($usageGroupBy !== 'none') {
                        $usageChartTitle .= ': by ' . $usageGroupByObject->getLabel();
                    }
                }

                // If a thumbnail was requested, do not use an in-chart title or subtitle.
                // Otherwise, use one.
                if ($thumbnailRequested) {
                    $meChart['title']['text'] = '';
                    $meChart['subtitle']['text'] = '';
                } else {
                    // If a title was provided, display that. Otherwise, use the
                    // generated title.
                    $meChart['title']['text'] = $usageChartTitle;
                    $meChart['subtitle']['text'] = $usageChartSubtitle;
                }

                // Set the title style.
                $meChart['title']['style'] = $usageTitleStyle;

                // If the "Show Title" checkbox on the Export Dialog has not been ticked,
                // do not show a chart title. However, the Metric Explorer promotes the
                // subtitle to the title if it exists and the title is not shown so mimic
                // this behavior for consistency. See HighChart2::setChartTitleSubtitle()

                if ( 'n' == $showTitle ) {
                    // The subtitle text is empty for thumbnails but above it is set to
                    // the value of the 'subtitle' parameter or the chart title if the
                    // parameter isn't present. Keep this check in here in case that
                    // changes.

                    if ( isset($meChart['subtitle']['text']) && '' != $meChart['subtitle']['text'] ) {
                        $meChart['title']['text'] = $meChart['subtitle']['text'];
                        $meChart['subtitle']['text'] = '';
                    } else {
                        $meChart['title']['text'] = '';
                    }
                }

                // Generate the expected IDs for the chart.
                $usageMetric = $meRequest['data_series_unencoded'][0]['metric'];
                $usageChartId = "statistic_${usageRealm}_${usageGroupBy}_${usageMetric}";
                $usageChartMenuId = "group_by_${usageRealm}_${usageGroupBy}";

                // Remove extraneous x-axis properties.
                if ($meRequestIsTimeseries) {
                    unset($meChart['xAxis']['title']);
                } else {
                    unset($meChart['xAxis']['title']['text']);
                    unset($meChart['xAxis']['labels']['staggerLines']);
                }
                unset($meChart['xAxis']['otitle']);
                unset($meChart['xAxis']['dtitle']);

                // set x-axis title, if any, bold:
                if (isset($meChart['xAxis']['title']['style'])) {
                    $meChart['xAxis']['title']['style']['fontWeight'] = 'bold';
                }

                // If there is a y-axis...
                if (isset($meChart['yAxis'][0])) {
                    // Remove extraneous y-axis properties.
                    unset($meChart['yAxis'][0]['otitle']);
                    unset($meChart['yAxis'][0]['dtitle']);
                    unset($meChart['yAxis'][0]['opposite']);
                    unset($meChart['yAxis'][0]['max']);
                    unset($meChart['yAxis'][0]['startOnTick']);

                    // If a thumbnail was requested, remove the y-axis label.
                    if ($thumbnailRequested) {
                        $meChart['yAxis'][0]['title']['text'] = '';
                    }

                    // set y-axis title, if any, bold:
                    if (isset($meChart['yAxis'][0]['title']['style'])) {
                        $meChart['yAxis'][0]['title']['style']['fontWeight'] = 'bold';
                    }

                    // Fix the x-axis labels to be the same size as the y-axis labels.
                    $meChart['xAxis']['labels']['style']['fontSize'] =
                        $meChart['yAxis'][0]['labels']['style']['fontSize'];

                    // Restore the last y-axis label.
                    $meChart['yAxis'][0]['showLastLabel'] = true;

                    // Set the y-axis grid line dash style and color.
                    if ($meRequestIsTimeseries) {
                        $meChart['yAxis'][0]['gridLineDashStyle'] = 'Solid';
                        $meChart['yAxis'][0]['gridLineColor'] = '#C0C0C0';
                    } else {
                        unset($meChart['yAxis'][0]['gridLineDashStyle']);
                        unset($meChart['yAxis'][0]['gridLineColor']);
                    }
                    if ($usageChartSettings['show_guide_lines'] === 'n') {
                        $meChart['yAxis'][0]['gridLineWidth'] = 0;
                    }
                }

                // If there are x-axis categories, they are sorted by value,
                // and this is a grouped chart, enumerate them with rank.
                $chartSortedByValue = \xd_utilities\string_begins_with(
                    $meRequest['data_series_unencoded'][0]['sort_type'],
                    'value'
                );
                if (
                    isset($meChart['xAxis']['categories'])
                    && $chartSortedByValue
                    && $usageGroupBy !== 'none'
                ) {
                    $meChartCategories = $meChart['xAxis']['categories'];
                    $usageChartCategories = array();
                    $currentCategoryRank = $usageOffset + 1;
                    foreach ($meChartCategories as $meChartCategory) {
                        $usageChartCategories[] = "${currentCategoryRank}. ${meChartCategory}";
                        $currentCategoryRank++;
                    }
                    $meChart['xAxis']['categories'] = $usageChartCategories;
                }

                // Generate the chart arguments string for the report generator.
                //
                // If the controller module was specified in the request, ensure
                // it is at the front of the arguments string. The rest of the
                // parameters should be sorted by key.
                $usageChartArgs = $this->request;
                unset($usageChartArgs['height']);
                unset($usageChartArgs['scale']);
                unset($usageChartArgs['show_title']);
                unset($usageChartArgs['width']);
                ksort($usageChartArgs);
                if (array_key_exists('controller_module', $usageChartArgs)) {
                    $usageChartArgs = array(
                        'controller_module' => $usageChartArgs['controller_module'],
                    ) + $usageChartArgs;
                }
                $usageChartArgsStr = urldecode(http_build_query($usageChartArgs));

                // Check if the user's chart pool contains this chart.
                $usageChartInPool = $chartPool->chartExistsInQueue($usageChartArgsStr);
                $queryDescripter = Acls::getQueryDescripters(
                    $user,
                    $usageRealm,
                    $usageGroupBy,
                    $meRequestMetric->getAlias()->getName()
                );
                $drillTargets = $queryDescripter->getDrillTargets($meRequestMetric->getAlias());
                $drillDowns = array_map(
                    function ($drillTarget) {
                        return explode('-', $drillTarget, 2);
                    },
                    $drillTargets
                );

                // For each data series...
                $primaryDataSeriesRank = $usageOffset;

                array_walk($meChart['series'], function (
                    &$meDataSeries,
                    $meDataSeriesIndex
                ) use (
                    $usageRealm,
                    $usageGroupBy,
                    $drillDowns,
                    $meRequestIsTimeseries,
                    $thumbnailRequested,
                    $meRequest,
                    $meRequestMetric,
                    $usageGroupByObject,
                    $user,
                    &$primaryDataSeriesRank,
                    $chartSortedByValue
                ) {
                    // Determine the type of this data series.
                    $isTrendLineSeries = \xd_utilities\string_begins_with($meDataSeries['name'], 'Trend Line: ');
                    $isStdErrSeries = \xd_utilities\array_get($meDataSeries, 'type') === 'errorbar';
                    $isPrimaryDataSeries = !($isTrendLineSeries || $isStdErrSeries);

                    // If this is a primary data series, increment the rank of the
                    // current primary data series. Further, if this chart is
                    // a timeseries chart, it is sorted by value, and it is a
                    // grouped chart, add the rank to the series label.
                    if ($isPrimaryDataSeries) {
                        $primaryDataSeriesRank++;
                        if (
                            $meRequestIsTimeseries
                            && $chartSortedByValue
                            && $usageGroupBy !== 'none'
                        ) {
                            $meDataSeries['name'] = "${primaryDataSeriesRank}. " . $meDataSeries['name'];
                        }
                    }

                    // If this is the primary data series, modify the data labels
                    // and don't specify the line style. Otherwise, just remove
                    // the data labels.
                    if ($isPrimaryDataSeries) {
                        $meDataSeries['dataLabels']['y'] = -90;
                        unset($meDataSeries['dashStyle']);
                    } else {
                        unset($meDataSeries['dataLabels']);
                    }

                    // If this is the primary data series and the chart is not a
                    // thumbnail, use line markers if and only if the number of
                    // data points is less than or equal to 30,
                    // or if there's a single y series data point.
                    if ($isPrimaryDataSeries && !$thumbnailRequested) {
                        // is there a single y data point?
                        $y_values_count = 0;
                        foreach ($meDataSeries['data'] as $value) {
                            if ($value['y'] !== null ) {
                                ++$y_values_count;
                            }
                            // we are only interested in the == 1 case
                            if ($y_values_count > 1) {
                                break;
                            }
                        }
                        $meDataSeries['marker']['enabled'] = $y_values_count == 1 ||
                                            count($meDataSeries['data']) <= 30;
                    }

                    // If this is a trend line data series...
                    if ($isTrendLineSeries) {
                        // Change the line style to a dotted line.
                        $meDataSeries['dashStyle'] = 'ShortDot';
                    }

                    if (!$isTrendLineSeries && !$thumbnailRequested) {
                        $meDataSeries['drilldown']['drilldowns'] = $drillDowns;
                        $meDataSeries['drilldown']['realm'] = $usageRealm;
                        $meDataSeries['drilldown']['groupUnit'] = array($usageGroupBy, $usageGroupByObject->getUnit());
                    }

                    // Set properties that are different.
                    $meDataSeries['zIndex'] = $meDataSeriesIndex + 2;

                    // Remove extraneous properties.
                    unset($meDataSeries['otitle']);
                    unset($meDataSeries['datasetId']);
                    unset($meDataSeries['visible']);
                    unset($meDataSeries['events']);

                    // Note: keep dataLabels color param set, else we lose some of the pie datalabels
                    // in the Usage chart only.
                    if ($meDataSeries['type'] === 'pie') {
                        unset($meDataSeries['dataLabels']['y']);
                        unset($meDataSeries['dataLabels']['style']['color']);
                    }
                });

                if ('n' == $usageGroupByObject->getDefaultEnableErrors()) {
                    $usageChartSettings['enable_errors'] = 'n';
                }

                // Create a Usage-style chart.
                $usageChart = array(
                    'hc_jsonstore' => $meChart,
                    'query_time' => '',
                    'query_string' => '',
                    'title' => $usageChartTitle,
                    'params_title' => html_entity_decode($usageChartSubtitle),
                    'comments' => 'comments',
                    'chart_args' => $usageChartArgsStr,
                    'reportGeneratorMeta' => array(
                        'included_in_report' => $usageChartInPool ? 'y' : 'n',
                    ),
                    'short_title' => $usageChartShortTitle,
                    'random_id' => 'chart_' . mt_rand(), //TODO: Use a definitively-unique ID for inserting chart HTML tags.
                    'subnotes' => $usageSubnotes,
                    'group_description' => $usageChartDimensionDescription,
                    'description' => $usageChartMetricDescription,
                    'id' => $usageChartId,
                    'menu_id' => $usageChartMenuId,
                    'realm' => $usageRealm,
                    'start_date' => $this->request['start_date'],
                    'end_date' => $this->request['end_date'],
                    'chart_settings' => json_encode($usageChartSettings),
                    'show_gradient' => $usageShowGradient,
                    'final_width' => $usageWidth,
                    'final_height' => $usageHeight - 4,
                    'sort_type' => $meRequest['data_series_unencoded'][0]['sort_type'],
                );
                foreach ($meReportGeneratorMeta as $reportKey => $reportValue) {
                    if (
                        $reportKey === 'included_in_report'
                        || array_key_exists($reportKey, $usageChart)
                    ) {
                        continue;
                    }

                    $usageChart[$reportKey] = $reportValue;
                }

                // Add the chart to the set of charts to return.
                $usageCharts[] = $usageChart;
            }
        }

        // Sort the results by short title.
        usort($usageCharts, function ($a, $b) {
            return strcmp($a['short_title'], $b['short_title']);
        });

        // Get the format to return the results in.
        if ($usageFormat === 'session_variable') {
            $usageFormat = 'hc_jsonstore';
        }

        // Get the file name to use for the results.
        $usageFileNameTitle = $usageCharts[0]['hc_jsonstore']['title']['text'];
        if (empty($usageFileNameTitle)) {
            $usageFileNameTitle = 'untitled';
        }
        $usageFileName = $this->generateFilename(
            $usageFileNameTitle,
            $this->request['start_date'],
            $this->request['end_date'],
            $usageIsTimeseries
        );

        // Return the results in a response-like array.
        if ($usageFormat !== 'hc_jsonstore') {
            $chartsKey = 'data';
            $usageCharts = array_map(function ($usageChart) {
                return $usageChart['hc_jsonstore'];
            }, $usageCharts);
        }
        return $this->exportImage(
            array(
                'success' => true,
                'message' => 'success',
                'totalCount' => count($usageCharts),
                $chartsKey => $usageCharts,
            ),
            $usageWidth,
            $usageHeight,
            \xd_utilities\array_get($this->request, 'scale', self::DEFAULT_SCALE),
            $usageFormat,
            $usageFileName
        );
    }

    /**
     * Convert a Usage chart request into a Metric Explorer chart request.
     *
     * Logic adapted from: html/gui/js/modules/Usage.js
     *
     * @param  array  $usageRequest The request to convert.
     * @param  boolean $useGivenFormat If true, the resulting request will
     *                                 ask for the same format as the original
     *                                 request. Otherwise, an internal format
     *                                 will be requested, allowing the response
     *                                 to be manipulated as a PHP object.
     * @return array                A request array that can be used for
     *                              generating a Metric Explorer chart.
     */
    private function convertChartRequest(array $usageRequest, $useGivenFormat) {
        // Start with a Metric Explorer request pre-filled with defaults
        // not present in Usage requests.
        $meRequest = array(
            'show_title' => true,
            'show_filters' => true,
            'format' => '_internal',
            'show_remainder' => true,
            'data_series' => array(
                array(
                    'id' => lcg_value(),
                    'x_axis' => false,
                    'has_std_err' => 'y',
                    'filters' => array(
                        'data' => array(),
                        'total' => 0,
                    ),
                    'ignore_global' => false,
                    'long_legend' => true,
                ),
            ),
        );

        // Set some convenience references.
        $meRequestDataOptions = &$meRequest['data_series'][0];

        // Get the active role by looking at the query group and truncating
        // '_usage' from the end.
        $usageQueryGroup = \xd_utilities\array_get($usageRequest, 'query_group');
        if (!empty($usageQueryGroup)) {
            $meRequest['active_role'] = substr($usageQueryGroup, 0, -6);
        }

        // Get the display type and axis layout from the Usage display type.
        $usageDisplayType = \xd_utilities\array_get($usageRequest, 'display_type');
        $meRequestDataOptions['display_type'] = (
            $usageDisplayType === 'bar'
            || $usageDisplayType === 'h_bar'
            || $usageDisplayType === 'auto'
        ) ? 'column' : $usageDisplayType;
        $meRequest['swap_xy'] = $usageDisplayType === 'h_bar';

        // Get the data combine type from the Usage combine type.
        $usageCombineType = \xd_utilities\array_get($usageRequest, 'combine_type');
        $meRequestDataOptions['combine_type'] = (
            $usageCombineType === 'side'
            || $usageCombineType === 'auto'
        ) ? 'side' : (
            $usageCombineType === 'percentage' ? 'percent' : 'stack'
        );

        // Get the global filters from any present Usage filter parameters.
        $usageFilterSuffix = '_filter';
        $usageFilterSuffixLength = strlen($usageFilterSuffix);
        $usageRealm = \xd_utilities\array_get($usageRequest, 'realm');
        $meFilters = array();
        foreach ($usageRequest as $usageKey => $usageValue) {
            if (!\xd_utilities\string_ends_with($usageKey, $usageFilterSuffix)) {
                continue;
            }

            $usageFilterType = substr($usageKey, 0, -$usageFilterSuffixLength);
            $usageFilterValues = explode(',', $usageValue);

            foreach ($usageFilterValues as $usageFilterValue) {
                $meFilters[] = array(
                    'id' => "$usageFilterType=$usageFilterValue",
                    'value_id' => $usageFilterValue,
                    'dimension_id' => $usageFilterType,
                    'realms' => array($usageRealm),
                    'checked' => true,
                );
            }
        }

        // Create global filters from any Usage drilldowns.
        $realmAggregateClass = "\\DataWarehouse\\Query\\$usageRealm\\Aggregate";
        $realmAggregateClass::registerGroupBys();
        $realmGroupByClasses = $realmAggregateClass::getRegisteredGroupBys();
        $realmGroupByNames = array_map(function ($realmGroupByClass) {
            $realmGroupBy = new $realmGroupByClass();
            return $realmGroupBy->getName();
        }, $realmGroupByClasses);
        foreach ($usageRequest as $usageKey => $usageValue) {
            if (!in_array($usageKey, $realmGroupByNames)) {
                continue;
            }

            $meFilters[] = array(
                'id' => "$usageKey=$usageValue",
                'value_id' => $usageValue,
                'dimension_id' => $usageKey,
                'realms' => array($usageRealm),
                'checked' => true,
            );
        }

        // Store the global filters in the Metric Explorer request.
        $meRequest['global_filters'] = array(
            'data' => $meFilters,
            'total' => count($meFilters),
        );

        // Get the sort order from the defaults for the requested group by
        // and metric.
        $sortMechanism = SORT_DESC;
        $usageGroupBy = \xd_utilities\array_get($usageRequest, 'group_by', 'none');
        $usageMetric = \xd_utilities\array_get($usageRequest, 'statistic');
        $usageGroupByObject = $realmAggregateClass::getGroupBy($usageGroupBy);
        $usageGroupByOrder = $usageGroupByObject->getOrderByStatOption();
        if ($usageGroupByOrder !== SORT_DESC) {
            $sortMechanism = $usageGroupByOrder;
        } else {
            $usageMetricObject = $realmAggregateClass::getStatistic($usageMetric);
            $sortMechanism = $usageMetricObject->getOrderByStatOption();
        }

        switch ($sortMechanism) {
            case SORT_ASC:
                $sortOrder = 'value_asc';
                break;
            case SORT_DESC:
                $sortOrder = 'value_desc';
                break;
            default:
                $sortOrder = 'label_asc';
                break;
        }
        $meRequestDataOptions['sort_type'] = $sortOrder;

        // Use the given format, if enabled.
        if ($useGivenFormat) {
            $meRequest['format'] = \xd_utilities\array_get($usageRequest, 'format', 'hc_jsonstore');
        }

        // Perform simple parameter conversions.
        $meRequest['timeseries'] = \xd_utilities\array_get($usageRequest, 'dataset_type') === 'timeseries';
        $meRequest['legend_type'] = \xd_utilities\array_get($usageRequest, 'legend_type');
        $meRequest['font_size'] = \xd_utilities\array_get($usageRequest, 'font_size');
        $meRequest['height'] = \xd_utilities\array_get($usageRequest, 'height', self::DEFAULT_HEIGHT);
        $meRequest['width'] = \xd_utilities\array_get($usageRequest, 'width', self::DEFAULT_WIDTH);
        $meRequest['scale'] = \xd_utilities\array_get($usageRequest, 'scale', self::DEFAULT_SCALE);
        $meRequest['aggregation_unit'] = \xd_utilities\array_get($usageRequest, 'aggregation_unit');
        $meRequest['start_date'] = \xd_utilities\array_get($usageRequest, 'start_date');
        $meRequest['end_date'] = \xd_utilities\array_get($usageRequest, 'end_date');
        $meRequest['start'] = \xd_utilities\array_get($usageRequest, 'offset');
        $meRequest['limit'] = \xd_utilities\array_get($usageRequest, 'limit');
        $meRequest['inline'] = \xd_utilities\array_get($usageRequest, 'inline', 'y') === 'y';
        $meRequest['hide_tooltip'] = \xd_utilities\array_get($usageRequest, 'hide_tooltip', false);

        $meRequestDataOptions['metric'] = $usageMetric;
        $meRequestDataOptions['realm'] = $usageRealm;
        $meRequestDataOptions['group_by'] = $usageGroupBy;
        $meRequestDataOptions['log_scale'] = \xd_utilities\array_get($usageRequest, 'log_scale') === 'y';
        $meRequestDataOptions['std_err'] = \xd_utilities\array_get($usageRequest, 'show_error_bars') === 'y';
        $meRequestDataOptions['std_err_labels'] = \xd_utilities\array_get($usageRequest, 'show_error_labels') === 'y';
        $meRequestDataOptions['value_labels'] = \xd_utilities\array_get($usageRequest, 'show_aggregate_labels') === 'y';
        $meRequestDataOptions['trend_line'] = \xd_utilities\array_get($usageRequest, 'show_trend_line') === 'y';

        // Convert any array parameters to JSON and URL encode them.
        // Also, store the original arrays as differently-named parameters.
        $unencodedMeRequestParams = array();
        foreach ($meRequest as $meRequestKey => $meRequestValue) {
            if (!is_array($meRequestValue)) {
                continue;
            }

            $unencodedMeRequestParams[$meRequestKey] = $meRequestValue;
        }
        foreach ($unencodedMeRequestParams as $meRequestKey => $meRequestValue) {
            $meRequest["${meRequestKey}_unencoded"] = $meRequestValue;
            $meRequest[$meRequestKey] = urlencode(json_encode($meRequestValue));
        }

        // Return the Metric Explorer request.
        return $meRequest;
    }

    /**
     * Generate a chart's filename from a set of chart parameters.
     *
     * @param  string  $title        The title of the chart.
     * @param  string  $start_date   The start date of the chart.
     * @param  string  $end_date     The end date of the chart.
     * @param  boolean $isTimeseries Whether or not the chart is a timeseries
     *                               chart.
     * @return string                The filename of the chart.
     */
    private function generateFilename($title, $start_date, $end_date, $isTimeseries) {
        return
            str_replace('%', 'Percent', $title)
            . '_'
            . $start_date
            . '_to_'
            . $end_date
            . '_'
            . ($isTimeseries ? 'timeseries' : 'aggregate')
        ;
    }
}
