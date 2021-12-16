<?php

namespace DataWarehouse\Access;

use Exception;
use Models\Services\Parameters;
use Models\Services\Acls;
use Models\Services\Realms;
use PDOException;
use stdClass;

use CCR\DB;
use DataWarehouse\RoleRestrictionsStringBuilder;
use DataWarehouse\Query\Exceptions\AccessDeniedException;
use DataWarehouse\Query\Exceptions\MissingFilterListTableException;
use DataWarehouse\Query\Exceptions\UnknownGroupByException;
use FilterListHelper;
use XDUser;

class MetricExplorer extends Common
{
    public function get_data($user)
    {
        if(isset($this->request['config'])) {
            $config = json_decode($this->request['config'], true);
            $this->request = array_merge($config, $this->request);
        }

        $format = \DataWarehouse\ExportBuilder::getFormat(
            $this->request,
            'png',
            array(
                'svg',
                'png',
                'pdf',
                'png_inline',
                'svg_inline',
                'xml',
                'csv',
                'json',
                'jsonstore',
                'hc_jsonstore',
                '_internal',
            )
        );

        $inline = $this->getInline();

        list($start_date, $end_date, $start_ts, $end_ts) = $this->checkDateParameters();

        if ($start_ts > $end_ts) {
            throw new Exception(
                'End date must be greater than or equal to start date'
            );
        }

        $showContextMenu = $this->getShowContextMenu();

        $aggregation_unit = $this->getAggregationUnit();
        $timeseries       = $this->getTimeseries();

        $limit = $this->getLimit();
        $offset = $this->getOffset();
        $showRemainder = $this->getShowRemainder();

        $title      = $this->getTitle();
        $show_title = $this->getShowTitle();

        $global_filters = $this->getGlobalFilters();

        $activeRoleParam = \xd_utilities\array_get($this->request, 'active_role');
        if (!empty($activeRoleParam)) {
            self::convertActiveRoleToGlobalFilters($user, $activeRoleParam, $global_filters);
        }

        $share_y_axis = $this->getShareYAxis();
        $hide_tooltip = $this->getHideTooltip();
        $show_filters = $this->getShowFilters();
        $showWarnings = $this->getShowWarnings();

        $y_axis = $this->getYAxis();
        $x_axis = $this->getXAxis();
        $legend = $this->getLegend();

        $dataset_classname
            = $timeseries
            ? '\DataWarehouse\Data\TimeseriesDataset'
            : '\DataWarehouse\Data\SimpleDataset';

        $highchart_classname
            = $timeseries
            ? '\DataWarehouse\Visualization\HighChartTimeseries2'
            : '\DataWarehouse\Visualization\HighChart2';

        $filename = $this->getFilename();
        $filenameSpecifiedInRequest = $filename !== null;
        if (!$filenameSpecifiedInRequest) {
            $filename
                = 'xdmod_'
                . ($title != '' ? $title : 'untitled')
                . '_' . $start_date . '_to_' . $end_date;
        }
        $filename = substr($filename, 0, 250);

        $all_data_series = $this->getDataSeries();

        $data_series = array();

        // Discard disabled datasets.
        foreach ($all_data_series as $data_description_index => $data_description) {
            if ($data_description->display_type == 'radar') {
                $data_description->display_type = 'line';
            }

            if (
                !isset($data_description->{'enabled'})
                || $data_description->{'enabled'}
            ) {
                $data_series[] = $data_description;
            }
        }

        // Check that the user is allowed to view the datasets they have
        // requested. If they are not allowed to view any of them, throw an
        // exception indicating access is denied.
        foreach ($data_series as $data_description) {
            $data_description->authorizedRoles = self::checkDataAccess(
                $user,
                $data_description->realm,
                $data_description->group_by,
                $data_description->metric
            );
        }

        $min_aggregation_unit = null;
        foreach ($data_series as $data_series_desc) {
            $min_aggregation_unit = \DataWarehouse\Query\TimeAggregationUnit::getMaxUnit(
                $min_aggregation_unit,
                \DataWarehouse\Query\TimeAggregationUnit::getMinUnitForRealm($data_series_desc->realm)
            );
        }

        if (
            $format === 'hc_jsonstore'
            || $format === 'png'
            || $format === 'pdf'
            || $format === 'svg'
            || $format === 'png_inline'
            || $format === 'svg_inline'
            || $format === '_internal'
        ) {
            $width   = $this->getWidth();
            $height  = $this->getHeight();
            $scale   = $this->getScale();
            $swap_xy = $this->getSwapXY();

            $legend_location = $this->getLegendLocation();

            $font_size = $this->getFontSize();

            $hc = new $highchart_classname(
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

            if ($show_title) {
                $hc->setTitle($title, $font_size);
            }

            // Called before and after configure.
            $hc->setLegend($legend_location, $font_size);

            $hc->configure(
                $data_series,
                $x_axis,
                $y_axis,
                $legend,
                $show_filters,
                $global_filters,
                $font_size,
                $limit,
                $offset,
                $showRemainder
            );

            $hc->setLegend($legend_location, $font_size);

            $returnData = $hc->exportJsonStore($limit, $offset);

            $requestDescripter = new \User\Elements\RequestDescripter($this->request);
            $chartIdentifier = $requestDescripter->__toString();
            $chartPool = new \XDChartPool($user);

            $includedInReport = $chartPool->chartExistsInQueue(
                $chartIdentifier,
                $title
            );

            $returnData['data'][0]['reportGeneratorMeta'] = array(
                'chart_args'         => $chartIdentifier,
                'title'              => $title,
                'params_title'       => $hc->getSubtitleText(),
                'start_date'         => $start_date,
                'end_date'           => $end_date,
                'included_in_report' => $includedInReport ? 'y' : 'n',
            );

            return $this->exportImage(
                $returnData,
                $width,
                $height,
                $scale,
                $format,
                $filename,
                array(
                    'author' => $user->getFormalName(),
                    'subject' => ($timeseries ? 'Timeseries' : 'Aggregate') . ' data for period ' . $start_date. ' -> ' . $end_date,
                    'title' => $title
                )
            );
        } // if $format === 'hc_jsonstore' || $format === 'png' || $format === 'svg'
          //  || $format === 'png_inline' || $format === 'svg_inline'
        elseif ($format === 'jsonstore' || $format === 'json' || $format === 'csv' || $format === 'xml') {
            $datasets = array();
            $datasetsRestricted = array();
            $datasetsRestrictedMessages = array();

            $aggregation_unit = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName(
                $aggregation_unit,
                $start_date,
                $end_date,
                $min_aggregation_unit
            );

            foreach ($data_series as $data_description_index => $data_description) {
                $query_classname = sprintf(
                    '\\DataWarehouse\\Query\\%sQuery',
                    ( $timeseries ? 'Timeseries' : 'Aggregate' )
                );

                $query = new $query_classname(
                    $data_description->realm,
                    $aggregation_unit,
                    $start_date,
                    $end_date
                );

                $query->addGroupBy($data_description->group_by);
                $query->addStat($data_description->metric);

                if ($data_description->std_err == 1) {
                    $semStatId = \Realm\Realm::getStandardErrorStatisticFromStatistic(
                        $data_description->metric
                    );
                    if ($query->getRealm()->statisticExists($semStatId)) {
                        $query->addStat($semStatId);
                    }
                    else {
                        $data_description->std_err = 0;
                    }
                }

                $groupedRoleParameters = array();
                foreach ($global_filters->data as $global_filter) {
                    if ($global_filter->checked == 1) {
                        if (
                            !isset(
                                $groupedRoleParameters[$global_filter->dimension_id]
                            )
                        ) {
                            $groupedRoleParameters[$global_filter->dimension_id]
                                = array();
                        }

                        $groupedRoleParameters[$global_filter->dimension_id][]
                            = $global_filter->value_id;
                    }
                } // foreach ($global_filters->data as $global_filter) {

                $query->setRoleParameters($groupedRoleParameters);

                $query->setFilters($data_description->filters);

                $roleRestrictionsParameters = $query->setMultipleRoleParameters($data_description->authorizedRoles, $user);
                $restrictedByRoles = $query->isLimitedByRoleRestrictions();

                $query->addOrderByAndSetSortInfo($data_description);

                $datasets[] = new $dataset_classname($query);
                $datasetsRestricted[] = $restrictedByRoles;
                if ($restrictedByRoles) {
                    $roleRestrictionsStringBuilder = new RoleRestrictionsStringBuilder();
                    $roleRestrictionsStringBuilder->registerRoleRestrictions($roleRestrictionsParameters);
                    $roleRestrictionsStrings = $roleRestrictionsStringBuilder->getRoleRestrictionsStrings();
                    $datasetsRestrictedMessages[] = $roleRestrictionsStrings[0];
                } else {
                    $datasetsRestrictedMessages[] = '';
                }
            } // foreach ($data_series as $data_description_index => $data_description)

            if ($format === 'csv' || $format === 'xml' || $format === 'json') {
                $exportedDatas = array();

                // This is to maintain consistency with how the title inside of
                // Usage tab exports was written when it had its own back-end.
                $exportTitle = $filenameSpecifiedInRequest ? $title : $filename;

                foreach ($datasets as $datasetIndex => $dataset) {
                    $exportedData = $dataset->export($exportTitle);
                    $exportedData['restrictedByRoles'] = $datasetsRestricted[$datasetIndex];
                    $exportedData['roleRestrictionsMessage'] = $datasetsRestrictedMessages[$datasetIndex];
                    $exportedDatas[] = $exportedData;
                }

                return \DataWarehouse\ExportBuilder::export($exportedDatas, $format, $inline, $filename);

            } // if ($format === 'csv' || $format === 'xml')

            elseif($format === 'jsonstore') {
                $exportedDatas = array();

                foreach ($datasets as $datasetIndex => $dataset) {
                    $exportedData = $dataset->exportJsonStore();
                    $exportedData['restrictedByRoles'] = $datasetsRestricted[$datasetIndex];
                    $exportedData['roleRestrictionsMessage'] = $datasetsRestrictedMessages[$datasetIndex];
                    $exportedDatas[] = json_encode($exportedData);
                }


                $result = array(
                    "headers" => \DataWarehouse\ExportBuilder::getHeader($format),
                    "results" => $exportedDatas,
                );

                return $result;
            } // elseif($format === 'jsonstore')

        } //  elseif ($format === 'jsonstore' || $format === 'csv' || $format === 'xml')

        throw new Exception("Internal Error");
    } // function get_data($user)

    private function getAggregationUnit()
    {
        return
            isset($this->request['aggregation_unit'])
            ? $this->request['aggregation_unit']
            : 'auto';
    } // function getAggregationUnit()

    private function getDataSeries()
    {
        if (!isset($this->request['data_series']) || empty($this->request['data_series'])) {
            return json_decode(0);
        }

        if (
            is_array($this->request['data_series'])
            && is_array($this->request['data_series']['data'])
        ) {
            $v = $this->request['data_series']['data'];

            $ret = array();
            foreach ($v as $x) {
                $y = (object)$x;

                for ($i = 0, $b = count($y->filters['data']); $i < $b; $i++) {
                    $y->filters['data'][$i] = (object)$y->filters['data'][$i];
                }

                $y->filters = (object)$y->filters;

                // Set values of new attribs for backward compatibility.
                if (!isset($y->line_type) || empty($y->line_type)) {
                    $y->line_type = 'Solid';
                }

                if (
                    !isset($y->line_width)
                    || empty($y->line_width)
                    || !is_numeric($y->line_width)
                ) {
                    $y->line_width = 2;
                }

                if (!isset($y->color) || empty($y->color)) {
                    $y->color = 'auto';
                }

                if (!isset($y->shadow) || empty($y->shadow)) {
                    $y->shadow = false;
                }

                $ret[] = $y;
            }

            return $ret;
        }
        $ret =  urldecode($this->request['data_series']);

        $jret = json_decode($ret);

        foreach ($jret as &$y) {

            // Set values of new attribs for backward compatibility.
            if (!isset($y->line_type) || empty($y->line_type)) {
                $y->line_type = 'Solid';
            }

            if (
                !isset($y->line_width)
                || empty($y->line_width)
                || !is_numeric($y->line_width)
            ) {
                $y->line_width = 2;
            }

            if (!isset($y->color) || empty($y->color)) {
                $y->color = 'auto';
            }

            if (!isset($y->shadow) || empty($y->shadow)) {
                $y->shadow = false;
            }
        }

        return $jret;
    } // function getDataSeries()

    private function getGlobalFilters()
    {
        if (
            !isset($this->request['global_filters'])
            || empty($this->request['global_filters'])
        ) {
            return (object)array('data' => array(), 'total' => 0);
        }

        if (is_array($this->request['global_filters'])) {
            $v = $this->request['global_filters']['data'];

            $ret = (object)array('data' => array(), 'total' => 0);

            foreach ($v as $x) {
                $ret->data[] = (object)$x;
                $ret->total++;
            }

            return $ret;
        }

        $ret =  urldecode($this->request['global_filters']);

        return json_decode($ret);
    } // function getGlobalFilters()

    private function getShowContextMenu()
    {
        return
            isset($this->request['showContextMenu'])
            ? $this->request['showContextMenu'] == 'true'
            || $this->request['showContextMenu'] === 'y'
            : false;
    } // function getShowContextMenu()

    private function getXAxis()
    {
        if (!isset($this->request['x_axis']) || empty($this->request['x_axis'])) {
            return array();
        }

        if (is_array($this->request['x_axis'])) {
            $ret = new stdClass;

            foreach ($this->request['x_axis'] as $k => $x) {
                if (is_array($x)) {
                    $ret->{$k} = (object)$x;
                }
                else {
                    $ret->{$k} = $x;
                }
            }

            return  $ret;
        }

        return json_decode(urldecode($this->request['x_axis']));
    } // function getXAxis()

    private function getYAxis()
    {
        if (!isset($this->request['y_axis']) || empty($this->request['y_axis'])) {
            return array();
        }

        if (is_array($this->request['y_axis'])) {
            $ret = new stdClass;

            foreach ($this->request['y_axis'] as $k => $x) {
                if (is_array($x)) {
                    $ret->{$k} = (object)$x;
                }
                else {
                    $ret->{$k} = $x;
                }
            }

            return $ret;
        }

        return json_decode(urldecode($this->request['y_axis']));
    } // function getYAxis()

    private function getLegend()
    {
        if (!isset($this->request['legend']) || empty($this->request['legend'])) {
            return array();
        }

        if (is_array($this->request['legend'])) {
            $ret = new stdClass;

            foreach($this->request['legend'] as $k => $x) {
                if (is_array($x)) {
                    $ret->{$k} = (object)$x;
                }
                else {
                    $ret->{$k} = $x;
                }
            }

            return  $ret;
        }

        return json_decode(urldecode($this->request['legend']));
    } // function getLegend()

    private function getShowFilters()
    {
        return
            isset($this->request['show_filters'])
            ? $this->request['show_filters'] == 'y'
            || $this->request['show_filters'] == 'true'
            : true;
    } // function getShowFilters()

    private function getShowWarnings()
    {
        $showWarningsParam = \xd_utilities\array_get($this->request, 'show_warnings', 'true');
        return $showWarningsParam === 'true' || $showWarningsParam === 'y';
    }

    /**
     * Check that a user has access to the specified metric data.
     *
     * @param  XDUser $user            The user to check the authorization of.
     * @param  string $realm_name      (Optional) The realm name.
     * @param  string $group_by_name   (Optional) The group by name.
     * @param  string $statistic_name  (Optional) The statistic name.
     *
     * @return array The roles authorized to view the data.
     *               These should be used to restrict the query to the
     *               subsets of data the user is allowed to view.
     *
     * @throws AccessDeniedException The user does not have access to the data.
     */
    public static function checkDataAccess(
        XDUser $user,
        $realm_name = null,
        $group_by_name = null,
        $statistic_name = null,
        $includePub = true
    ) {
        $userRoles = $user->getAllRoles($includePub);

        $authorizedRoles = array();
        foreach ($userRoles as $userRole) {
            $accessPermitted = Acls::hasDataAccess(
                $user,
                $realm_name,
                $group_by_name,
                $statistic_name,
                $userRole
            );
            if ($accessPermitted) {
                $authorizedRoles[] = $userRole;
            }
        }

        if (empty($authorizedRoles)) {
            throw new AccessDeniedException();
        }

        return $authorizedRoles;
    }

    /**
     * Convert a given role into equivalent global filters.
     *
     * @param  XDUser $user          The user making the request.
     * @param  string $activeRoleId  The identifier for the role to convert.
     * @param  object $globalFilters A global filters object sent in a Metric
     *                               Explorer chart request. Any new global
     *                               filters will be stored in here.
     */
    public static function convertActiveRoleToGlobalFilters(XDUser $user, $activeRoleId, $globalFilters) {
        // Load the active role's filter parameters.
        // (Regex for artificial service provider roles from now-deleted code.)
        if (preg_match('/rp_(?P<rp_id>[0-9]+)/', $activeRoleId, $resourceProviderRoleIdMatches)) {
            $activeRoleParameters = array(
                'provider' => $resourceProviderRoleIdMatches['rp_id'],
            );
        } else {
            $activeRoleComponents = explode(':', $activeRoleId);
            $activeRoleId = $activeRoleComponents[0];
            $activeRole = Acls::getAclByName($activeRoleId);
            if ($activeRole === null) {
                $activeRoleId = ROLE_ID_PUBLIC;
            }
            $activeRoleParameters = Parameters::getParameters($user, $activeRoleId);
        }

        // For each set of filter parameters the role has, create an
        // equivalent global filter.
        foreach ($activeRoleParameters as $parameterDimensionId => $parameterValueId) {
            // Generate the filter ID. If it matches an existing global filter,
            // skip this filter.
            $roleFilterId = "$parameterDimensionId=$parameterValueId";
            foreach ($globalFilters->data as $globalFilter) {
                if ($globalFilter->id === $roleFilterId) {
                    continue 2;
                }
            }

            // Create and store the filter object.
            $globalFilters->data[] = (object) array(
                'id' => $roleFilterId,
                'value_id' => $parameterValueId,
                'value_name' => MetricExplorer::getDimensionValueName($user, $parameterDimensionId, $parameterValueId),
                'dimension_id' => $parameterDimensionId,
                'realms' => MetricExplorer::getDimensionRealms($user, $parameterDimensionId),
                'checked' => true
            );
            $globalFilters->total++;
        }
    }

    /**
     * Get values for a dimension from realms' aggregate data.
     *
     * @param  XDUser     $user           The user requesting this data.
     * @param  string     $dimension_id The dimension values are being
     *                                    requested for.
     * @param  array|null $realms         (Optional) The realms to check for
     *                                    values. If not given or empty, all
     *                                    applicable realms will be checked.
     * @param  int        $offset         (Optional) The offset into the set
     *                                    of values to start returning.
     *                                    (Defaults to 0.)
     * @param  int|null   $limit          (Optional) The limit on the number of
     *                                    values to return. See documentation
     *                                    for the length parameter for
     *                                    array_slice. (Defaults to null.)
     * @param  string|null $searchText    (Optional) A search string to limit
     *                                    the results returned. (Defaults to
     *                                    null.)
     * @param  array|null $selectedFilterIds (Optional) A set of value IDs to
     *                                       check against the result set,
     *                                       which sets the "checked" property
     *                                       in each result. If null, this
     *                                       property will not be set.
     *                                       (Defaults to null.)
     * @param  bool|false $showAllDimensionValues (Optional) A boolean to determine
     *                                       if all values in the dimension set should
     *                                       be shown. If true, all values are only shown
     *                                       if group_by allows.
     * @return array                      A result representation containing:
     *                                        * data: An array of values.
     *                                        * totalCount: The total number
     *                                          of values found using the
     *                                          given criteria (before applying
     *                                          offset and limit).
     *
     * @throws AccessDeniedException The user explicitly requested values
     *                               from a combination of a realm and
     *                               dimension they don't have access to.
     * @throws UnknownGroupByException The user explicitly requested values
     *                                 from a combination of a realm and
     *                                 dimension that doesn't exist. This may
     *                                 also be thrown if the user did not
     *                                 specify any realms and the dimension
     *                                 was either nonexistent or restricted for
     *                                 all realms.
     */
    public static function getDimensionValues(
        XDUser $user,
        $dimension_id,
        array $realms = null,
        $offset = 0,
        $limit = null,
        $searchText = null,
        array $selectedFilterIds = null,
        $includePub = true,
        $showAllDimensionValues = false
    ) {
        // Check if the realms were specified, and if not, use all realms.
        $realmsSpecified = !empty($realms);
        if (!$realmsSpecified) {
            $realms = Realms::getRealmIdsForUser($user);
        }

        // Determine which aggregation unit to use for dimension values queries.
        $queryAggregationUnit = FilterListHelper::getQueryAggregationUnit();

        // Get a dimension values query for each valid realm.
        $dimensionValuesQueries = array();
        foreach ($realms as $realm) {

            // Attempt to get the group by object for this realm to check that
            // the dimension exists for this realm.
            $realmObj = \Realm\Realm::factory($realm);

            if ( ! $realmObj->groupByExists($dimension_id) ) {
                if ( $realmsSpecified ) {
                    // If the group by does not exist and realms were explicitly
                    // specified, throw an exception. Otherwise, just continue to
                    // the next realm.
                    throw new UnknownGroupByException(
                        sprintf("Dimension '%s' does not exist for realm '%s'.", $dimension_id, $realm)
                    );
                } else {
                    continue;
                }
            }

            $group_by = $realmObj->getGroupByObject($dimension_id);

            // Get the user's roles that are authorized to view this data.
            try {
                $realmAuthorizedRoles = MetricExplorer::checkDataAccess(
                    $user,
                    $realm,
                    $dimension_id,
                    null,
                    $includePub
                );
            } catch (AccessDeniedException $e) {
                // Only throw an exception that the user is not authorized if
                // they requested this realm's data explicitly. Otherwise, just
                // skip this realm.
                if ($realmsSpecified) {
                    throw $e;
                }
                continue;
            }

            // Generate the dimension values query for this realm.
            $query = new \DataWarehouse\Query\AggregateQuery(
                $realm,
                $queryAggregationUnit,
                null,
                null,
                $dimension_id
            );

            if($showAllDimensionValues && $group_by->showAllDimensionValues()){
                $dimensionValuesQueries[] = $query->getDimensionValuesQuery();
            }else{
                $query->setMultipleRoleParameters($realmAuthorizedRoles, $user);
                $dimensionValuesQueries[] = $query->getDimensionValuesQuery();
            }
        }

        // Throw an exception if no queries could be generated.
        $numDimensionValuesQueries = count($dimensionValuesQueries);
        if ($numDimensionValuesQueries === 0) {
            throw new UnknownGroupByException("Dimension \"$dimension_id\" either does not exist or you are not authorized to view its values in any realm.");
        }

        // Execute the queries as a single query. If only one query was
        // generated, execute it as is. Otherwise, union the queries together
        // and sort the result set by the specified ordering value.
        //
        // Requesting the entire set all at once and not specifying limiting
        // criteria allows us to make optimal use of the database's cache.
        //
        // If each realm's data is requested individually, the server needs to
        // remove the duplicates between realms on each request. This solution
        // has the database performing this task and caching the result.
        //
        // If limiting criteria are added to the query, only the final results
        // are cached, meaning that the base queries need to be rerun each time
        // the criteria are changed. In this case, the server is better off
        // filtering the results.
        $db = DB::factory('datawarehouse');
        try {
            if ($numDimensionValuesQueries === 1) {
                $dimensionValues = $db->query($dimensionValuesQueries[0]);
                foreach ($dimensionValues as &$dimensionValue) {
                    unset($dimensionValue['_dimensionOrderValue']);
                }
            } else {
                $combinedDimensionValuesSortOrder = 'ASC';
                foreach ($dimensionValuesQueries as $dimensionValuesQuery) {
                    if (preg_match(
                        '/ORDER\s+BY\s+[^,\s]+\s+DESC/i',
                        $dimensionValuesQuery
                    )) {
                        $combinedDimensionValuesSortOrder = 'DESC';
                        break;
                    }
                }

                $dimensionValuesUnion = '(' . implode(') UNION (', $dimensionValuesQueries) . ')';
                $combinedDimensionValuesQuery = "
                    SELECT
                        id,
                        name,
                        short_name
                    FROM
                        ($dimensionValuesUnion) AS dimensionValuesUnion
                    GROUP BY id
                    ORDER BY _dimensionOrderValue $combinedDimensionValuesSortOrder
                ";
                $dimensionValues = $db->query($combinedDimensionValuesQuery);
            }
        } catch (PDOException $e) {
            // If the filter table was missing from the schema,
            // throw a more user-friendly exception.
            if (
                $e->getCode() === '42S02'
                && strpos($e->getMessage(), 'modw_filters') !== false
            ) {
                throw new MissingFilterListTableException();
            }

            // If the schema does not exist or is not accessible,
            // also throw that exception.
            if ($e->getCode() === '42000') {
                $missingSchema = false;
                try {
                    $schemaCheckResults = $db->query("SHOW SCHEMAS LIKE 'modw_filters'");
                    $missingSchema = empty($schemaCheckResults);
                } catch (Exception $schemaCheckException) {
                }

                if ($missingSchema) {
                    throw new MissingFilterListTableException();
                }
            }

            throw $e;
        }

        // If a search string was given, filter the result set to values with
        // labels containing each word in the string.
        if ($searchText !== null) {
            $searchComponents = preg_split('/\s+/', $searchText, null, PREG_SPLIT_NO_EMPTY);
            foreach ($searchComponents as $searchComponent) {
                $dimensionValues = array_filter(
                    $dimensionValues,
                    function($dimensionValue) use ($searchComponent) {
                        return stripos($dimensionValue['short_name'], $searchComponent) !== false
                            || stripos($dimensionValue['name'], $searchComponent) !== false;
                    }
                );
            }
        }

        // Remove rows with duplicate names from the result set, favoring later
        // entries in the result set.
        $uniqueNameValues = array();
        foreach ($dimensionValues as &$dimensionValue) {
            $uniqueNameValues[$dimensionValue['name']] = &$dimensionValue;
        }
        $dimensionValues = array_values($uniqueNameValues);

        // Before applying any length-based filtering, count the number of
        // values found.
        $totalDimensionValues = count($dimensionValues);

        // If a non-null limit or non-zero offset was given, reduce the
        // result set to the specified slice.
        $limitGiven = $limit !== null;
        $offsetGiven = $offset !== 0;
        if ($limitGiven || $offsetGiven) {
            $dimensionValues = array_slice($dimensionValues, $offset, $limit);
        }

        // If a set of selected IDs is given, check the result set against
        // this set to determine what matches.
        if ($selectedFilterIds !== null) {
            foreach ($dimensionValues as &$dimensionValue) {
                $dimensionValue['checked'] = in_array($dimension_id.'='.$dimensionValue['id'], $selectedFilterIds);
            }
        }

        // Return the results.
        return array(
            'totalCount' => $totalDimensionValues,
            'data' => $dimensionValues,
        );
    }

    /**
     * Look up the name of a dimension.
     *
     * @param  XDUser $user         The user looking up the name.
     * @param  string $dimension_id The ID of the dimension being looked up.
     * @return mixed                The name for the dimension or null if not
     *                              found.
     */
    public static function getDimensionName(
        XDUser $user,
        $dimension_id
    ) {
        $realms = Realms::getRealmIdsForUser($user);

        foreach ($realms as $realm) {
            try {
                $groupBy = self::getGroupBy($user, $realm, $dimension_id);
            } catch (UnknownGroupByException $e) {
                continue;
            } catch (AccessDeniedException $e) {
                continue;
            }

            return $groupBy->getName();
        }

        return null;
    }

    /**
     * Get the realms a dimension applies to.
     *
     * The list of realms returned will only include realms which the given
     * user has access to when using the given dimension.
     *
     * @param  XDUser $user         The user requesting the realms.
     * @param  string $dimension_id The ID of the dimension being looked up.
     * @return array                The realms the dimension applies to.
     */
    public static function getDimensionRealms(
        XDUser $user,
        $dimension_id
    ) {
        $realms = Realms::getRealmIdsForUser($user);

        $dimensionRealms = array();
        foreach ($realms as $realm) {
            try {
                self::getGroupBy($user, $realm, $dimension_id);
            } catch (UnknownGroupByException $e) {
                continue;
            } catch (AccessDeniedException $e) {
                continue;
            }

            $dimensionRealms[] = $realm;
        }

        return $dimensionRealms;
    }

    /**
     * Look up a name from a dimension value's id.
     *
     * NOTE: This function should not be used to look up user-provided
     * dimension values. It does not check if the user is allowed to view the
     * specific dimension value requested. It only checks if the user has any
     * access to the dimension as a whole.
     *
     * @param  XDUser  $user         The user looking up the name.
     * @param  string  $dimension_id The dimension being looked up.
     * @param  mixed   $value_id     The ID of the dimension value being
     *                               looked up.
     * @param  boolean $getLongName  (Optional) Retrieve the long name for the
     *                               dimension value instead of the short name.
     *                               (Defaults to false.)
     * @return mixed                 The name associated with the dimension
     *                               value or null if not found.
     */
    public static function getDimensionValueName(
        XDUser $user,
        $dimension_id,
        $value_id,
        $getLongName = false
    ) {
        $realms = Realms::getRealmIdsForUser($user);

        $dimensionValueName = null;
        foreach ($realms as $realm) {
            try {
                $groupBy = self::getGroupBy($user, $realm, $dimension_id);
            } catch (UnknownGroupByException $e) {
                continue;
            } catch (AccessDeniedException $e) {
                continue;
            }

            // Attempt to look up the value.
            $possibleValues = $groupBy->getAttributeValues(array(
                'id' => $value_id,
            ));
            foreach ($possibleValues as $possibleValue) {
                $dimensionValueName = $possibleValue[$getLongName ? 'long_name' : 'short_name'];
                break 2;
            }
        }

        return $dimensionValueName;
    }

    /**
     * Get a GroupBy object for a given realm and dimension.
     *
     * This will check that the given user has access to the realm and
     * dimension.
     *
     * @param  XDUser $user         The user requesting the GroupBy object.
     * @param  string $realm        The realm the GroupBy is in.
     * @param  string $dimension_id The dimension the GroupBy represents.
     * @return GroupBy              The GroupBy object.
     *
     * @throws AccessDeniedException The user does not have access to this
     *                               realm and dimension.
     * @throws UnknownGroupByException The GroupBy object could not be found
     *                                 for this realm and dimension.
     */
    private static function getGroupBy(
        XDUser $user,
        $realm,
        $dimension_id
    ) {
        // Get the group by object for this dimension in this realm.
        // This will throw an exception if the object cannot be found.
        $realmObj = \Realm\Realm::factory($realm);

        $groupBy = $realmObj->getGroupByObject($dimension_id);

        // Check that the user is allowed to view this dimension in this
        // realm. If not, an exception will be thrown.
        self::checkDataAccess(
            $user,
            $realm,
            $dimension_id
        );

        // Return the group by object.
        return $groupBy;
    }
} // class MetricExplorer extends Common
