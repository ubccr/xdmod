<?php
/* ==========================================================================================
 * Simple aggregator is essentially another name for pdoAggregator.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-12-09
 *
 * @see pdoAggregator
 * @see iAction
 * ------------------------------------------------------------------------------------------
 */

namespace ETL\Aggregator;

use ETL\iAction;

class SimpleAggregator extends pdoAggregator implements iAction
{

}  // class SimpleAggregator
