<?php
/* ==========================================================================================
 * Options used to control the overall ETL process.  These are typically provided from defaults or
 * from the command line.
 *
 * This options object is also an iterator for the chunked date interval, allowing actions to
 * iterate over this object to break the ETL process up into manageble chunks.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-09-25
 * ==========================================================================================
 */

namespace ETL;

use Log;
use Exception;
use ETL\DataEndpoint\iDataEndpoint;
use ETL\DbEntity\Query;

class EtlOverseerOptions extends Loggable implements \Iterator
{
    // Start of the ETL period
    private $startDate = null;

    // End of the ETL period.
    private $endDate = null;

    // The number of days to ingest from the source
    private $numberOfDays = null;

    // Start of the last modified period.  This may be used by some actions such as ingestion to
    // operate on the most recently added data.
    private $lastModifiedStartDate = null;

    // End of the last modified period.  This may be used by some actions such as ingestion to
    // operate on the most recently added data.
    private $lastModifiedEndDate = null;

    // If breaking the ETL period up into chunks, the number of days for each chunk otherwise NULL.
    private $etlIntervalChunkSize = null;

    // An array of (chunk start date, chunk end date) tuples. This is the (start date, end date)
    // broken into $etlIntervalChunkSize chunks.
    private $etlPeriodChunkList = null;

    // TRUE if we will be forcing a re-propcessing of the an action's data
    private $forceOperation = false;

    // An array of resource ids that will be the only resources that actions supporting inclusion
    // will be operating on. An empty list or null implies all resources. NOTE: This is mutually
    // exclusive with $excludeResourceCodes
    private $includeOnlyResourceCodes = array();

    // An array of resource ids that will be excluded from supporting action execution. An empty
    // list implies no resources will be excluded. NOTE: This is mutually exclusive with
    // $includeOnlyResourceCodes
    private $excludeResourceCodes = array();

    // Directory where lock files are stored
    private $lockDir = null;

    // Optional prefix for lock files
    private $lockFilePrefix = null;

    // A mapping of resource codes to resource ids.
    private $resourceCodeToIdMap = array();

    // A list of all requested section names
    private $sectionNames = array();

    // A list of all requested action names
    private $actionNames = array();

    // Perform all operations except execution of the actions
    private $dryrun = false;

    // Enhanced output
    private $verbose = false;

    // Restrictions may be placed on the query by the ETL Overseer. For example, start_date,
    // end_date, resources, etc. These keys identify the supported restrictions and are used to set
    // them for a query.

    const RESTRICT_START_DATE = 'start_date';
    const RESTRICT_END_DATE = 'end_date';
    const RESTRICT_LAST_MODIFIED_START_DATE = 'last_modified_start_date';
    const RESTRICT_LAST_MODIFIED_END_DATE = 'last_modified_end_date';
    const RESTRICT_INCLUDE_ONLY_RESOURCES = 'include_only_resource_codes';
    const RESTRICT_EXCLUDE_RESOURCES = 'exclude_resource_codes';

    protected $supportedOverseerRestrictions = array();

    /* ------------------------------------------------------------------------------------------
     * Constructor. We are using an array for options because the list with optional parameters was
     * getting unwieldy.
     *
     * @param $options An associative array of options to the overseer. Supported option keys are:
     *
     * actions => The list of individual ETL action names to execute.
     * chunk-size-days => The nuber of days in each ETL chunk, or NULL for no chunking.
     * dryrun => TRUE to enter dryrun mode and perform verification only.
     * end-date => The ETL end date
     * exclude-resource-codes =>The list of resource ids to process during ETL. No resources means
     *   process them all.
     * force => TRUE to force ETL actions even if nothing new was detected during the requested time
     *   period. Useful to force re-processing of some actions.
     * include-only-resource-codes => The list of resource ids to process during ETL. No resources
     *   means process them all.
     * last-modified-start-date => The ETL last modified start date, used by some actions. Defaults
     *   to the start of the ETL process.
     * last-modified-end-date => The ETL last modified end date, used by some actions. Defaults
     *   to the start of the ETL process.
     * process-sections => The list of ETL sections to process.
     * resource-code-map => An associative array where the keys are resource codes and the
     *   values are the corresponding id in the database.
     * start-date => The ETL start date
     *
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(array $options, Log $logger = null)
    {
        parent::__construct($logger);

        foreach ( $options as $option => $value ) {
            switch ($option) {
                case 'actions':
                    $this->setActionNames($value);
                    break;
                case 'chunk-size-days':
                    $this->setChunkSize($value);
                    break;
                case 'dryrun':
                    $this->setDryrun($value);
                    break;
                case 'end-date':
                    $this->setEndDate($value);
                    break;
                case 'exclude-resource-codes':
                    $this->setExcludeResourceCodes($value);
                    break;
                case 'force':
                    $this->setForce($value);
                    break;
                case 'include-only-resource-codes':
                    $this->setIncludeOnlyResourceCodes($value);
                    break;
                case 'last-modified-start-date':
                    $this->setLastModifiedStartDate($value);
                    break;
                case 'last-modified-end-date':
                    $this->setLastModifiedEndDate($value);
                    break;
                case 'number-of-days':
                    $this->setNumberOfDays($value);
                    break;
                case 'resource-code-map':
                    $this->setResourceCodeToIdMap($value);
                    break;
                case 'process-sections':
                    $this->setSectionNames($value);
                    break;
                case 'start-date':
                    $this->setStartDate($value);
                    break;
                case 'lock-dir':
                    $this->setLockDir($value);
                    break;
                case 'lock-file-prefix':
                    $this->setLockFilePrefix($value);
                    break;
                default:
                    break;
            }  // switch ($option)
        }  // foreach ( $options as $option => $value )

        $this->generateEtlChunkList();

        // Automatially build up the list of supported restrictions

        $r = new \ReflectionClass($this);
        foreach ( $r->getConstants() as $const => $value ) {
            if ( 0 === strpos($const, 'RESTRICT_') ) {
                $this->supportedOverseerRestrictions[] = $value;
            }
        }

    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * Apply the overseer restrictions to the specified query object using values contained in the
     * overseer.  This will replace the ${VALUE} macro present in the restriction parsed from the
     * query configuration with the value from the overseer. If the provided value is an array, it
     * will be imploded into a comma separated list. If the value is NULL the restriction will not
     * be added.
     *
     * @param $query A Query object that contains the restriction templates
     * @param $endpoint A DataEndpoint object used for quoting special characters
     * @param $endpoint A DataEndpoint object used for quoting special characters
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function applyOverseerRestrictions(Query $query, iDataEndpoint $endpoint, array $overrides = array())
    {
        $queryRestrictions = $query->getOverseerRestrictions();

        foreach ( $queryRestrictions as $restriction => $template ) {

            if ( ! in_array($restriction, $this->supportedOverseerRestrictions) ) {
                $msg = "Query specified unsupported overseer restriction '$restriction'";
                $this->logger->notice($msg);
            }

            // Apply the templates
            //
            // Note that the overseer may split ETL date range into chunks so apply the CURRENT
            // start/end dates rather than the initial start/end date.

            $replacement = null;

            switch ($restriction) {
                case self::RESTRICT_START_DATE:
                    if ( null !== ($value = $this->getCurrentStartDate()) ) {
                        $replacement = $endpoint->quote($value);
                    }
                    break;
                case self::RESTRICT_END_DATE:
                    if ( null !== ($value = $this->getCurrentEndDate()) ) {
                        $replacement = $endpoint->quote($value);
                    }
                    break;
                case self::RESTRICT_LAST_MODIFIED_START_DATE:
                    if ( null !== ($value = $this->getLastModifiedStartDate()) ) {
                        $replacement = $endpoint->quote($value);
                    }
                    break;
                case self::RESTRICT_LAST_MODIFIED_END_DATE:
                    if ( null !== ($value = $this->getLastModifiedEndDate()) ) {
                        $replacement = $endpoint->quote($value);
                    }
                    break;
                case self::RESTRICT_INCLUDE_ONLY_RESOURCES:
                    $value = ( array_key_exists($restriction, $overrides) && is_array($overrides[$restriction])
                               ? $overrides[$restriction]
                               : $this->includeOnlyResourceCodes );
                    if ( count($value) > 0 ) {
                        $replacement = "(" . implode(",", $this->mapResourceCodesToIds($value)) . ")";
                    }
                    break;
                case self::RESTRICT_EXCLUDE_RESOURCES:
                    $value = ( array_key_exists($restriction, $overrides) && is_array($overrides[$restriction])
                               ? $overrides[$restriction]
                               : $this->excludeResourceCodes );
                    if ( count($value) > 0 ) {
                        $replacement = "(" . implode(",", $this->mapResourceCodesToIds($value)) . ")";
                    }
                    break;
                default:
                    break;
            } // switch ($restriction)

            if ( null !== $replacement ) {
                $value = str_replace('${VALUE}', $replacement, $template);
                $query->addOverseerRestrictionValue($restriction, $value);
            }
        }  // foreach ( $queryRestrictions as $restriction => $template )

        return $this;
    }  // applyOverseerRestrictions()

    /* ------------------------------------------------------------------------------------------
     * @return The ETL period start date.
     * ------------------------------------------------------------------------------------------
     */

    public function getStartDate()
    {
        return $this->startDate;
    }  // getStartDate()

    /* ------------------------------------------------------------------------------------------
     * Set the start date in a format suitable for use by the database. If date is NULL, use the
     * current date to ensure that the date is always set.
     *
     * @param $date A date representation or null to use the current date.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setStartDate($date)
    {
        if ( null === $date ) {
            $this->startDate = date("Y-m-d H:i:s");
        } else {
            if ( false === ($ts = strtotime($date)) ) {
                $msg = get_class($this) . ": Could not parse start date '$date'";
                throw new Exception($msg);
            }

            $this->startDate = date("Y-m-d H:i:s", $ts);
        }

        return $this;
    }  // setStartDate()

    /* ------------------------------------------------------------------------------------------
     * @return The ETL period end date.
     * ------------------------------------------------------------------------------------------
     */

    public function getEndDate()
    {
        return $this->endDate;
    }  // getEndDate()

    /* ------------------------------------------------------------------------------------------
     * Set the end date in a format suitable for use by the database. If date is NULL, use the
     * current date to ensure that the date is always set.
     *
     * @param $date A date representation, or null to use the current date.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setEndDate($date)
    {
        if ( null === $date ) {
            $this->endDate = date("Y-m-d H:i:s");
        } else {
            if ( false === ($ts = strtotime($date)) ) {
                $msg = get_class($this) . ": Could not parse end date '$date'";
                throw new Exception($msg);
            }

            $this->endDate = date("Y-m-d H:i:s", $ts);
        }

        return $this;
    }  // setEndDate()

    /* ------------------------------------------------------------------------------------------
     * @return The ETL period end date.
     * ------------------------------------------------------------------------------------------
     */

    public function getNumberOfDays()
    {
        return $this->numberOfDays;
    }  // getNumberOfDays()

    /* ------------------------------------------------------------------------------------------
     * Set the end date in a format suitable for use by the database. If date is NULL, use the
     * current date to ensure that the date is always set.
     *
     * @param $date A date representation, or null to use the current date.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setNumberOfDays($numberOfDays)
    {
        if ( null !== $numberOfDays && ! is_numeric($numberOfDays) ) {
            $msg = "Invalid number for number of days: '$numberOfDays'";
            $this->logAndThrowException($msg);
        }

        $this->numberOfDays = $numberOfDays;

        return $this;
    }  // setNumberOfDays()

    /* ------------------------------------------------------------------------------------------
     * @return The start of the last modified period. This may be used by some actions such as
     * ingestion to operate on the most recently added data.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedStartDate()
    {
        return $this->lastModifiedStartDate;
    }  // getLastModifiedStartDate()

    /* ------------------------------------------------------------------------------------------
     * Set the start date for the last modified period in a format suitable for use by the
     * database. If date is NULL, use the current date to ensure that the date is always set.
     *
     * @param $date A date representation or null to use the current date.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLastModifiedStartDate($date)
    {
        if ( null === $date ) {
            $this->lastModifiedStartDate = null;
        } else if ( false === ( $ts = strtotime($date)) ) {
            $msg = get_class($this) . ": Could not parse last modified start date '$date'";
            throw new Exception($msg);
        } else {
            $this->lastModifiedStartDate = date("Y-m-d H:i:s", $ts);
        }

        return $this;
    }  // setStartDate()

    /* ------------------------------------------------------------------------------------------
     * @return The end of the last modified period. This may be used by some actions such as
     * ingestion to operate on the most recently added data.
     * ------------------------------------------------------------------------------------------
     */

    public function getLastModifiedEndDate()
    {
        return $this->lastModifiedEndDate;
    }  // getLastModifiedEndDate()

    /* ------------------------------------------------------------------------------------------
     * Set the end date for the last modified period in a format suitable for use by the
     * database. If date is NULL, use the current date to ensure that the date is always set.
     *
     * @param $date A date representation, or null to use the current date.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLastModifiedEndDate($date)
    {
        if ( null === $date ) {
            $this->lastModifiedEndDate = null;
        } else if ( false === ( $ts = strtotime($date)) ) {
            $msg = get_class($this) . ": Could not parse last modified start date '$date'";
            throw new Exception($msg);
        } else {
            $this->lastModifiedEndDate = date("Y-m-d H:i:s", $ts);
        }

        return $this;
    }  // setLastModifiedEndDate()

    /* ------------------------------------------------------------------------------------------
     * @return The current start date from the list of chunked date intervals.  This can be used
     *   inside of an action to get the current date that it should be working with.
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentStartDate() {
        $current = current($this->etlPeriodChunkList);
        return ( false === $current ? null : $current[0] );
    }  // getCurrentStartDate()

    /* ------------------------------------------------------------------------------------------
     * @return The current end date from the list of chunked date intervals.  This can be used
     *   inside of an action to get the current date that it should be working with.
     * ------------------------------------------------------------------------------------------
     */

    public function getCurrentEndDate() {
        $current = current($this->etlPeriodChunkList);
        return ( false === $current ? null : $current[1] );
    }  // getCurrentEndDate()

    /* ------------------------------------------------------------------------------------------
     * @return The number of days in each ETL chunk, or NULL for no chunking.
     * ------------------------------------------------------------------------------------------
     */

    public function getChunkSize()
    {
        return $this->etlIntervalChunkSize;
    }  // getChunkSize()

    /* ------------------------------------------------------------------------------------------
     * Set the number of days in each ETL interval chunk, or NULL to not chunk.
     *
     * @param $chunkSize The chunk size in days, or NULL for no chunking.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setChunkSize($days)
    {
        if ( ! (null === $days || is_numeric($days) ) ) {
            $msg = get_class($this) . ": Chunk size must be NULL or numeric";
            throw new Exception($msg);
        }
        $this->etlIntervalChunkSize = $days;
        return $this;
    }  // setChunkSize()

    /* ------------------------------------------------------------------------------------------
     * @return The directory where lock files are stored.
     * ------------------------------------------------------------------------------------------
     */

    public function getLockDir()
    {
        return $this->lockDir;
    }  // getLockDir()

    /* ------------------------------------------------------------------------------------------
     * Set the directory where lock files are stored.
     *
     * @param $dir The lock directory.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLockDir($dir)
    {
        $this->lockDir = $dir;
        return $this;
    }  // setLockDir()

    /* ------------------------------------------------------------------------------------------
     * @return The optional prefix to use when creating lock files.
     * ------------------------------------------------------------------------------------------
     */

    public function getLockFilePrefix()
    {
        return $this->lockFilePrefix;
    }  // getLockFilePrefix()

    /* ------------------------------------------------------------------------------------------
     * Set the directory where lock files are stored.
     *
     * @param $dir The lock directory.
     *
     * @return This object to support method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setLockFilePrefix($prefix)
    {
        $this->lockFilePrefix = $prefix;
        return $this;
    }  // setLockFilePrefix()

    /* ------------------------------------------------------------------------------------------
     * @return The value of the force flag
     * ------------------------------------------------------------------------------------------
     */

    public function isForce()
    {
        return $this->forceOperation;
    }  // isForce()

    /* ------------------------------------------------------------------------------------------
     * Set the force option.
     *
     * @param $flag A flag indicating force mode is enabled or disabled
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setForce($flag = true)
    {
        $origFlag = $flag;
        $flag = Utilities::filterBooleanVar($flag);
        if ( null === $flag ) {
            $msg = get_class($this) . ": Force flag is not a boolean: '$origFlag'";
            throw new Exception($msg);
        }
        $this->forceOperation = $flag;
        return $this;
    }  // setForce()

    /* ------------------------------------------------------------------------------------------
     * @return The value of the dryrun flag
     * ------------------------------------------------------------------------------------------
     */

    public function isDryrun()
    {
        return $this->dryrun;
    }  // isDryrun()

    /* ------------------------------------------------------------------------------------------
     * Set the force option.
     *
     * @param $flag A flag indicating dryrun mode is enabled or disabled
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setDryrun($flag = true)
    {
        $origFlag = $flag;
        $flag = Utilities::filterBooleanVar($flag);
        if ( null === $flag ) {
            $msg = get_class($this) . ": Dryrun flag is not a boolean: '$origFlag'";
            throw new Exception($msg);
        }
        $this->dryrun = $flag;
        return $this;
    }  // setDryrun()

    /* ------------------------------------------------------------------------------------------
     * @return The value of the verbose flag
     * ------------------------------------------------------------------------------------------
     */

    public function isVerbose()
    {
        return $this->verbose;
    }  // isVerbose()

    /* ------------------------------------------------------------------------------------------
     * Set the verbose option.
     *
     * @param $flag A flag indicating verbose mode is enabled or disabled
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setVerbose($flag = true)
    {
        $origFlag = $flag;
        $flag = Utilities::filterBooleanVar($flag);
        if ( null === $flag ) {
            $msg = get_class($this) . ": Verbose flag is not a boolean: '$origFlag'";
            throw new Exception($msg);
        }
        $this->verbose = $flag;
        return $this;
    }  // setVerbose()

    /* ------------------------------------------------------------------------------------------
     * Look up a resource code in and map it to a resource id.
     *
     * @param A resource code (e.g., OSG, TACC-STAMPEDE)
     *
     * @return The resource id corresponding to the specified code, or false if the code was not found
     *   in the map.
     * ------------------------------------------------------------------------------------------
     */

    public function getResourceIdFromCode($code)
    {
        return ( array_key_exists($code, $this->resourceCodeToIdMap) ? $this->resourceCodeToIdMap[$code] : false );
    }  // getResourceIdFromCode()

    /* ------------------------------------------------------------------------------------------
     * Set the mapping between resource codes and resource Ids.
     *
     * @param $map An associative array where the keys are resource codes and the values are the
     *   resource ids that those codes map to.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setResourceCodeToIdMap(array $map)
    {
        $this->resourceCodeToIdMap = $map;
        return $this;
    }  // setResourceCodeToIdMap()


    /* ------------------------------------------------------------------------------------------
     * Map an array of resource codes to their ids.
     *
     * @param $codes An array of esource codes (e.g., OSG, TACC-STAMPEDE)
     *
     * @return The list of resource ids corresponding to the specified codes.
     *
     * @throws Exception if a code does not have a mapping to an id.
     * ------------------------------------------------------------------------------------------
     */

    public function mapResourceCodesToIds(array $codes) {
        $resourceIdList = array();

        foreach ( $codes as $code ) {
            if ( false === ($resourceId = $this->getResourceIdFromCode($code)) ) {
                $msg = "Unknown include resource code: '$code'";
                $this->logAndThrowException($msg);
            } else {
                $resourceIdList[] = $resourceId;
            }
        }

        return $resourceIdList;
    }  // mapResourceCodesToIds()

    /* ------------------------------------------------------------------------------------------
     * @return The list of resource ids to that the ETL process will be restricted to. An empty
     *   array indicates all resources.
     * ------------------------------------------------------------------------------------------
     */

    public function getIncludeOnlyResourceCodes()
    {
        return $this->includeOnlyResourceCodes;
    }  // getIncludeOnlyResourceCodes()

    /* ------------------------------------------------------------------------------------------
     * Set the list of resource codes that the ETL process will be restricted to. NULL indicates all
     * resources.
     *
     * @param $codes A list of resource codes, a single resource id, or NULL.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setIncludeOnlyResourceCodes($codes)
    {
        if ( null === $codes ) {
            $this->includeOnlyResourceCodes = array();
        } else {
            $this->includeOnlyResourceCodes = ( ! is_array($codes) ? array($codes) : $codes );
        }
        return $this;
    }  // setIncludeOnlyResourceCodes()

    /* ------------------------------------------------------------------------------------------
     * @return The list of resource codes to exclude from the ETL process. An empty array indicates
     *   no exclusions.
     * ------------------------------------------------------------------------------------------
     */

    public function getExcludeResourceCodes()
    {
        return $this->excludeResourceCodes;
    }  // getExcludeResourceCodes()

    /* ------------------------------------------------------------------------------------------
     * Set the list of resource codes to exclude from the ETL process. NULL indicates no exclusions.
     *
     * @param $codes A list of resource codes, a single resource id, or NULL.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setExcludeResourceCodes($codes)
    {
        if ( null === $codes ) {
            $this->excludeResourceCodes = array();
        } else {
            $this->excludeResourceCodes = ( ! is_array($codes) ? array($codes) : $codes );
        }
        return $this;
    }  // setExcludeResourceCodes()

   /* ------------------------------------------------------------------------------------------
     * @return The list of action names
     * ------------------------------------------------------------------------------------------
     */

    public function getActionNames()
    {
        return $this->actionNames;
    }  // setActionNames()

    /* ------------------------------------------------------------------------------------------
     * Set the list of actions to execute.
     *
     * @param $names A list of action names or a single action name.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setActionNames($names)
    {
        $this->actionNames = ( ! is_array($names) ? array($names) : $names );
        return $this;
    }  // setActionNames()

    /* ------------------------------------------------------------------------------------------
     * @return The list of section names
     * ------------------------------------------------------------------------------------------
     */

    public function getSectionNames()
    {
        return $this->sectionNames;
    }  // setSectionNames()

    /* ------------------------------------------------------------------------------------------
     * Set the list of sections to process
     *
     * @param $names A list of section names or a single section name.
     *
     * @return This object for method chaining.
     * ------------------------------------------------------------------------------------------
     */

    public function setSectionNames($names)
    {
        $this->sectionNames = ( ! is_array($names) ? array($names) : $names );
        return $this;
    }  // setSectionNames()

    /* ==========================================================================================
     * Iterator implementation. Allow iteration over the list of ETL period chunks.
     * ==========================================================================================
     */

    public function current()
    {
        return current($this->etlPeriodChunkList);
    }

    public function key()
    {
        return key($this->etlPeriodChunkList);
    }

    public function next()
    {
        return next($this->etlPeriodChunkList);
    }

    public function rewind()
    {
        return reset($this->etlPeriodChunkList);
    }

    public function valid()
    {
        return false !== current($this->etlPeriodChunkList);
    }

    /* ------------------------------------------------------------------------------------------
     * Generate a list of ETL date intervals of the requested chunk size from the overall start and
     * end date, If the chunk size is NULL the list will be a single (start, end) tuple.
     *
     * NOTES:
     * - The list of chunks is generated from most most recent to oldest.
     * - The first chunk may contain an extra day
     * - If the chunks span daylight savings time you may notice a +/- 1 hour shift but won't miss
     *   any data
     * ------------------------------------------------------------------------------------------
     */

    private function generateEtlChunkList()
    {
        if ( null === $this->etlIntervalChunkSize ) {
            $this->etlPeriodChunkList = array(array($this->startDate, $this->endDate));
            return;
        }

        // Handle daylight savings time!!!!! Off my an hour
        $chunkList = array();
        $startTs = strtotime($this->startDate);
        $currentEndTs = strtotime($this->endDate);
        $secondsPerChunk = (60 * 60 * 24) * $this->etlIntervalChunkSize;

        while ( $currentEndTs > $startTs ) {
            $intervalEnd = date('Y-m-d H:i:s', $currentEndTs);
            // Decrement 1 year or the remainder if the period is less than 1 year
            $decrementSeconds = ( ($currentEndTs - $startTs) < $secondsPerChunk
                                  ? $currentEndTs - $startTs
                                  : $secondsPerChunk );
            $currentEndTs -= $decrementSeconds;
            // When printing the start date, round up 1 second unless this is the last interval.
            $intervalStart = date('Y-m-d H:i:s', $currentEndTs + ( $currentEndTs == $startTs ? 0 : 1 ) );
            $chunkList[] = array($intervalStart, $intervalEnd);
        }

        $this->etlPeriodChunkList = $chunkList;
    }  // generateEtlChunkList()

}  // class EtlOverseerOptions
