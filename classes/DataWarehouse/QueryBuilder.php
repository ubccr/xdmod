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

    public static function getAggregationUnits()
    {
        return \DataWarehouse\Query\TimeAggregationUnit::getRegsiteredAggregationUnits();
    }

    public static function getDatasetTypes()
    {
        return self::$_datasetTypes;
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

