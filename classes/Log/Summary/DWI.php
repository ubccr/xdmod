<?php
/**
 * Log summary class for the data warehouse initialized. Extends the Summary class to define
 * $recordCountKeys specific to the DWI logger.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 */

namespace Log\Summary;
use Log\Summary;

class DWI
extends Summary
{
    /**
     * The array keys used by the logger to indicate record counts.
     *
     * @var array
     */

    protected $recordCountKeys = array(
        'records_examined',
        'records_loaded'
    );

}  // class DWI
