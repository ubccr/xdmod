<?php

namespace DataWarehouse\Query;

abstract class RawQueryTypes
{
    const ACCOUNTING         = 0;
    const BATCH_SCRIPT       = 1;
    const EXECUTABLE         = 2;
    const PEERS              = 3;
    const NORMALIZED_METRICS = 4;
    const DETAILED_METRICS   = 5;
    const TIMESERIES_METRICS = 6;
    const ANALYTICS          = 7;
}
