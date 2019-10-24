<?php

use CCR\DB;
use CCR\DB\MySQLHelper;
use DB\Exceptions\TableNotFoundException;
use DataWarehouse\Query\GroupBy;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\TimeAggregationUnit;

/**
 * Builds lists of filters for every realm's dimensions.
 */
class FilterListBuilder extends Loggable
{
    /**
     * The set of list tables already built by this builder.
     *
     * This array stores the names of list tables as keys since it is only used
     * to test for membership.
     *
     * @var array
     */
    private $builtListTables = array();

    /**
     * The set of names of dimensions used by roles.
     *
     * This array stores the names of the dimensions as keys since it is only
     * used to test for membership.
     *
     * This is not created until it needs to be used.
     *
     * @var array|null
     */
    private static $rolesDimensionNames = null;

    /**
     * Construct filter list builder.
     */
    public function __construct()
    {
        if ($this->_logger === null) {
            $this->_logger = Log::singleton('null');
        }
    }

    /**
     * Build filter lists for all realms' dimensions.
     */
    public function buildAllLists()
    {
        $config = \Configuration\XdmodConfiguration::assocArrayFactory(
            'datawarehouse.json',
            CONFIG_DIR,
            $this->_logger
        );

        // Get the realms to be processed.
        $realmNames = array_keys($config['realms']);

        // Generate lists for each realm's dimensions.
        foreach ($realmNames as $realmName) {
            $this->buildRealmLists($realmName);
        }
    }

    /**
     * Build filter lists for the given realm's dimensions.
     *
     * @param string $realmName The name of a realm to build lists for.
     */
    public function buildRealmLists($realmName)
    {
        // Get a query for the given realm.
        $realmClassName = "\\DataWarehouse\\Query\\$realmName\\Aggregate";
        $realmQuery = new $realmClassName(FilterListHelper::getQueryAggregationUnit(), null, null, 'none');

        // Get the dimensions in the given realm.
        $groupBys = $realmQuery->getRegisteredGroupBys();

        // Generate the lists for each dimension and each pairing of dimensions.
        foreach ($groupBys as $groupByName => $groupByClassName) {
            $this->buildDimensionLists($realmQuery, $realmQuery->getGroupBy($groupByName));
        }
    }

    /**
     * Build filter lists for the given dimension.
     *
     * @param Query   $realmQuery A query for the realm the dimension is in.
     * @param GroupBy $groupBy    The dimension's GroupBy to build lists for.
     */
    public function buildDimensionLists(Query $realmQuery, GroupBy $groupBy)
    {
        // Check that the given dimension has associated filter lists.
        // If it does not, stop.
        if (!$this->checkDimensionForLists($groupBy)) {
            return;
        }

        // Generate the main list table. If the list table does not already
        // exist, create it.
        $dimensionName = $groupBy->getName();
        $mainTableName = FilterListHelper::getTableName($realmQuery, $groupBy);

        $db = DB::factory('datawarehouse');
        $targetSchema = FilterListHelper::getSchemaName();
        $mainTableExistsResults = $db->query("SHOW TABLES FROM {$targetSchema} LIKE '$mainTableName'");
        if (empty($mainTableExistsResults)) {
            try {
                $dimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $groupBy);
            } catch (TableNotFoundException $e) {
                $this->_logger->notice("Not creating $targetSchema.$mainTableName list table; {$e->getTable()} table not found");
                return;
            }

            $dimensionColumnType = $dimensionProperties['type'];

            $db->execute(
                "CREATE TABLE `{$targetSchema}`.`{$mainTableName}` (
                    `{$dimensionName}` {$dimensionColumnType} NOT NULL,
                    PRIMARY KEY (`{$dimensionName}`)
                );"
            );
        }

        try {
            $db->beginTransaction();

            $dimensionQuery = $this->createDimensionQuery($realmQuery, $groupBy);

            $selectTables = $dimensionQuery->getSelectTables();
            $selectFields = $dimensionQuery->getSelectFields();
            $wheres = $dimensionQuery->getWhereConditions();

            $idField = $selectFields['id'];

            $selectTablesStr = implode(', ', $selectTables);
            $wheresStr = implode(' AND ', $wheres);

            $db->execute("TRUNCATE TABLE `{$targetSchema}`.`{$mainTableName}`");
            $db->execute(
                "INSERT INTO
                    `{$targetSchema}`.`{$mainTableName}`
                SELECT DISTINCT
                    $idField
                FROM $selectTablesStr
                WHERE $wheresStr"
            );

            $db->commit();

            $this->builtListTables[$mainTableName] = true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        // Generate list tables pairing this dimension with every other
        // dimension in the realm that's associated with roles.
        $realmGroupBys = $realmQuery->getRegisteredGroupBys();
        foreach ($realmGroupBys as $realmDimensionName => $realmGroupByClassName) {
            // If this dimension is the given dimension, skip it.
            $dimensionNameComparison = strcasecmp($dimensionName, $realmDimensionName);
            if ($dimensionNameComparison === 0) {
                continue;
            }

            // If this dimension does not have lists associated with it
            // or is not associated with any roles, skip it.
            $realmGroupBy = $realmQuery->getGroupBy($realmDimensionName);
            if (!$this->checkDimensionForLists($realmGroupBy) || !$this->checkDimensionForRoles($realmGroupBy)) {
                continue;
            }

            // Use alphabetical ordering to construct the list table name.
            // If the given dimension is ordered after the current dimension
            // and all realm lists are being built, this can be skipped, since
            // this pairing will have been taken care of by the other dimension.
            if ($dimensionNameComparison < 0) {
                $firstGroupBy = $groupBy;
                $firstDimensionName = $dimensionName;
                $firstDimensionQuery = $dimensionQuery;
                $secondGroupBy = $realmGroupBy;
                $secondDimensionName = $realmDimensionName;
                $secondDimensionQuery = $this->createDimensionQuery($realmQuery, $realmGroupBy);
            } else {
                $firstGroupBy = $realmGroupBy;
                $firstDimensionName = $realmDimensionName;
                $firstDimensionQuery = $this->createDimensionQuery($realmQuery, $realmGroupBy);
                $secondGroupBy = $groupBy;
                $secondDimensionName = $dimensionName;
                $secondDimensionQuery = $dimensionQuery;
            }
            $pairTableName = FilterListHelper::getTableName($realmQuery, $firstGroupBy, $secondGroupBy);
            if (array_key_exists($pairTableName, $this->builtListTables)) {
                continue;
            }

            // Generate the pair table. If the pair table does not exist,
            // create it.
            $pairTableExistsResults = $db->query("SHOW TABLES FROM {$targetSchema} LIKE '$pairTableName'");
            if (empty($pairTableExistsResults)) {
                try {
                    $firstDimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $firstGroupBy);
                    $firstDimensionColumnType = $firstDimensionProperties['type'];
                    $secondDimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $secondGroupBy);
                    $secondDimensionColumnType = $secondDimensionProperties['type'];
                } catch (TableNotFoundException $e) {
                    $this->_logger->notice("Not creating $targetSchema.$pairTableName pair table; {$e->getTable()} table not found");
                    continue;
                }
                $db->execute(
                    "CREATE TABLE `{$targetSchema}`.`{$pairTableName}` (
                        `{$firstDimensionName}` {$firstDimensionColumnType} NOT NULL,
                        `{$secondDimensionName}` {$secondDimensionColumnType} NOT NULL,
                        PRIMARY KEY (`{$firstDimensionName}`, `{$secondDimensionName}`),
                        INDEX `idx_second_dimension` (`{$secondDimensionName}` ASC)
                    )"
                );
            }

            try {
                $db->beginTransaction();

                $firstSelectTables = $firstDimensionQuery->getSelectTables();
                $firstSelectFields = $firstDimensionQuery->getSelectFields();
                $firstWheres = $firstDimensionQuery->getWhereConditions();
                $secondSelectTables = $secondDimensionQuery->getSelectTables();
                $secondSelectFields = $secondDimensionQuery->getSelectFields();
                $secondWheres = $secondDimensionQuery->getWhereConditions();

                $firstIdField = $firstSelectFields['id'];
                $secondIdField = $secondSelectFields['id'];

                $selectTablesStr = implode(', ', array_unique(array_merge($firstSelectTables, $secondSelectTables)));
                $wheresStr = implode(' AND ', array_unique(array_merge($firstWheres, $secondWheres)));

                $db->execute("TRUNCATE TABLE `{$targetSchema}`.`{$pairTableName}`");
                $db->execute(
                    "INSERT INTO
                        `{$targetSchema}`.`{$pairTableName}`
                    SELECT DISTINCT
                        $firstIdField,
                        $secondIdField
                    FROM $selectTablesStr
                    WHERE $wheresStr"
                );

                $db->commit();

                $this->builtListTables[$pairTableName] = true;
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
    }

    /**
     * Check if a given dimension has filter lists associated with it.
     *
     * @param  GroupBy $groupBy The GroupBy for the dimension to check.
     * @return boolean          An indicator of if there are lists for the
     *                          given dimension.
     */
    private function checkDimensionForLists(GroupBy $groupBy)
    {
        $dimensionName = $groupBy->getName();

        return $dimensionName !== 'none' && !TimeAggregationUnit::isTimeAggregationUnitName($dimensionName);
    }

    /**
     * Check if a given dimension has roles associated with it.
     *
     * @param  GroupBy $groupBy The GroupBy for the dimension to check.
     * @return boolean          An indicator of if there are roles that use the
     *                          given dimension.
     */
    private function checkDimensionForRoles(GroupBy $groupBy)
    {
        // If the set of dimensions associated with roles has not yet been
        // generated, do so now.
        if (!isset(self::$rolesDimensionNames)) {
            self::$rolesDimensionNames = array();

            $roles = \Configuration\XdmodConfiguration::assocArrayFactory(
                'roles.json',
                CONFIG_DIR,
                null
            )['roles'];

            foreach ($roles as $roleData) {
                $roleDimensionNames = \xd_utilities\array_get($roleData, 'dimensions', array());
                foreach ($roleDimensionNames as $roleDimensionName) {
                    self::$rolesDimensionNames[$roleDimensionName] = true;
                }
            }
        }

        // Check if the given dimension has roles associated with it.
        return array_key_exists($groupBy->getName(), self::$rolesDimensionNames);
    }

    /**
     * Create a new Query constructed around the given GroupBy.
     *
     * @param  Query   $realmQuery A Query of the class of the desired result.
     * @param  GroupBy $groupBy    The GroupBy to construct the Query around.
     * @return Query               A Query constructed around $groupBy.
     */
    private function createDimensionQuery(Query $realmQuery, GroupBy $groupBy)
    {
        $queryClassName = get_class($realmQuery);
        return new $queryClassName(FilterListHelper::getQueryAggregationUnit(), null, null, $groupBy->getName());
    }

    /**
     * Get data about how a dimension is stored in the database.
     *
     * @param  Query   $realmQuery A query for the realm the dimension is in.
     * @param  GroupBy $groupBy    The GroupBy for the dimension to get data for.
     * @return array            Data about the dimension, including:
     *                              * type: The data type used to represent IDs
     *                                      for the dimension.
     */
    private function getDimensionDatabaseProperties(Query $realmQuery, GroupBy $groupBy)
    {
        $db = DB::factory('datawarehouse');
        $helper = MySQLHelper::factory($db);
        $helper->setLogger($this->_logger);

        // TODO After GroupBy is refactored, use GroupBy methods to get the
        // table and column names,
        $dimensionName = $groupBy->getName();
        $dimensionQuery = $this->createDimensionQuery($realmQuery, $groupBy);
        $dimensionQueryTables = $dimensionQuery->getSelectTables();
        $dimensionQueryFields = $dimensionQuery->getSelectFields();
        $dimensionTableStringComponents = explode(' ', $dimensionQueryTables[1]);
        $dimensionTable = $dimensionTableStringComponents[0];
        preg_match('/\.(\S+)\s/', $dimensionQueryFields['id'], $dimensionColumnMatches);
        $dimensionColumn = $dimensionColumnMatches[1];

        if (!$helper->tableExists($dimensionTable)) {
            throw new TableNotFoundException("Could not find table $dimensionTable", 0, null, $dimensionTable);
        }

        $columnDescriptionResults = $db->query("DESCRIBE {$dimensionTable} {$dimensionColumn}");
        if (empty($columnDescriptionResults)) {
            $realmName = $realmQuery->getRealmName();
            throw new Exception("Could not find column $dimensionColumn in table {$dimensionTable}. Realm: $realmName, Dimension: $dimensionName");
        }

        $columnDescriptionResult = $columnDescriptionResults[0];
        return array(
            'type' => $columnDescriptionResult['Type'],
        );
    }
}
