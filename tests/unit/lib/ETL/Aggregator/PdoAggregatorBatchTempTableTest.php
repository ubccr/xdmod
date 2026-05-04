<?php
/**
 * Unit tests for the per-period batch temp-table WHERE rewrite used by
 * pdoAggregator. The rewrite is what scopes the SELECT into `agg_tmp`
 * (and `agg_tmp_stage`) to only the rows that overlap the periods in the
 * current batch slice rather than the full day_id range that spans the
 * slice.
 */

namespace UnitTests\ETL\Aggregator;

use ETL\Aggregator\pdoAggregator;
use ReflectionClass;

class PdoAggregatorBatchTempTableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Invoke the private instance helper via reflection. The helper does not
     * touch $this, so we construct the aggregator without invoking its
     * (non-trivial) constructor and bind the call to the bare instance.
     */
    private static function rewrite($whereClause, array $slice)
    {
        $rc = new ReflectionClass(pdoAggregator::class);
        $instance = $rc->newInstanceWithoutConstructor();
        $m = $rc->getMethod('rewriteWhereForPeriodSlice');
        $m->setAccessible(true);
        return $m->invoke($instance, $whereClause, $slice);
    }

    /**
     * Build a slice element carrying the standard period columns. Tests can
     * pass any subset of overrides.
     */
    private static function period($startDayId, $endDayId, $start = null, $end = null)
    {
        return array(
            'period_start_day_id' => $startDayId,
            'period_end_day_id'   => $endDayId,
            'period_start'        => $start,
            'period_end'          => $end,
        );
    }

    /**
     * A non-contiguous slice (mirrors the real-world bug: nine days in 2026
     * plus one day from 2022) should produce one OR branch per period, with
     * the per-period bind values matching the slice — not the slice's
     * min/max range.
     */
    public function testNonContiguousSliceProducesOneOrBranchPerPeriod()
    {
        $where =
            "task.start_day_id <= :period_end_day_id"
            . " AND task.end_day_id >= :period_start_day_id"
            . " AND task.is_deleted = 0";

        $slice = array();
        for ($i = 0; $i < 9; $i++) {
            $d = 202600007 + $i;
            $slice[] = self::period($d, $d);
        }
        $slice[] = self::period(202200007, 202200007);

        list($whereSql, $params) = self::rewrite($where, $slice);

        // Each period contributes one renamed pair for each :period_*_day_id
        // bind var that appears in the WHERE — here, two pairs per period.
        $this->assertCount(2 * count($slice), $params);
        foreach ($slice as $i => $period) {
            $this->assertSame(
                $period['period_start_day_id'],
                $params[":period_start_day_id_$i"]
            );
            $this->assertSame(
                $period['period_end_day_id'],
                $params[":period_end_day_id_$i"]
            );
        }

        // One OR branch per period.
        $this->assertSame(
            count($slice) - 1,
            substr_count($whereSql, ' OR '),
            'one OR between consecutive period branches'
        );
        // Each branch keeps the original predicate shape (just with renamed
        // bind variables).
        $this->assertSame(
            count($slice),
            substr_count($whereSql, 'task.start_day_id <= :period_end_day_id_'),
            'one branch per period preserves the original overlap shape'
        );

        // Original (un-suffixed) period bind vars are gone.
        $this->assertDoesNotMatchRegularExpression('/:period_start_day_id(?![_0-9])/', $whereSql);
        $this->assertDoesNotMatchRegularExpression('/:period_end_day_id(?![_0-9])/', $whereSql);

        // Outlier 2022 day is bound to its own slot, not absorbed into a range.
        $this->assertSame(202200007, $params[':period_start_day_id_9']);
        $this->assertSame(202200007, $params[':period_end_day_id_9']);
    }

    /**
     * Non-period predicates get duplicated into every OR branch (semantically
     * identical by distributive law). Aliases and other predicates must be
     * preserved verbatim inside each branch.
     */
    public function testWhereClauseRewritePreservesAliasAndOtherPredicates()
    {
        $where =
            "task.start_day_id <= :period_end_day_id"
            . " AND task.end_day_id >= :period_start_day_id"
            . " AND task.is_deleted = 0"
            . " AND task.resource_id IN (1,2,3)";

        $slice = array(self::period(202600007, 202600007), self::period(202600008, 202600008));

        list($whereSql) = self::rewrite($where, $slice);

        $this->assertDoesNotMatchRegularExpression('/:period_start_day_id(?![_0-9])/', $whereSql);
        $this->assertDoesNotMatchRegularExpression('/:period_end_day_id(?![_0-9])/', $whereSql);

        $this->assertStringContainsString('task.start_day_id <= :period_end_day_id_0', $whereSql);
        $this->assertStringContainsString('task.end_day_id >= :period_start_day_id_0', $whereSql);
        $this->assertStringContainsString('task.start_day_id <= :period_end_day_id_1', $whereSql);

        // Non-period predicates duplicated once per OR branch.
        $this->assertSame(2, substr_count($whereSql, 'task.is_deleted = 0'));
        $this->assertSame(2, substr_count($whereSql, 'task.resource_id IN (1,2,3)'));
    }

    /**
     * Single-period slice degenerates to one branch with no OR operator.
     */
    public function testSinglePeriodSliceProducesSingleBranch()
    {
        $where = "task.start_day_id <= :period_end_day_id AND task.end_day_id >= :period_start_day_id";

        $slice = array(self::period(202600007, 202600007));

        list($whereSql, $params) = self::rewrite($where, $slice);

        $this->assertSame(0, substr_count($whereSql, ' OR '));
        $this->assertSame(202600007, $params[':period_start_day_id_0']);
        $this->assertSame(202600007, $params[':period_end_day_id_0']);
    }

    /**
     * Monthly aggregation uses periods that span a range of days. The rewrite
     * must preserve each period's range rather than collapsing them.
     */
    public function testMonthlySliceKeepsEachPeriodRange()
    {
        $where = "task.start_day_id <= :period_end_day_id AND task.end_day_id >= :period_start_day_id";

        $slice = array(self::period(202600001, 202600031), self::period(202600060, 202600090));

        list(, $params) = self::rewrite($where, $slice);

        $this->assertSame(202600001, $params[':period_start_day_id_0']);
        $this->assertSame(202600031, $params[':period_end_day_id_0']);
        $this->assertSame(202600060, $params[':period_start_day_id_1']);
        $this->assertSame(202600090, $params[':period_end_day_id_1']);
    }

    /**
     * Action defs use a variety of source-table aliases (task, sr, ra, crs,
     * gw, r). The rewrite must work with any alias.
     */
    public function testRewritePreservesArbitraryAlias()
    {
        $where = "gw.start_day_id <= :period_end_day_id AND gw.end_day_id >= :period_start_day_id AND gw.is_deleted = 0";

        $slice = array(self::period(202600007, 202600007), self::period(202600008, 202600008));

        list($whereSql) = self::rewrite($where, $slice);

        $this->assertStringContainsString('gw.start_day_id <= :period_end_day_id_0', $whereSql);
        $this->assertStringContainsString('gw.end_day_id >= :period_start_day_id_0', $whereSql);
        $this->assertStringContainsString('gw.start_day_id <= :period_end_day_id_1', $whereSql);
        $this->assertSame(2, substr_count($whereSql, 'gw.is_deleted = 0'));
    }

    /**
     * Single-column BETWEEN form on day_id (used by ood/pagefact_by_*.json:
     * `log_day_id BETWEEN :period_start_day_id AND :period_end_day_id`). The
     * rewrite must produce one BETWEEN per period rather than a single
     * min/max BETWEEN spanning the slice.
     */
    public function testSingleColumnBetweenOnDayIdIsExpandedPerPeriod()
    {
        $where = "log_day_id BETWEEN :period_start_day_id AND :period_end_day_id";

        $slice = array(self::period(202600007, 202600007), self::period(202600008, 202600008), self::period(202600011, 202600011));

        list($whereSql, $params) = self::rewrite($where, $slice);

        $this->assertSame(3, substr_count($whereSql, 'log_day_id BETWEEN :period_start_day_id_'));
        $this->assertSame(2, substr_count($whereSql, ' OR '));
        $this->assertSame(202600007, $params[':period_start_day_id_0']);
        $this->assertSame(202600008, $params[':period_start_day_id_1']);
        $this->assertSame(202600011, $params[':period_start_day_id_2']);
    }

    /**
     * Timestamp BETWEEN form (used by accounts/accountfact_by_*.json:
     * `af.account_creation_time BETWEEN :period_start AND :period_end`).
     * These bind variables resolve to the period_start / period_end timestamp
     * columns in the dirty-period row, not the day_id columns.
     */
    public function testTimestampBetweenIsExpandedPerPeriod()
    {
        $where = "af.account_creation_time BETWEEN :period_start AND :period_end";

        $slice = array(
            self::period(0, 0, '2026-01-01 00:00:00', '2026-01-01 23:59:59'),
            self::period(0, 0, '2026-01-02 00:00:00', '2026-01-02 23:59:59'),
        );

        list($whereSql, $params) = self::rewrite($where, $slice);

        $this->assertStringContainsString(
            'af.account_creation_time BETWEEN :period_start_0 AND :period_end_0',
            $whereSql
        );
        $this->assertStringContainsString(
            'af.account_creation_time BETWEEN :period_start_1 AND :period_end_1',
            $whereSql
        );
        $this->assertSame('2026-01-01 00:00:00', $params[':period_start_0']);
        $this->assertSame('2026-01-01 23:59:59', $params[':period_end_0']);
        $this->assertSame('2026-01-02 00:00:00', $params[':period_start_1']);
        $this->assertSame('2026-01-02 23:59:59', $params[':period_end_1']);

        // The unsuffixed bind vars must not still appear (regression check
        // for :period_start matching inside :period_start_day_id, etc.).
        $this->assertDoesNotMatchRegularExpression('/:period_start(?![_0-9])/', $whereSql);
        $this->assertDoesNotMatchRegularExpression('/:period_end(?![_0-9])/', $whereSql);
    }

    /**
     * `:period_start` must not eat into `:period_start_day_id`. A WHERE that
     * mixes both kinds of bind variable in the same clause must rewrite each
     * one independently.
     */
    public function testMixedDayIdAndTimestampBindVarsAreDisambiguated()
    {
        $where =
            "log_day_id BETWEEN :period_start_day_id AND :period_end_day_id"
            . " AND log_time BETWEEN :period_start AND :period_end";

        $slice = array(
            self::period(202600007, 202600007, '2026-01-07 00:00:00', '2026-01-07 23:59:59'),
        );

        list($whereSql, $params) = self::rewrite($where, $slice);

        $this->assertStringContainsString('log_day_id BETWEEN :period_start_day_id_0 AND :period_end_day_id_0', $whereSql);
        $this->assertStringContainsString('log_time BETWEEN :period_start_0 AND :period_end_0', $whereSql);
        $this->assertSame(202600007, $params[':period_start_day_id_0']);
        $this->assertSame(202600007, $params[':period_end_day_id_0']);
        $this->assertSame('2026-01-07 00:00:00', $params[':period_start_0']);
        $this->assertSame('2026-01-07 23:59:59', $params[':period_end_0']);
    }
}
