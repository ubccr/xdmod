<?php

namespace DataWarehouse\Query;

abstract class RawQueryTypes
{
    public const ACCOUNTING         = 0;
    public const BATCH_SCRIPT       = 1;
    public const EXECUTABLE         = 2;
    public const PEERS              = 3;
    public const NORMALIZED_METRICS = 4;
    public const DETAILED_METRICS   = 5;
    public const TIMESERIES_METRICS = 6;
    public const ANALYTICS          = 7;
    public const VM_INSTANCE        = 8;
}
