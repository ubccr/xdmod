<?php
/* ==========================================================================================
 * This is the overseer for the ingestion and aggregation process.  The overseer should:
 *
 *  1. read config file
 *  2. set up list of enabled ingestors and groups
 *  3. ingest using options
 *    a. verify data endpoints for selected ingestors (once)
 *    b. ingest selected ingestors. Ingestors may or may not block.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @data 2015-10-15
 * ==========================================================================================
 */

namespace ETL;

use \Exception;  // Base exception
use Log;
use ETL\Configuration\EtlConfiguration;

class EtlOverseer extends \CCR\Loggable implements iEtlOverseer
{
    // true if data endpoints have been verified
    private $verifiedDataEndpoints = false;

    // An associative array where they keys are fully qualified action names and the values
    // instantiated action objects. These are populated upon verification.
    private $standaloneActions = array();

    // A multi-dimensional associative array where they keys are section names and the values are
    // associative arrays where they keys are fully qualified action names and the values.  These are
    // populated upon verification.
    private $sectionActions = array();

    // Overseer options for this invocation
    private $etlOverseerOptions = null;

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::__construct()
     * ------------------------------------------------------------------------------------------
     */

    public function __construct(EtlOverseerOptions $options, Log $logger = null)
    {
        parent::__construct($logger);
        $this->etlOverseerOptions = $options;
    }  // __construct()

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::verifyDataEndpoints()
     * ------------------------------------------------------------------------------------------
     */

    public function verifyDataEndpoints(EtlConfiguration $etlConfig, $leaveConnected = false)
    {
        // Data endpoints should only be configured for enabled ingestors. Run through the list of
        // endpoints and attempt a connection to ensure they are active.

        $this->verifiedDataEndpoints = false;

        // Generate a list of data endpoints that are used by enabled actions so we can verify them
        // prior to executing the actions. The actions are not instantiated at this point because we
        // need to verify the endpoints first.

        $usedEndpointKeys = array();

        foreach ( $this->etlOverseerOptions->getActionNames() as $actionName ) {
            $options = $etlConfig->getActionOptions($actionName);
            if ( ! $options->enabled ) {
                continue;
            }
            if ( null !== $options->utility ) {
                $usedEndpointKeys[] = $options->utility;
            }
            if ( null !== $options->source ) {
                $usedEndpointKeys[] = $options->source;
            }
            if ( null !== $options->destination ) {
                $usedEndpointKeys[] = $options->destination;
            }
        }
        foreach ( $this->etlOverseerOptions->getSectionNames() as $sectionName ) {
            foreach ( $etlConfig->getSectionActionNames($sectionName) as $actionName ) {
                $options = $etlConfig->getActionOptions($actionName, $sectionName);
                if ( ! $options->enabled ) {
                    continue;
                }
                if ( null !== $options->utility ) {
                    $usedEndpointKeys[] = $options->utility;
                }
                if ( null !== $options->source ) {
                    $usedEndpointKeys[] = $options->source;
                }
                if ( null !== $options->destination ) {
                    $usedEndpointKeys[] = $options->destination;
                }
            }
        }

        $usedEndpointKeys = array_unique($usedEndpointKeys);

        $messages = array();

        foreach ( $usedEndpointKeys as $endpointKey ) {
            if ( false === ($endpoint = $etlConfig->getDataEndpoint($endpointKey)) ) {
                $this->logger->warning(
                    sprintf("Could not retrieve data endpoint object with key %s, skipping", $endpointKey)
                );
                continue;
            }

            try {
                $this->logger->info("Verifying endpoint: " . $endpoint);
                $endpoint->verify($this->etlOverseerOptions->isDryrun(), $leaveConnected);
            } catch ( Exception $e ) {
                $messages[] = $e->getMessage();
            }
        }  // foreach ( $config->getDataEndpoints() as $endpoint )

        if ( 0 != count($messages) ) {
            $msg = get_class($this) . ": Error verifying data endpoints:\n" . implode(",\n", $messages);
            throw new Exception($msg);
        }

        $this->verifiedDataEndpoints = true;

        return $this->verifiedDataEndpoints;

    }  // verifyDataEndpoints()

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::verifyActions()
     * ------------------------------------------------------------------------------------------
     */

    public function verifyActions(
        EtlConfiguration $etlConfig,
        array $actionNameList,
        array $actionObjectList = array(),
        $sectionName = null,
        $verifyDisabled = false
    ) {
        if ( 0 == count($actionNameList) ) {
            return $actionObjectList;
        }

        $messages = array();

        foreach ( $actionNameList as $actionName ) {

            try {

                // When processing single actions we won't have the section name passed (but it may
                // have been parsed above). We will have it when processing sections.

                if ( array_key_exists($actionName, $actionObjectList) &&
                     is_object($actionObjectList[$actionName]) &&
                     $actionObjectList[$actionName] instanceof iAction &&
                     $actionObjectList[$actionName]->isVerified() )
                {
                    continue;
                }

                $action = aAction::factory($etlConfig, $actionName, $this->logger);

                if ( ! $verifyDisabled && ! $action->isEnabled() ) {
                    $this->logger->info("Action disabled, skipping verification: " . $action);
                    continue;
                }

                $this->logger->info("Verifying action: " . $action);

                // If this action specifies resource codes we will need to load the mapping between codes
                // and ids. This is done on demand so it will not break bootstrapping processes.

                if ( isset($action->getOptions()->include_only_resource_codes) || isset($action->getOptions()->exclude_resource_codes) ) {
                    $this->queryResourceCodeToIdMap($etlConfig);
                }

                $action->initialize($this->etlOverseerOptions);
                $actionObjectList[$actionName] = $action;

            } catch ( Exception $e ) {
                $messages[] = sprintf("%s (%s)", $actionName, $e->getMessage());
            }
        }

        if ( 0 != count($messages) ) {
            throw new Exception(sprintf("Error verifying actions: %s", implode(", ", $messages)));
        }

        return $actionObjectList;

    }  // verifyActions()

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::verifySections()
     * ------------------------------------------------------------------------------------------
     */

    public function verifySections(
        EtlConfiguration $etlConfig,
        array $sectionNameList,
        array $sectionActionObjectList = array(),
        $verifyDisabled = false
    ) {
        if ( 0 == count($sectionNameList) ) {
            return $sectionActionObjectList;
        }

        $messages = array();

        foreach ( $sectionNameList as $sectionName ) {
            $actionNameList = $etlConfig->getSectionActionNames($sectionName);
            $actionObjectList = ( array_key_exists($sectionName, $sectionActionObjectList)
                                      ? $sectionActionObjectList[$sectionName]
                                      : array() );
            try {
                $sectionActionObjectList[$sectionName] = $this->verifyActions(
                    $etlConfig,
                    $actionNameList,
                    $actionObjectList,
                    $sectionName
                );
            } catch ( Exception $e ) {
                $messages[] = "('$sectionName': " . $e->getMessage() . ")";
            }
        }  // foreach ( $sectionNames as $sectionName )

        if ( 0 != count($messages) ) {
            $msg = "Error verifying sections: " . implode(", ", $messages);
            throw new Exception($msg);
        }

        return $sectionActionObjectList;

    }  // verifySections()

    /**
     * Query the database and set the mapping of resource codes to ids. This is needed to map the codes
     * specified on the command line and in action definitions to their database ids for queries executed
     * by the actions and is done one demand only when needed.
     *
     * @param EtlConfiguration $etlConfig The configuration that we are currently using
     */

    protected function queryResourceCodeToIdMap(EtlConfiguration $etlConfig)
    {
        // We don't need to query if the map is already set

        if ( 0 != count($this->etlOverseerOptions->getResourceCodeToIdMap()) ) {
            return;
        }

        $map = array();

        try {
            $sql = $this->etlOverseerOptions->getResourceCodeToIdMapSql();
            $utilityEndpoint = $etlConfig->getGlobalEndpoint('utility');
            $this->logger->debug(sprintf("Loading resource code to id map %s:\n%s", $utilityEndpoint, $sql));
            $result = $utilityEndpoint->getHandle()->query($sql);
        } catch (Exception $e) {
            $this->logAndThrowException(
                sprintf("%s%s%s", $e->getMessage(), PHP_EOL, $e->getTraceAsString())
            );
        }

        foreach ( $result as $row ) {
            $map[ $row['code'] ] = $row['id'];
        }

        $this->etlOverseerOptions->setResourceCodeToIdMap($map);
    }

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlConfiguration $etlConfig)
    {
        // If resource codes were specified on the command line we will need to load the map of
        // codes to database ids. If no codes were specified, actions may have specified them so we
        // will check prior to initializing actions as well. This is done on demand so it will not
        // break bootstrapping processes.

        if (
            count($this->etlOverseerOptions->getIncludeOnlyResourceCodes()) > 0
            || count($this->etlOverseerOptions->getExcludeResourceCodes()) > 0
        ) {
            $this->queryResourceCodeToIdMap($etlConfig);
        }

        // Pre-pend the default module name to any section names that are not already qualified.
        // This is done for backwards compatibility and cannot be done automatically for individual
        // actions.

        $defaultModuleName = $this->etlOverseerOptions->getDefaultModuleName();
        $this->etlOverseerOptions->setSectionNames(array_map(
            function ($name) use ($defaultModuleName) {
                $parts = explode('.', $name);
                if ( count($parts) < 2 ) {
                    return sprintf("%s.%s", $defaultModuleName, $name);
                } else {
                    return $name;
                }
            },
            $this->etlOverseerOptions->getSectionNames()
        ));

        // Verify requested section names before proceeding
        $sectionNames = $this->etlOverseerOptions->getSectionNames();

        if ( count($sectionNames) > 0 ) {
            $missing = array();

            foreach ( $sectionNames as $sectionName ) {
                if ( ! $etlConfig->sectionExists($sectionName) ) {
                    $missing[] = $sectionName;
                }
            }

            if ( count($missing) > 0 ) {
                $this->logAndThrowException(
                    sprintf("Unknown sections: %s", implode(", ", $missing))
                );
            }
        }

        // Verify connections to the data endpoints prior to verifying the actions. Action
        // initialization may need to connect to a data endpoint to obtain the handle so these need
        // to be done first.

        if ( ! $this->verifiedDataEndpoints ) {
            $leaveConnected = ( $this->etlOverseerOptions->isDryrun() ? false : true );
            $this->verifyDataEndpoints($etlConfig, $leaveConnected);
        }

        // Verify actions that were specified directly on the command line
        $actionNames = $this->etlOverseerOptions->getActionNames();
        if ( count($actionNames) > 0 ) {
            $this->standaloneActions = $this->verifyActions($etlConfig, $actionNames);
        }

        // Verify sections that were specified as part of a pipeline
        if ( count($sectionNames) > 0 ) {
            $this->sectionActions = $this->verifySections($etlConfig, $sectionNames);
        }

        // Generate a list of individual actions that will be executed so we can store them in the
        // lock file.

        $uniqueActionList = array_unique(
            array_reduce(
                $this->sectionActions,
                function ($carry, $item) {
                    return array_merge($carry, $item);
                },
                $this->standaloneActions
            )
        );
        $actionNameList = array_map(
            function ($obj) {
                return $obj->getName();
            },
            $uniqueActionList
        );

        // Generate a lock file

        $lockfile = new LockFile(
            $this->etlOverseerOptions->getLockDir(),
            $this->etlOverseerOptions->getLockFilePrefix(),
            $this->logger
        );

        $lockfile->lock($actionNameList);

        foreach ( $this->standaloneActions as $actionName => $actionObj ) {
            try {
                $this->_execute($actionName, $actionObj);
            } catch ( Exception $e ) {
                $lockfile->unlock();
                throw $e;
            }
        }

        foreach ( $this->sectionActions as $sectionName => $actionList ) {
            $this->logger->notice("Start processing section '$sectionName'");
            foreach ( $actionList as $actionName => $actionObj ) {
                try {
                    $this->_execute($actionName, $actionObj);
                } catch ( Exception $e ) {
                    $lockfile->unlock();
                    throw $e;
                }
            }
            $this->logger->notice("Finished processing section '$sectionName'");
        }

        $lockfile->unlock();

    }  // execute()

    /* ------------------------------------------------------------------------------------------
     * Internal method for performing ingestion, allowing the Overseer to make modifications if
     * necessary.
     *
     * @param $actionName The name of the action passed on the command line
     * @param $actionObj Action object implementing iAction
     * ------------------------------------------------------------------------------------------
     */

     // @codingStandardsIgnoreLine
    private function _execute($actionName, iAction $actionObj)
    {
        $this->logger->info(array(
                                'message'     => 'start',
                                'action_name' => $actionName,
                                'action'      => $actionObj,
                                'start_date'  => $this->etlOverseerOptions->getStartDate(),
                                'end_date'    => $this->etlOverseerOptions->getEndDate(),
                                ));

        // Execute the action using the overseer options including date, resource ids, etc.  If this
        // action should halt the ETL process on an exception re-throw the exception, otherwise log it
        // and continue.

        try {
            $actionObj->execute($this->etlOverseerOptions);
        } catch ( Exception $e ) {
            if ( isset($actionObj->getOptions()->stop_on_exception)
                 && true == $actionObj->getOptions()->stop_on_exception ) {
                $msg = "Stopping ETL due to exception in " . $actionObj;
                $this->logger->warning($msg);
                throw $e;
            } else {
                $msg = "Exception thrown by " . $actionObj . " but stop_on_execution=false, continuing";
                $this->logger->warning($msg);
            }
        }

        $this->logger->info(array(
                                'message'    => 'end',
                                'action_name' => $actionName,
                                'action'     => $actionObj
                                ));
    }  // _execute()
}  // class EtlOverseer
