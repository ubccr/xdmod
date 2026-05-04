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
     * A non-contiguous slice (nine days in 2026 plus one day from 2022) 
     * should produce one OR branch per period, with the per-period bind 
     * values matching the slice, not the slice's min/max range.
     */
    public function testNonContiguousSliceProducesOneOrBranchPerPeriod()
    {
        $where =
            "task.start_day_id <= :period_end_day_id"
            . " AND task.end_day_id >= :period_start_day_id"
            . " AND task.is_deleted = 0";

        // Build a 10-period slice: nine consecutive days in 2026 plus an
        // outlier in 2022. day_id values are arbitrary placeholders.
        $slice = array();
        for ($i = 0; $i < 9; $i++) {
            $d = 202600003 + $i;
            $slice[] = array('period_start_day_id' => $d, 'period_end_day_id' => $d);
        }
        $slice[] = array('period_start_day_id' => 202200152, 'period_end_day_id' => 202200152);

        list($whereSql, $params) = self::rewrite($where, $slice);

        // Every period in the slice contributes exactly one bind value pair.
        $this->assertCount(20, $params, 'one :p_start_<i> + :p_end_<i> per period');
        foreach ($slice as $i => $period) {
            $this->assertSame(
                $period['period_start_day_id'],
                $params[":p_start_$i"],
                "p_start_$i bound to slice period $i start"
            );
            $this->assertSame(
                $period['period_end_day_id'],
                $params[":p_end_$i"],
                "p_end_$i bound to slice period $i end"
            );
        }

        // One OR branch per period: count of "task.start_day_id <= :p_end_"
        // occurrences must equal the slice length.
        $this->assertSame(
            count($slice),
            substr_count($whereSql, 'task.start_day_id <= :p_end_'),
            'one OR branch per period'
        );
        // n periods => n-1 " OR " separators inside the rewritten group.
        $this->assertSame(
            count($slice) - 1,
            substr_count($whereSql, ' OR '),
            'one OR between consecutive period branches'
        );

        // The original period bind variables are gone.
        $this->assertStringNotContainsString(':period_start_day_id', $whereSql);
        $this->assertStringNotContainsString(':period_end_day_id', $whereSql);

        // The outlier 2022 day did not get absorbed into a 4-year range.
        $this->assertSame(202200152, $params[':p_start_9']);
        $this->assertSame(202200152, $params[':p_end_9']);
    }

    /**
     * The rewritten WHERE must replace the period overlap predicate with the
     * OR group while leaving all other predicates intact, and must preserve
     * the original column alias inside each OR branch.
     */
    public function testWhereClauseRewritePreservesAliasAndOtherPredicates()
    {
        $where =
            "task.start_day_id <= :period_end_day_id"
            . " AND task.end_day_id >= :period_start_day_id"
            . " AND task.is_deleted = 0"
            . " AND task.resource_id IN (1,2,3)";

        $slice = array(
            array('period_start_day_id' => 202600003, 'period_end_day_id' => 202600003),
            array('period_start_day_id' => 202600004, 'period_end_day_id' => 202600004),
        );

        list($whereSql) = self::rewrite($where, $slice);

        $this->assertStringNotContainsString(
            ':period_start_day_id',
            $whereSql,
            'period_start bind var fully removed from WHERE'
        );
        $this->assertStringNotContainsString(
            ':period_end_day_id',
            $whereSql,
            'period_end bind var fully removed from WHERE'
        );
        $this->assertStringContainsString(
            'task.start_day_id <= :p_end_0',
            $whereSql,
            'first OR branch references original alias and per-period end bind'
        );
        $this->assertStringContainsString(
            'task.end_day_id >= :p_start_0',
            $whereSql,
            'first OR branch references original alias and per-period start bind'
        );
        $this->assertStringContainsString(
            'task.start_day_id <= :p_end_1',
            $whereSql,
            'second OR branch present'
        );
        $this->assertStringContainsString(
            'task.is_deleted = 0',
            $whereSql,
            'unrelated predicates preserved'
        );
        $this->assertStringContainsString(
            'task.resource_id IN (1,2,3)',
            $whereSql,
            'unrelated predicates preserved'
        );
    }

    /**
     * A slice with one period (i.e. when batching effectively degenerates to
     * a single period) must produce a single OR branch with no OR operator.
     */
    public function testSinglePeriodSliceProducesSingleBranch()
    {
        $where =
            "task.start_day_id <= :period_end_day_id"
            . " AND task.end_day_id >= :period_start_day_id";

        $slice = array(
            array('period_start_day_id' => 202600003, 'period_end_day_id' => 202600003),
        );

        list($whereSql, $params) = self::rewrite($where, $slice);

        $this->assertSame(
            0,
            substr_count($whereSql, ' OR '),
            'no OR operator when only one period in the slice'
        );
        $this->assertSame(
            1,
            substr_count($whereSql, 'task.start_day_id <= :p_end_'),
            'exactly one OR branch'
        );
        $this->assertSame(array(':p_start_0' => 202600003, ':p_end_0' => 202600003), $params);
    }

    /**
     * For monthly aggregation, periods cover a range of days
     * (period_start_day_id != period_end_day_id). The rewrite must preserve
     * those ranges per period rather than collapsing them.
     */
    public function testMonthlySliceKeepsEachPeriodRange()
    {
        $where = "task.start_day_id <= :period_end_day_id AND task.end_day_id >= :period_start_day_id";

        $slice = array(
            array('period_start_day_id' => 202600001, 'period_end_day_id' => 202600031),  // a 31-day month
            array('period_start_day_id' => 202600060, 'period_end_day_id' => 202600090),  // a non-contiguous month
        );

        list(, $params) = self::rewrite($where, $slice);

        $this->assertSame(202600001, $params[':p_start_0']);
        $this->assertSame(202600031, $params[':p_end_0']);
        $this->assertSame(202600060, $params[':p_start_1']);
        $this->assertSame(202600090, $params[':p_end_1']);
    }

    /**
     * Action defs use a variety of source-table aliases (task, sr, ra, crs,
     * r). The captured alias must be re-used in every OR branch.
     */
    public function testRewritePreservesArbitraryAlias()
    {
        $where = "sr.start_day_id <= :period_end_day_id AND sr.end_day_id >= :period_start_day_id AND sr.instance_state_id = 1";

        $slice = array(
            array('period_start_day_id' => 202600003, 'period_end_day_id' => 202600003),
            array('period_start_day_id' => 202600004, 'period_end_day_id' => 202600004),
        );

        list($whereSql) = self::rewrite($where, $slice);

        $this->assertStringContainsString('sr.start_day_id <= :p_end_0', $whereSql);
        $this->assertStringContainsString('sr.end_day_id >= :p_start_0', $whereSql);
        $this->assertStringContainsString('sr.start_day_id <= :p_end_1', $whereSql);
        $this->assertStringContainsString('sr.end_day_id >= :p_start_1', $whereSql);
        $this->assertStringContainsString('sr.instance_state_id = 1', $whereSql);
    }
}
