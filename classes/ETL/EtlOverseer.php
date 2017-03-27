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

class EtlOverseer extends Loggable implements iEtlOverseer
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

        // This is a quick hack to only verify endpoints used by the actions that we are
        // processing. The actions are not instantiated at this point because we need to verify the
        // endpoints first.

        $usedEndpointKeys = array();

        foreach ( $this->etlOverseerOptions->getActionNames() as $actionName ) {
            list($sectionName, $actionName) = $this->parseSectionFromActionName($actionName);
            $options = $etlConfig->getActionOptions($actionName, $sectionName);
            $usedEndpointKeys[] = $options->utility;
            $usedEndpointKeys[] = $options->source;
            $usedEndpointKeys[] = $options->destination;
        }
        foreach ( $this->etlOverseerOptions->getSectionNames() as $sectionName ) {
            foreach ( $etlConfig->getSectionActionNames($sectionName) as $actionName ) {
                $options = $etlConfig->getActionOptions($actionName, $sectionName);
                $usedEndpointKeys[] = $options->utility;
                $usedEndpointKeys[] = $options->source;
                $usedEndpointKeys[] = $options->destination;
            }
        }

        $usedEndpointKeys = array_unique($usedEndpointKeys);

        $messages = array();

        foreach ( $usedEndpointKeys as $endpointKey ) {
            if ( false === ($endpoint = $etlConfig->getDataEndpoint($endpointKey)) ) {
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
     * Verify that a single action is properly configured.
     *
     * @param $etlConfig An EtlConfiguration object containing the parsed ETL configuration
     * @param $options An action options object
     *
     * @return The instantiated and verified action object
     *
     * @throws Exception if there was an error validating any action.
     * ------------------------------------------------------------------------------------------
     */

    private function verifyAction(EtlConfiguration $etlConfig, $options)
    {
        if ( ! $options instanceof aOptions ) {
            $msg = get_class($this) . ": Options is not an instance of aOptions";
            throw new Exception($msg);
        }

        $action = forward_static_call(array($options->factory, "factory"), $options, $etlConfig, $this->logger);
        $this->logger->info("Verifying action: " . $action);

        // $action->verify($this->etlOverseerOptions);
        $action->initialize($this->etlOverseerOptions);

        return $action;

    }  // verifyAction()

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

                // Keep the fully qualified action name (section:action) so it can be used
                // internally by the overseer in the case where multiple actions with the same name
                // are invoked in different sections. This only applies to individually specified
                // actions.

                list($parsedSectionName, $unqualifiedActionName) = $this->parseSectionFromActionName($actionName);

                // When processing single actions we won't have the section name passed (but it may
                // have been parsed above). We will have it when processing sections.

                $sectionName = ( null !== $sectionName ? $sectionName : $parsedSectionName );

                if ( array_key_exists($actionName, $actionObjectList) &&
                     is_object($actionObjectList[$actionName]) &&
                     $actionObjectList[$actionName] instanceof iAction &&
                     $actionObjectList[$actionName]->isVerified() )
                {
                    continue;
                }

                $options = $etlConfig->getActionOptions($unqualifiedActionName, $sectionName);

                if ( ! $verifyDisabled && ! $options->enabled ) {
                    continue;
                }

                $actionObjectList[$actionName] = $this->verifyAction($etlConfig, $options);
            } catch ( Exception $e ) {
                $messages[] = "(" . $e->getMessage() . ")";
            }
        }  // foreach ( $actionNameList as $actionName )

        if ( 0 != count($messages) ) {
            $msg = "Error verifying actions: " . implode(", ", $messages);
            throw new Exception($msg);
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

    /* ------------------------------------------------------------------------------------------
     * @see iEtlOverseer::execute()
     * ------------------------------------------------------------------------------------------
     */

    public function execute(EtlConfiguration $etlConfig)
    {
        // Verify connections to the data endpoints prior to verifying the actions. Action
        // initialization may need to connect to a data endpoint to obtain the handle so these need
        // to be done first.

        try {
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
            $sectionNames = $this->etlOverseerOptions->getSectionNames();
            if ( count($sectionNames) > 0 ) {
                $this->sectionActions = $this->verifySections($etlConfig, $sectionNames);
            }
        } catch ( Exception $e ) {
            $msg = get_class($this) . ": Verification error: " . $e->getMessage();
            $this->logger->err($msg);
            return;
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

    /* ------------------------------------------------------------------------------------------
     * Parse the optional section name from the action name allowing individual actions specific to
     * a section to be referenced from the command line. For example, osg:OsgJobsIngestor referrs to
     * the "OsgJobsIngestor" action in the "osg" section.
     *
     * @param $actionName An action name, optionally prefixed by a section name and a colon (:)
     *
     * @return An array containing the section name (null if not specified) and action name.
     * ------------------------------------------------------------------------------------------
     */

    private function parseSectionFromActionName($actionName) {
        $sectionName = null;
        if ( $position = strpos($actionName, ":") ) {
            $parts = explode(":", $actionName);
            if ( count($parts) > 2 ) {
                $msg = "Action name '$actionName' cannot contain more than one ':'";
                throw new Exception($msg);
            }
            list($sectionName, $actionName) = $parts;
        }

        return array($sectionName, $actionName);
    }  // parseSectionFromActionName()
}  // class EtlOverseer
