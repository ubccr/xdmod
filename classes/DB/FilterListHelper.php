<?php

use \Realm\GroupBy;
use DataWarehouse\Query\Query;

/**
 * Contains shared functionality useful for interacting with filter lists.
 */
class FilterListHelper
{
    /**
     * The name of the schema where the filter lists are stored.
     *
     * @var string
     */
    private static $targetSchema;

    /**
     * Get the full name of a filter list table for the given dimensions.
     *
     * @param  Query   $realmQuery A Query for the realm the dimensions are in.
     * @param  GroupBy $groupBy1   A dimension to get a table name for.
     * @param  GroupBy $groupBy2   (Optional) The dimension the first dimension
     *                             is being paired with. If not given or equal
     *                             to the first dimension, a main list will be
     *                             given instead of a pair list.
     * @return string              The full name of the dimensions' table.
     */
    public static function getFullTableName(Query $realmQuery, GroupBy $groupBy1, GroupBy $groupBy2 = null) {
        $schemaName = static::getSchemaName();
        $shortTableName = static::getTableName($realmQuery, $groupBy1, $groupBy2);
        return "{$schemaName}.{$shortTableName}";
    }

    /**
     * Get the (short) name of a filter list table for the given dimensions.
     *
     * @param  Query   $realmQuery A Query for the realm the dimensions are in.
     * @param  GroupBy $groupBy1   A dimension to get a table name for.
     * @param  GroupBy $groupBy2   (Optional) The dimension the first dimension
     *                             is being paired with. If not given or equal
     *                             to the first dimension, a main list will be
     *                             given instead of a pair list.
     * @return string              The short name of the dimensions' table.
     */
    public static function getTableName(Query $realmQuery, GroupBy $groupBy1, GroupBy $groupBy2 = null) {
        $groupBy1Id = $groupBy1->getId();

        $secondDimensionGiven = $groupBy2 !== null;
        if ($secondDimensionGiven) {
            $groupBy2Id = $groupBy2->getId();

            $groupByComparison = strcasecmp($groupBy1Id, $groupBy2Id);
        }

        $realmName = $realmQuery->getRealmName();
        $tableName = "{$realmName}_";
        if (!$secondDimensionGiven || $groupByComparison === 0) {
            $tableName .= $groupBy1Id;
        } else {
            if ($groupByComparison < 0) {
                $firstId = $groupBy1Id;
                $secondId = $groupBy2Id;
            } else {
                $firstId = $groupBy2Id;
                $secondId = $groupBy1Id;
            }
            $tableName .= "{$firstId}___{$secondId}";
        }

        return $tableName;
    }

    /**
     * Get the name of the schema where the filter lists are stored.
     *
     * @return string The schema name.
     */
    public static function getSchemaName() {
        if (empty(static::$targetSchema)) {
            static::$targetSchema = 'modw_filters';
        }
        return static::$targetSchema;
    }

    /**
     * Get the aggregation unit to use when querying aggregate tables.
     *
     * The year tables are the fastest to search against when obtaining
     * dimension values, as the search is only interested in whether
     * there exists data that the user has access to for a given
     * dimension value.
     *
     * @return string The aggregation unit to use.
     */
    public static function getQueryAggregationUnit() {
        return 'year';
    }
}
