<?php

namespace DataWarehouse;

use Configuration\XdmodConfiguration;
use Models\Services\Acls;
use Models\Services\Parameters;
use XDUser;

/**
 * Singleton class for helping guide the creation of a Query object.
 *
 * @author Amin Ghadersohi
 */
class QueryBuilder
{
    private static $_self = NULL;

    public static $_realms = null;

    public static $_datasetTypes = array(
        array(
            'id'    => 'timeseries',
            'label' => 'Timeseries'
        ) ,
        array(
            'id'    => 'aggregate',
            'label' => 'Aggregate'
        )
    );

    private $_params;

    public static function getInstance()
    {
        if (self::$_self == NULL) {
            self::$_self = new QueryBuilder();
        }

        return self::$_self;
    }

    private function __construct()
    {
    }

    public function getParameters()
    {
        return $this->_params;
    }

    public static function getQueryRealms()
    {
        self::loadQueryRealmConfig();
        return self::$_realms;
    }

    public static function getQueryRealmClassname($realm)
    {
        self::loadQueryRealmConfig();
        if (!isset(self::$_realms[$realm])) {
            throw new \Exception('"' . $realm . '" is an invalid query realm.');
        }

        return self::$_realms[$realm]['classname'];
    }

    public static function getAggregationUnits()
    {
        return \DataWarehouse\Query\TimeAggregationUnit::getRegsiteredAggregationUnits();
    }

    public static function getDatasetTypes()
    {
        return self::$_datasetTypes;
    }

    public function queryRealmIsValid($realmname)
    {
        self::loadQueryRealmConfig();
        return isset($this->_realms[$realmname]);
    }

    private static function loadQueryRealmConfig()
    {
        if( self::$_realms !== null ) {
            // Already loaded config
            return;
        }

        $config = XdmodConfiguration::assocArrayFactory('datawarehouse.json', CONFIG_DIR);
        $dwconfig = $config['realms'];

        foreach($dwconfig as $realmName => $data) {
            self::$_realms[$realmName] = array(
                "realm"     => $realmName,
                "classname" => "\\DataWarehouse\\Query\\" . $realmName . "\\Aggregate",
            );
        }
    }

    public function generateDatasets()
    {
        foreach ($this->params['query_realms'] as $query_realm_key => $query_realm_object) {
            $query_class_name
                = '\\DataWarehouse\\Query\\'
                . $query_realm_key
                . '\\Aggregate';
        }
    }

    public function buildQueriesFromDescripters(
        array &$query_descipters,
        &$request,
        \XDUser &$user
    ) {
        $queries = array();

        foreach ($query_descipters as $query_descipter) {
            $request['realm']        = $query_descipter->getRealmName();
            $request['group_by']     = $query_descipter->getGroupByName();
            $request['statistic']    = $query_descipter->getDefaultStatisticName();
            $request['query_group']  = $query_descipter->getQueryGroupname();
            $request['dataset_type'] = $query_descipter->getDefaultQueryType();

            $queries = array_merge(
                $queries,
                $this->buildQueriesFromRequest($request, $user)
            );
        }

        return $queries;
    }

    public static function getQueryGroupFromRequest(&$request)
    {
        $query_group = 'tg_usage';

        if (isset($request['query_group'])) {
            $query_group = $request['query_group'];
        }
        elseif (isset($request['querygroup'])) {
            $query_group = $request['querygroup'];
        }

        return $query_group;
    }

    public function getRealmFromRequest(&$request)
    {
        if (!isset($request['realm'])) {
            return 'Jobs';
        }

        return $request['realm'];
    }

    public function getGroupByFromRequest(&$request)
    {
        if (isset($request['group_by'])) {
            return $request['group_by'];
        }
        elseif (isset($request['dimension'])) {
            return $request['dimension'];
        }
        else {
            throw new \Exception('Parameter group_by/dimension is not set');
        }
    }

    public function getStatisticFromRequest(&$request)
    {
        return
            isset($request['statistic'])
            ? $request['statistic']
            : (
                isset($request['fact'])
                ? $request['fact']
                : NULL
            );
    }

    public function buildQueriesFromRequest(&$request, \XDUser &$user)
    {
        $queries = array();

        $realm     = $this->getRealmFromRequest($request);
        $group_by  = $this->getGroupByFromRequest($request);
        $statistic = $this->getStatisticFromRequest($request);

        $query_group      = $this->getQueryGroupFromRequest($request);
        $rp_usage_regex   = '/rp_(?P<rp_id>[0-9]+)_usage/';
        $rp_summary_regex = '/rp_(?P<rp_id>[0-9]+)_summary/';

        if (
               $query_group === 'my_usage'
            || $query_group === 'my_summary'
            || $query_group === 'tg_usage'
            || $query_group === 'tg_summary'
        ) {
            // Do nothing...
        }
        elseif (preg_match($rp_usage_regex, $query_group, $matches) > 0) {
            $request['provider'] = $matches['rp_id'];
            $query_group         = 'tg_usage';
        }
        elseif (preg_match($rp_summary_regex, $query_group, $matches) > 0) {
            $request['provider'] = $matches['rp_id'];
            $query_group         = 'tg_summary';
        }
        else {
            if (($suffix_index = strpos($query_group, '_summary')) !== false) {
                $suffix = '_summary';
            }
            elseif (
                ($suffix_index = strpos($query_group, '_usage')) !== false
            ) {
                $suffix = '_usage';
            }

            if (isset($suffix)) {
                $role_data = explode(
                    ':',
                    substr($query_group, 0, strpos($query_group, $suffix))
                );

                $activeRole = XDUser::_getFormalRoleName($role_data[0], true);

                $role_parameters = Parameters::getParameters($user, $activeRole);
                $request = array_merge($request, $role_parameters);
                $query_group = 'tg' . $suffix;
            }
        }

        if (!isset($request['start_date'])) {
            throw new \Exception(
                'Parameter start_date (yyyy-mm-dd) is not set'
            );
        }

        $start_date = $request['start_date'];

        if (!isset($request['end_date'])) {
            throw new \Exception('Parameter end_date (yyyy-mm-dd) is not set');
        }

        $end_date = $request['end_date'];

        $aggregation_unit = 'auto';
        if (isset($request['aggregation_unit'])) {
            $aggregation_unit = $request['aggregation_unit'];
        }

        $aggregation_unit = Query\TimeAggregationUnit::deriveAggregationUnitName(
            $aggregation_unit,
            $start_date,
            $end_date,
            Query\TimeAggregationUnit::getMinUnitForRealm($realm)
        );

        $dataset_type = 'aggregate';
        if (isset($request['dataset_type'])) {
            $dataset_type = $request['dataset_type'];
        }

        $single_stat = false;
        if (isset($request['single_stat'])) {
            $single_stat
                = $request['single_stat'] == 'y'
                || $request['single_stat'] == 'true'
                || $request['single_stat'] == true;
        }

        $query_descripter = Acls::getQueryDescripters(
            $user,
            $realm,
            $group_by,
            $statistic
        );

        if (is_array($query_descripter)) {
            throw new \Exception(
                'QueryBuilder params incorrect: '
                . json_encode(array(
                    'query_group' => $query_group,
                    'realm'       => $realm,
                    'group_by'    => $group_by,
                    'statistic'   => $statistic,
                ))
            );
        }

        // parse other (drill-down) paramters and form parameters array;
        $parameters = $query_descripter->pullQueryParameters($request);

        $parameterDescriptions
            = $query_descripter->pullQueryParameterDescriptions($request);

        $queries = $query_descripter->getAllQueries(
            $start_date,
            $end_date,
            $aggregation_unit,
            $parameters,
            $dataset_type,
            $parameterDescriptions,
            $single_stat
        );

        return $queries;
    }

    public function pullQueryParameterDescriptionsFromRequest(
        &$request,
        \XDUser &$user
    ) {
        $realm            = $this->getRealmFromRequest($request);
        $statistic        = $this->getStatisticFromRequest($request);
        $group_by         = $this->getGroupByFromRequest($request);
        $query_group      = $this->getQueryGroupFromRequest($request);
        $rp_usage_regex   = '/rp_(?P<rp_id>[0-9]+)_usage/';
        $rp_summary_regex = '/rp_(?P<rp_id>[0-9]+)_summary/';

        if (preg_match($rp_usage_regex, $query_group, $matches) > 0) {
            $request['provider'] = $matches['rp_id'];
            $query_group         = 'tg_usage';
        }
        elseif (preg_match($rp_summary_regex, $query_group, $matches) > 0) {
            $request['provider'] = $matches['rp_id'];
            $query_group         = 'tg_summary';
        }
        else {
            if (($suffix_index = strpos($query_group, '_summary')) !== false) {
                $suffix = '_summary';
            }
            elseif (
                ($suffix_index = strpos($query_group, '_usage')) !== false
            ) {
                $suffix = '_usage';
            }

            if (isset($suffix)) {
                $role_data = explode(
                    ':',
                    substr($query_group, 0, strpos($query_group, $suffix))
                );

                $activeRole = XDUser::_getFormalRoleName($role_data[0], true);

                $role_parameters = Parameters::getParameters($user, $activeRole);
                $request = array_merge($request, $role_parameters);
                $query_group = 'tg' . $suffix;
            }
        }

        $query_descripter = Acls::getQueryDescripters(
            $user,
            $realm,
            $group_by,
            $statistic
        );

        if (is_array($query_descripter)) {
            throw new \Exception(
                'QueryBuilder params incorrect: '
                . json_encode(array(
                    'query_group' => $query_group,
                    'realm'       => $realm,
                    'group_by'    => $group_by,
                    'statistic'   => $statistic,
                ))
            );
        }

        return $query_descripter->pullQueryParameterDescriptions($request);
    }
}

