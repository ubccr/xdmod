<?php

use CCR\DB;
use CCR\DB\MySQLHelper;
use CCR\Loggable;
use DB\Exceptions\TableNotFoundException;
use DB\EtlJournalHelper;
use Realm\iGroupBy;
use Realm\iRealm;
use DataWarehouse\Query\iQuery;
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
     * The date and time to use to retreive row from aggregate tables to get filter values from
     *
     * @var string|null
     */
    private $lastModifiedStartDate = null;

    /**
     * Used to mark if the filter lists should be appended to or deleted and recreated.
     *
     * @var boolean
     */
    private $appendToList = false;

    /**
     * Name of temporary table to build filter lists from. Holds rows from a realms aggregate table
     *
     * @var string
     */
    private $filterTemporaryTable = '';

    public function __construct() {
        parent::__construct();
        $this->filterTemporaryTable = uniqid('modw_aggregates.filter_tmp_', true);
    }

    /**
     * Build filter lists for all realms' dimensions.
     */
    public function buildAllLists()
    {
        // Get the ids of the realms to be processed.
        $realmNames = \Realm\Realm::getRealmNames();

        // Generate lists for each realm's dimensions.
        foreach ($realmNames as $realmId => $realmName) {
            $this->buildRealmLists($realmId);
        }
    }

    /**
     * Check to see if the table being used to retrieve filter values has a last_modified column.
     *
     * @param string $schema Name of the schema the table is in
     * @param string $table Name of the table to check for a last_modified column
     * @param string $column Name of column that is has the time a row was last modified. Defauls to last_modified
     * @return boolean
     */
    private function doesLastModifiedColumnExist(string $schema, string $table, string $column = "last_modified") {
        $db = DB::factory('datawarehouse');

        $doesFieldExist = "SELECT *
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = :schema
            AND TABLE_NAME = :tableName
            AND COLUMN_NAME = :column";

        $result = $db->execute($doesFieldExist, [":schema" => $schema, ":tableName" => $table, ":column" => $column]);

        return ($result > 0);
    }

    /**
     * Build filter lists for the given realm's dimensions.
     *
     * @param string $realmName The name of a realm to build lists for.
     */
    public function buildRealmLists($realmName, $appendToList = false)
    {
        // Get a query for the given realm.
        $startTime = microtime(true);
        $this->logger->notice('start', ['action' => $realmName . '.build-filter-list']);

        $realmQuery = new \DataWarehouse\Query\AggregateQuery(
            $realmName,
            FilterListHelper::getQueryAggregationUnit(),
            null,
            null,
            'none'
        );

        // Get the dimensions in the given realm.
        $currentRealm = \Realm\Realm::factory($realmName);

        $tables = $realmQuery->getTables();
        $tableAliasKey = array_key_first($tables);
        $schema = $tables[$tableAliasKey]->getSchema()->getName();
        $tableName = $tables[$tableAliasKey]->getName();
        $journalHelper = new EtlJournalHelper($schema, $tableName);

        // If the last modified column does not exist on the aggregate table then there is no way
        // to select the most recently aggregated rows so we cannot append new data.
        if($appendToList && !$this->doesLastModifiedColumnExist($schema, $tableName)) {
            $appendToList = false;
        }

        if($appendToList && $this->doesLastModifiedColumnExist($schema, $tableName) && $journalHelper->getLastModified() === null) {
            $appendToList = false;
        }

        $this->setAppendToList($appendToList);

        if($this->appendToList) {
            $db = DB::factory('datawarehouse');
            $lastModified = $journalHelper->getLastModified();
            $this->setLastModifiedStartDate($lastModified);
            $tempTableSql = "CREATE TEMPORARY TABLE $this->filterTemporaryTable AS SELECT * FROM $schema.$tableName where last_modified >= '$this->lastModifiedStartDate'";
            $this->logger->debug("Creating temporary table: $tempTableSql");
            $db->execute($tempTableSql);
        }

        // Generate the lists for each dimension and each pairing of dimensions.
        foreach ($currentRealm->getGroupByObjects() as $groupByObj) {
            $this->buildDimensionLists($realmQuery, $groupByObj, $currentRealm);
        }

        if($this->appendToList) {
            $db->execute("DROP TEMPORARY TABLE IF EXISTS $this->filterTemporaryTable");
        }

        $endTime = microtime(true);
        $journalHelper->markAsDone(date("Y-m-d h:i:s", $startTime), date("Y-m-d h:i:s", $endTime));

        $this->logger->notice(
            'end',
            [
                'action' => $realmName . '.build-filter-list',
                'start_time' => $startTime,
                'end_time' => $endTime
            ]
        );
    }

    /**
     * Build filter lists for the given dimension.
     *
     * NOTE: This function does not support dimensions with multi-column keys even though the
     *       GroupBy classes do. It must be refactored in order to support them. -SMG 2019-09-09
     *
     * @param iQuery   $realmQuery A query for the realm the dimension is in.
     * @param iGroupBy $groupBy    The dimension's GroupBy to build lists for.
     * @param iRealm   $realm      The realm currently being used.
     */
    private function buildDimensionLists(iQuery $realmQuery, iGroupBy $groupBy, iRealm $currentRealm)
    {
        // Check that the given dimension has associated filter lists.
        // If it does not, stop.
        if (!$this->checkDimensionForLists($groupBy)) {
            return;
        }

        // Generate the main list table. If the list table does not already
        // exist, create it.
        $dimensionId = $groupBy->getId();
        $startTime = microtime(true);
        $this->logger->notice(
            'start',
            [
                'action' => $currentRealm->getName() . '.build-filter-list.' . $dimensionId
            ]
        );

        $mainTableName = FilterListHelper::getTableName($realmQuery, $groupBy);

        $db = DB::factory('datawarehouse');
        $targetSchema = FilterListHelper::getSchemaName();

        try {
            $dimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $groupBy);
        } catch (TableNotFoundException $e) {
            $this->logger->notice("Not creating $targetSchema.$mainTableName list table; {$e->getTable()} table not found");
            return;
        }
        $dimensionColumnType = $dimensionProperties['type'];

        if(!$this->appendToList) {
            $db->execute("DROP TABLE IF EXISTS `{$targetSchema}`.`{$mainTableName}`;");
            $db->execute(
                "CREATE TABLE `{$targetSchema}`.`{$mainTableName}` (
                    `{$dimensionId}` {$dimensionColumnType} NOT NULL,
                    PRIMARY KEY (`{$dimensionId}`)
                ) CHARACTER SET utf8 COLLATE utf8_unicode_ci"
            );
        }

        $dimensionQuery = $this->createDimensionQuery($realmQuery, $groupBy);

        $selectTables = $dimensionQuery->getSelectTables();
        $selectFields = $dimensionQuery->getSelectFields();
        $wheres = $dimensionQuery->getWhereConditions();

        $idField = $selectFields[ sprintf('%s_id', $groupBy->getId()) ];
        $wheresStr = implode(' AND ', $wheres);

        $lastModifiedClause = "";
        $onDuplicateClause = "";
        if($this->appendToList) {
            $tables = $dimensionQuery->getTables();
            $aggregateTableAlias = $tables[array_key_first($tables)]->getAlias()->getName();
            $selectTables[0] = implode(" ", [$this->filterTemporaryTable, $aggregateTableAlias]);
            $onDuplicateClause = "ON DUPLICATE KEY UPDATE $dimensionId = ".$dimensionQuery->getFields()[sprintf('%s_id', $groupBy->getId())]->getDefinition();
            $lastModifiedClause = "AND last_modified >= '$this->lastModifiedStartDate'";
        }

        $selectTablesStr = implode(', ', $selectTables);
        $filterListSql = "INSERT INTO
                `{$targetSchema}`.`{$mainTableName}`
            SELECT DISTINCT
                $idField
            FROM $selectTablesStr
            WHERE $wheresStr
            $lastModifiedClause
            $onDuplicateClause";

        $this->logger->debug("Filter List SQL: $filterListSql");

        $db->execute($filterListSql);

        $this->builtListTables[$mainTableName] = true;

        // Generate list tables pairing this dimension with every other
        // dimension in the realm that's associated with roles.
        $realmGroupBys = $currentRealm->getGroupByNames();

        foreach ($realmGroupBys as $realmGroupById => $realmGroupByNames) {
            // If this dimension is the given dimension, skip it.
            $dimensionNameComparison = strcasecmp($dimensionId, $realmGroupById);
            if ($dimensionNameComparison === 0) {
                continue;
            }

            // If this dimension does not have lists associated with it
            // or is not associated with any roles, skip it.
            $realmGroupBy = $currentRealm->getGroupByObject($realmGroupById);
            if (!$this->checkDimensionForLists($realmGroupBy) || !$this->checkDimensionForRoles($realmGroupBy)) {
                continue;
            }

            // Use alphabetical ordering to construct the list table name.
            // If the given dimension is ordered after the current dimension
            // and all realm lists are being built, this can be skipped, since
            // this pairing will have been taken care of by the other dimension.
            if ($dimensionNameComparison < 0) {
                $firstGroupBy = $groupBy;
                $firstDimensionId = $dimensionId;
                $firstDimensionQuery = $dimensionQuery;
                $secondGroupBy = $realmGroupBy;
                $secondDimensionId = $realmGroupById;
                $secondDimensionQuery = $this->createDimensionQuery($realmQuery, $realmGroupBy);
            } else {
                $firstGroupBy = $realmGroupBy;
                $firstDimensionId = $realmGroupById;
                $firstDimensionQuery = $this->createDimensionQuery($realmQuery, $realmGroupBy);
                $secondGroupBy = $groupBy;
                $secondDimensionId = $dimensionId;
                $secondDimensionQuery = $dimensionQuery;
            }
            $pairTableName = FilterListHelper::getTableName($realmQuery, $firstGroupBy, $secondGroupBy);
            if (array_key_exists($pairTableName, $this->builtListTables)) {
                continue;
            }

            // Generate the pair table. If the pair table does not exist,
            // create it.
            try {
                $firstDimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $firstGroupBy);
                $firstDimensionColumnType = $firstDimensionProperties['type'];
                $secondDimensionProperties = $this->getDimensionDatabaseProperties($realmQuery, $secondGroupBy);
                $secondDimensionColumnType = $secondDimensionProperties['type'];
            } catch (TableNotFoundException $e) {
                $this->logger->notice("Not creating $targetSchema.$pairTableName pair table; {$e->getTable()} table not found");
                continue;
            }

            if(!$this->appendToList){
                $db->execute("DROP TABLE IF EXISTS `{$targetSchema}`.`{$pairTableName}`");
                $db->execute(
                    "CREATE TABLE `{$targetSchema}`.`{$pairTableName}` (
                        `{$firstDimensionId}` {$firstDimensionColumnType} NOT NULL,
                        `{$secondDimensionId}` {$secondDimensionColumnType} NOT NULL,
                        PRIMARY KEY (`{$firstDimensionId}`, `{$secondDimensionId}`),
                        INDEX `idx_second_dimension` (`{$secondDimensionId}` ASC)
                    ) CHARACTER SET utf8 COLLATE utf8_unicode_ci"
                );
            }

            $firstSelectTables = $firstDimensionQuery->getSelectTables();
            $firstSelectFields = $firstDimensionQuery->getSelectFields();
            $firstWheres = $firstDimensionQuery->getWhereConditions();
            $secondSelectTables = $secondDimensionQuery->getSelectTables();
            $secondSelectFields = $secondDimensionQuery->getSelectFields();
            $secondWheres = $secondDimensionQuery->getWhereConditions();
            $wheresStr = implode(' AND ', array_unique(array_merge($firstWheres, $secondWheres)));

            $firstIdField = $firstSelectFields[ sprintf('%s_id', $firstDimensionId) ];
            $secondIdField = $secondSelectFields[ sprintf('%s_id', $secondDimensionId) ];

            $lastModifiedClause = "";
            $onDuplicateClause = "";

            if ($this->appendToList) {
                $firstTables = $firstDimensionQuery->getTables();
                $firstAggregateTableAlias = $firstTables[array_key_first($firstTables)]->getAlias()->getName();
                $firstSelectTables[0] = implode(" ", [$this->filterTemporaryTable, $firstAggregateTableAlias]);
                $firstOnDuplicate = "$firstDimensionId = ".$firstDimensionQuery->getFields()[sprintf('%s_id', $firstDimensionId)]->getDefinition();

                $secondTables = $secondDimensionQuery->getTables();
                $secondAggregateTableAlias = $secondTables[array_key_first($secondTables)]->getAlias()->getName();
                $secondSelectTables[0] = implode(" ", [$this->filterTemporaryTable, $secondAggregateTableAlias]);
                $secondOnDuplicate = "$secondDimensionId = ".$secondDimensionQuery->getFields()[sprintf('%s_id', $secondDimensionId)]->getDefinition();

                $lastModifiedClause = "AND last_modified >= '$this->lastModifiedStartDate'";
                $onDuplicateClause = "ON DUPLICATE KEY UPDATE $firstOnDuplicate, $secondOnDuplicate";
            }

            $selectTablesStr = implode(', ', array_unique(array_merge($firstSelectTables, $secondSelectTables)));

            $filterListSql = "INSERT INTO
                    `{$targetSchema}`.`{$pairTableName}`
                SELECT DISTINCT
                    $firstIdField,
                    $secondIdField
                FROM $selectTablesStr
                WHERE $wheresStr
                $lastModifiedClause
                $onDuplicateClause";


            $this->logger->debug("Filter List SQL: $filterListSql");
            $db->execute($filterListSql);

            $this->builtListTables[$pairTableName] = true;
        }
        $this->logger->notice(
            'end',
            [
                'action' => $currentRealm->getName() . '.build-filter-list.' . $dimensionId,
                'start_time' => $startTime,
                'end_time' => microtime(true)
            ]
        );

    }

    /**
     * Check if a given dimension has filter lists associated with it.
     *
     * @param  iGroupBy $groupBy The GroupBy for the dimension to check.
     * @return boolean          An indicator of if there are lists for the
     *                          given dimension.
     */
    private function checkDimensionForLists(iGroupBy $groupBy)
    {
        $dimensionId = $groupBy->getId();

        return $dimensionId !== 'none' && !TimeAggregationUnit::isTimeAggregationUnitName($dimensionId);
    }

    /**
     * Check if a given dimension has roles associated with it.
     *
     * @param  iGroupBy $groupBy The GroupBy for the dimension to check.
     * @return boolean          An indicator of if there are roles that use the
     *                          given dimension.
     */
    private function checkDimensionForRoles(iGroupBy $groupBy)
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
        return array_key_exists($groupBy->getId(), self::$rolesDimensionNames);
    }

    /**
     * Create a new Query constructed around the given GroupBy.
     *
     * @param  iQuery   $realmQuery A Query of the class of the desired result.
     * @param  iGroupBy $groupBy    The GroupBy to construct the Query around.
     * @return Query               A Query constructed around $groupBy.
     */
    private function createDimensionQuery(iQuery $realmQuery, iGroupBy $groupBy)
    {
        $queryClassName = get_class($realmQuery);
        return new $queryClassName(
            $realmQuery->getRealmName(),
            FilterListHelper::getQueryAggregationUnit(),
            null,
            null,
            $groupBy->getId()
        );
    }

    /**
     * Get data about how a dimension is stored in the database.
     *
     * @param  iQuery   $realmQuery A query for the realm the dimension is in.
     * @param  iGroupBy $groupBy    The GroupBy for the dimension to get data for.
     * @return array            Data about the dimension, including:
     *                              * type: The data type used to represent IDs
     *                                      for the dimension.
     */
    private function getDimensionDatabaseProperties(iQuery $realmQuery, iGroupBy $groupBy)
    {
        $db = DB::factory('datawarehouse');
        $helper = MySQLHelper::factory($db);
        $helper->setLogger($this->logger);

        // TODO After GroupBy is refactored, use GroupBy methods to get the
        // table and column names,
        $dimensionId = $groupBy->getId();
        $dimensionQuery = $this->createDimensionQuery($realmQuery, $groupBy);
        $dimensionQueryTables = $dimensionQuery->getSelectTables();
        $dimensionQueryFields = $dimensionQuery->getSelectFields();
        $dimensionTableStringComponents = explode(' ', $dimensionQueryTables[1]);
        $dimensionTable = $dimensionTableStringComponents[0];
        preg_match('/\.(\S+)\s/', $dimensionQueryFields[ sprintf('%s_id', $dimensionId) ], $dimensionColumnMatches);
        $dimensionColumn = $dimensionColumnMatches[1];

        if (!$helper->tableExists($dimensionTable)) {
            throw new TableNotFoundException("Could not find table $dimensionTable", 0, null, $dimensionTable);
        }

        $sql = sprintf('DESCRIBE %s %s', $dimensionTable, $dimensionColumn);
        try {
            $columnDescriptionResults = $db->query($sql);
        } catch (\PDOException $e) {
            throw new \Exception(
                sprintf("Error inspecting dimension column '%s': %s", $sql, $e->getMessage())
            );
        }
        if (empty($columnDescriptionResults)) {
            $realmName = $realmQuery->getRealmName();
            throw new Exception("Could not find column $dimensionColumn in table {$dimensionTable}. Realm: $realmName, Dimension: $dimensionId");
        }

        $columnDescriptionResult = $columnDescriptionResults[0];
        return array(
            'type' => $columnDescriptionResult['Type'],
        );
    }

    /**
     * Sets value for lastModifiedStartDate property
     *
     * @param string|null $lastModifiedStartDate
     */
    public function setLastModifiedStartDate(?string $lastModifiedStartDate)
    {
        $this->lastModifiedStartDate = $lastModifiedStartDate;
    }

    /**
     * Sets value for appendToList property
     *
     * @param boolean $appendToList
     */
    public function setAppendToList(bool $appendToList)
    {
        $this->appendToList = $appendToList;
    }
}
