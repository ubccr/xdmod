<?php

namespace DataWarehouse;
use Exception;

require_once("Log.php");

class Search extends \aRestAction
{
    protected $logger = NULL;
    private $_params = NULL;
    private $_requestmethod = NULL;
    private $_supported_types = array(
            \DataWarehouse\Query\RawQueryTypes::ACCOUNTING =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::ACCOUNTING,
                "dtype" => "infoid",
                "text" => "Accounting data",
                "url" => "/rest/datawarehouse/search/jobs/accounting",
                "type" => "keyvaluedata",
                "leaf" => true
            ),
            \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::BATCH_SCRIPT,
                "dtype" => "infoid",
                "text" => "Job script",
                "url" => "/rest/datawarehouse/search/jobs/jobscript",
                "type" => "utf8-text",
                "leaf" => true
            ),
            \DataWarehouse\Query\RawQueryTypes::PEERS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::PEERS,
                "dtype" => "infoid",
                "text" => "Peers",
                "leaf" => false
            ),
            \DataWarehouse\Query\RawQueryTypes::EXECUTABLE =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::EXECUTABLE,
                "dtype" => "infoid",
                "text" => "Executable information",
                "url" => "/rest/datawarehouse/search/jobs/executable",
                "type" => "nested",
                "leaf" => true ),
            \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::NORMALIZED_METRICS,
                "dtype" => "infoid",
                "text" => "Summary metrics",
                "url" => "/rest/datawarehouse/search/jobs/metrics",
                "type" => "metrics",
                "leaf" => true
            ),
            \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::DETAILED_METRICS,
                "dtype" => "infoid",
                "text" => "Detailed metrics",
                "url" => "/rest/datawarehouse/search/jobs/detailedmetrics",
                "type" => "detailedmetrics",
                "leaf" => true
            ),
            \DataWarehouse\Query\RawQueryTypes::ANALYTICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::ANALYTICS,
                "dtype" => "infoid",
                "text" => "Job analytics",
                "url" => "/rest/datawarehouse/search/jobs/analytics",
                "type" => "analytics",
                "hidden" => true,
                "leaf" => true
            ),
            \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS =>
            array(
                "infoid" => \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS,
                "dtype" => "infoid",
                "text" => "Timeseries",
                "leaf" => false
            ),
        );

    // --------------------------------------------------------------------------------
    // @see aRestAction::__call()
    // --------------------------------------------------------------------------------

    public function __call($target, $arguments)
    {
        // Verify that the target method exists and call it.

        $method = $target . ucfirst($this->_operation);

        $this->_requestmethod = "GET";
        if( isset($_SERVER['REQUEST_METHOD']) ) {
            $this->_requestmethod = strtoupper($_SERVER['REQUEST_METHOD'] );
        }

        if (!method_exists($this, $method)) {

            if ($this->_operation == 'Help') {
                // The help method for this action does not exist, so attempt to generate a response
                // using that action's Documentation() method

                $documentationMethod = $target . 'Documentation';

                if (!method_exists($this, $documentationMethod)) {
                    throw new Exception("Help cannot be found for action '$target'");
                }

                return $this->$documentationMethod()->getRESTResponse();

            } else {
                throw new Exception("Unknown action '$target' in category '" . strtolower(__CLASS__) . "'");
            }

        }  // if ( ! method_exists($this, $method) )

        return $this->$method($arguments);

    } // __call()

    // --------------------------------------------------------------------------------

    public function __construct($request)
    {
        parent::__construct($request);

        // Initialize the logger

        $this->_params = $this->_parseRestArguments("");
        $verbose = (isset($this->_params['debug']) && $this->_params['debug']);
        $maxLogLevel = ($verbose ? PEAR_LOG_DEBUG : PEAR_LOG_INFO);
        $logConf = array('mode' => 0644);
        $logfile = LOG_DIR . "/" . \xd_utilities\getConfiguration('datawarehouse', 'rest_logfile');
        $this->logger = \Log::factory('file', $logfile, 'Search', $logConf, $maxLogLevel);

    }  // __construct

    // --------------------------------------------------------------------------------
    // @see aRestAction::factory()
    // --------------------------------------------------------------------------------

    public static function factory($request)
    {
        return new self($request);
    }

    /** @return the parameter value (if it is a string matching /[0-9]+/ or null otherwise
     *
     * */
    private function getIntParam($name, $mandatory = false) {
        if( isset($this->_params[$name]) ) {
            $value = $this->_params[$name];
            if( ! ctype_digit($value) ) {
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Invalid value for $name parameter. Must be an int.");
            }
            return $value;
        }
        // else the parameter is absent
        if( $mandatory ) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException("Required parameter $name is missing.");
        }
        return null;
    }

    /**
     * Extract the named parameter from the request and remove it from the list of params
     * @param mandatory - boolean whether to raise an exception if the parameter is missing
     * @return the value of the named parameter or null if absent and not mandatory
     */
    private function getStringParam($name, $mandatory) {
        if( isset($this->_params[$name]) ) {
            return $this->_params[$name];
        }
        // else the parameter is absent
        if( $mandatory ) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException("Required parameter $name is missing.");
        }
        return null;
    }

    private function getRealmParam($mandatory) {
        $realm = $this->getStringParam('realm', $mandatory);
        if($realm !== null) {
            if($realm != "SUPREMM") {
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Unsupported realm " . $realm);
            }
        }
        return $realm;
    }

    private function historyAction()
    {
        $user = $this->_authenticateUser();

        $nodeid = $this->getIntParam('nodeid');
        $tsid = $this->getStringParam('tsid', false);
        $infoid  = $this->getIntParam('infoid');
        $jobid = $this->getIntParam('jobid');
        $id    = $this->getIntParam('recordid');
        $realm = $this->getRealmParam(false);

        if( $nodeid !== null &&  $tsid !== null && $infoid !== null && $jobid !== null && $id !== null && $realm !== null ) {
            if( $infoid != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS ) {
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Node $infoid is a leaf");
            }
            return $this->processJobNodeTimeseries($user, $realm, $jobid, $tsid, $nodeid);
        }
        else if( $tsid !== null && $infoid !== null && $jobid !== null && $id !== null && $realm !== null ) {
            if( $infoid != \DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS ) {
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Node $infoid is a leaf");
            }
            return $this->processJobTimeseriesInfo($user, $realm, $jobid, $tsid);
        }
        else if( $infoid !== null && $jobid !== null  && $id !== null && $realm !== null) {
            return $this->processJobInfo($user, $realm, $jobid, $infoid);
        }
        else if( $jobid !== null && $id !== null && $realm !== null ) {
            return $this->processJobByJobid($user, $realm, $id, $jobid);
        }
        else if( $id !== null && $realm !== null ) {
            return $this->processHistoryRecord($user, $realm, $id);
        }
        else if( $realm !== null ) {
            return $this->processHistoryTopLevel($user, $realm);
        }
        else {
            return $this->processHistory($user);
        }
    }

    private function processHistoryRecord($user, $realm, $id)
    {
        $searchhistory = $this->getUserStorage($user, $realm);

        switch($this->_requestmethod) {
        case "GET":
            $record = $searchhistory->getById($id);
            foreach($record['results'] as &$r) {
                $r['dtype'] = "jobid";
            }
            return $record;
            break;
        case "DELETE":
            $ndel = $searchhistory->delById($id);
            return array( "total" => $ndel );
            break;
        case "PUT":
        case "POST":
            if(!isset($this->_params['data']) ) {
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Missing data parameter");
            }
            $data = json_decode($this->_params['data'], true);
            $result = $searchhistory->upsert($id, $data);
            if( $result === null ) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException("Request would exceed storage limits");
            }
            return array( "results" => $result );
            break;
        default:
            throw new \DataWarehouse\Query\Exceptions\BadRequestException("HTTP method " . $this->_requestmethod . " is not supported for this endpoint");
        }
    }

    private function processHistoryTopLevel($user, $realm)
    {


        $searchhistory = $this->getUserStorage($user, $realm);
        switch($this->_requestmethod) {
            case "GET":
                $history = $searchhistory->get();
                $results = array();
                foreach($history as $h) {
                    $results[] = array( "text" => $h['text'], "dtype" => "recordid", "recordid" => $h['recordid'] );
                }
                return array( "results" => $results );
                break;
            case "POST":
                if(!isset($this->_params['data']) ) {
                    throw new \DataWarehouse\Query\Exceptions\BadRequestException("Missing data parameter");
                }
                $data = json_decode($this->_params['data'], true);

                // Validate the data
                if( isset($data['searchterms']) && is_array($data['searchterms']) && isset($data['results']) && is_array($data['results']) && isset($data['text']) ) {
                    $data['dtype'] = "recordid";
                    $result = $searchhistory->insert($data);
                    return array( "results" => $result );
                } else {
                    throw new \DataWarehouse\Query\Exceptions\BadRequestException("Incorrect data format");
                }
                break;
            case "DELETE":
                $searchhistory->del();
                return array( "success" => true );
            default:
                throw new \DataWarehouse\Query\Exceptions\BadRequestException();
        }
    }

    private function processHistory($user)
    {
        switch($this->_requestmethod) {
            case "GET":
                return array( "results" => array( array( "dtype" => "realm", "realm" => "SUPREMM", "text" => "SUPREMM" ) ) );
                break;
            default:
                throw new \DataWarehouse\Query\Exceptions\BadRequestException($this->_requestmethod . " not supported for tree root");
        }
    }

    private function getUserStorage($user, $realm)
    {
        return new \UserStorage($user, "searchhistory-" . $realm);
    }

    private function infoAction()
    {
        $result = array();

        $user = $this->_authenticateUser();
        $realm = $this->getStringParam('realm', true);
        $title = $this->getStringParam('title', true);

        $storage = $this->getUserStorage($user, $realm);

        $data = $storage->get();
        foreach ($data as $entry) {
            $text = isset($entry['text']) ? $entry['text'] : null;
            if ($text == $title) {
                $result = $entry;
                break;
            }
        }
        return array("data" => $result);
    }

    private function infoDocumentation()
    {
        $doc = new \RestDocumentation();
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }


    private function processJobByJobid($user, $realm, $id, $jobid)
    {
        $searchhistory = $this->getUserStorage($user, $realm);
        $searchrecord = $searchhistory->getById($id);

        // TODO should we check to confirm that the jobid was in the search results?

        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();
        $jobmeta = $info->getJobMetadata($user, $jobid);

        $retval = array_intersect_key($this->_supported_types, $jobmeta);

        return array("results"=> array_values($retval) );
    }

    private function processJobInfo($user, $realm, $jobid, $infoid)
    {
        switch($infoid) {
            case "".\DataWarehouse\Query\RawQueryTypes::TIMESERIES_METRICS:
                $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
                $info = new $infoclass();

                $result = array();
                foreach($info->getJobTimeseriesMetaData($user, $jobid) as $tsid) {
                    $tsid['url'] = "/rest/datawarehouse/search/jobs/timeseries";
                    $tsid['type'] = "timeseries";
                    $tsid['dtype'] = "tsid";
                    $result[] = $tsid;
                }
                return array("results" => $result );
                break;
            case "".\DataWarehouse\Query\RawQueryTypes::PEERS:
                $dataset = $this->getJobDataset($user, $realm, $jobid, "peers");
                $result = array();
                foreach($dataset->getResults() as $jobpeer)
                {
                    $result[] = array( 
                        "text" => $jobpeer['resource'] . '-' . $jobpeer['local_job_id'],
                        "dtype" => "peerid",
                        "peerid" => $jobpeer['jobid'],
                        "qtip" => "Job Owner: " . $jobpeer['name'],
                        "leaf" => true );
                }
                return array("results" => $result );
                break;
            default:
                throw new \DataWarehouse\Query\Exceptions\BadRequestException("Node is a leaf");
        }
    }

    private function processJobNodeTimeseries($user, $realm, $jobid, $tsid, $nodeid)
    {
        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();

        $result = array();
        foreach($info->getJobTimeseriesMetricNodeMeta($user, $jobid, $tsid, $nodeid) as $cpu) {
            $cpu['url'] = "/rest/datawarehouse/search/jobs/timeseries";
            $cpu['type'] = "timeseries";
            $cpu['dtype'] = "cpuid";
            $result[] = $cpu;
        }

        return array("results" => $result);
    }

    private function processJobTimeseriesInfo($user, $realm, $jobid, $tsid)
    {
        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();

        $result = array();
        foreach($info->getJobTimeseriesMetricMeta($user, $jobid, $tsid) as $node) {
            $node['url'] = "/rest/datawarehouse/search/jobs/timeseries";
            $node['type'] = "timeseries";
            $node['dtype'] = "nodeid";
            $result[] = $node;
        }

        return array("results" => $result);
    }

    private function historyDocumentation()
    {
	    $doc = new \RestDocumentation();
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }

    private function jobsAction()
    {
        $user = $this->_authenticateUser();
        $realm = $this->getRealmParam(true);

        $action = $this->_request->getActionArguments();
        if($action == "" ) {
            return $this->processJobSearch($user, $realm);
        }
        else {
            $jobid = $this->getIntParam("jobid", true);

            switch($action) {
                case "accounting":
                case "jobscript":
                case "analysis":
                case "metrics":
                case "analytics":
                    return array("data" => $this->getJobDataset($user, $realm, $jobid, $action)->export() );
                    break;
                case "executable":
                    return $this->getJobExecutable($user, $realm, $jobid);
                    break;
                case "detailedmetrics":
                    return $this->getJobSummary($user, $realm, $jobid);
                    break;
                case "timeseries":
                    $tsid = $this->getStringParam("tsid", true);
                    $nodeid =  $this->getIntParam("nodeid");
                    $cpuid =  $this->getIntParam("cpuid");
                    return $this->getJobTimeseriesDataset($user, $realm, $jobid, $tsid, $nodeid, $cpuid);
                    break;
                default:
                    throw new \DataWarehouse\Query\Exceptions\BadRequestException("Unsupported action $action");
                    break;
            }
        }
    }


    function is_assoc($var)
    {
        return is_array($var) && array_diff_key($var,array_keys(array_keys($var)));
    }

    /*
     * Convert a php associative array to the format used by the extjs tree implementation
     */
    private function arraytostore($phparray){

        $result = array();

        foreach($phparray as $key => $value) {
            if( $this->is_assoc($value) ) {
                // Next level down
                $result[] = array("key" => $key, "value" => "", "expanded" => true, "children" => $this->arraytostore($value) );
            } else {
                $result[] = array("key" => $key, "value" => $value, "leaf" => true);
            }
        }

        return $result;
    }

    private function getJobExecutable($user, $realm, $jobid)
    {
        $queryclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $queryclass();

        $jobsummary = $query->getJobSummary($user, $jobid);

        if( !isset($jobsummary['lariat']) ){
            return array("success" => false, "message" => "Executable information unavailable for $realm $jobid");
        }

        // Note that we need to return the data in this format so that the rest stack
        // does not try to modify the output.
        return array(
            "httpCode" => 200,
            "headers" => array("Content-Type" => "application/json"),
            "results" => json_encode($this->arraytostore($jobsummary['lariat']))
        );
    }

    private function getJobSummary($user, $realm, $jobid)
    {
        $queryclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $query = new $queryclass();

        $jobsummary = $query->getJobSummary($user, $jobid);

        $result = array();

        // Really this should be a recursive function!
        foreach( $jobsummary as $key => $val )
        {
            $name = "$key";
            if( is_array($val) )
            {
                if ( array_key_exists('avg', $val) )
                {
                    $result[] = array_merge( array("name" => $name, "leaf" => true), $val);
                }
                else
                {
                    $l1data = array( "name" => $name, "avg" => "", "expanded" => "true",  "children" => array() );
                    foreach( $val as $subkey => $subval )
                    {
                        $subName = "$subkey";
                        if( is_array($subval) )
                        {
                            if ( array_key_exists('avg', $subval) )
                            {
                                $l1data['children'][] = array_merge( array("name" => $subName, "leaf" => true), $subval);
                            }
                            else
                            {
                                $l2data = array("name" => $subName, "avg" => "", "expanded" => "true", "children" => array());

                                foreach( $subval as $subsubkey => $subsubval )
                                {
                                    $subSubName = "$subsubkey";
                                    if( is_array($subsubval) ) {
                                        if ( array_key_exists('avg', $subsubval) )
                                        {
                                            $l2data['children'][] = array_merge( array("name" => $subSubName, "leaf" => true), $subsubval);
                                        }
                                    }
                                }

                                if( count($l2data['children']) > 0 ) {
                                    $l1data['children'][] =  $l2data;
                                }
                            }
                        }
                    }
                    if( count($l1data['children']) > 0 ) {
                        $result[] = $l1data;
                    }
                }
            }
        }
        // Note that we need to return the data in this format so that the rest stack
        // does not try to modify the output.
        return array(
            "httpCode" => 200,
            "headers" => array("Content-Type" => "application/json"),
            "results" => json_encode($result)
        );
    }

    private function getJobDataset($user, $realm, $jobid, $stats)
    {
        $params = array(new \DataWarehouse\Query\Model\Parameter("_id", "=", $jobid));

        $queryclass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $query = new $queryclass($params, $stats);

        $allroles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allroles);

        $dataset = new \DataWarehouse\Data\RawDataset($query);

        if( ! $dataset->hasResults() )
        {
            // No data returned for the query. This could be because the roleParameters
            // caused the data to be filtered. In this case we will return access-denied.
            // need to rerun the query without the role params to see if any results come back.
            // note the data for the priviledged query is not returned to the user.

            $priv_query = new $queryclass($params, $stats);
            $results = $priv_query->execute(1);

            if( $results['count'] != 0 ) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException();
            }
        }

        return $dataset;
    }

    private function getJobTimeseriesDataset($user, $realm, $jobid, $tsid, $nodeid, $cpuid) {

        $infoclass = "\\DataWarehouse\\Query\\$realm\\JobMetadata";
        $info = new $infoclass();
        $results = $info->getJobTimeseriesData($user, $jobid, $tsid, $nodeid, $cpuid);

        return array( "data" => array( 0 => $results ) );
    }

    private function processJobSearch($user, $realm)
    {
        $searchparams = json_decode($this->getStringParam("params", true), true);
        if($searchparams === NULL) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException("params: " . json_last_error_msg() );
        }

        if( isset($searchparams['local_job_id']) && isset($searchparams['resource_id']) ) {
            return $this->processJobSearchByLocalJobId($user, $realm, $searchparams['local_job_id'], $searchparams['resource_id']);
        }
        return $this->processJobSearchByDimension($user, $realm, $searchparams);
    }

    private function processJobSearchByLocalJobId($user, $realm, $local_job_id, $resource_id)
    {
        if( (! ctype_digit($local_job_id) ) || (! ctype_digit($resource_id) ) ) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException("Invalid resource_id local_job_id");
        }
        $params = array();
        $params[] = new \DataWarehouse\Query\Model\Parameter("resource_id", "=", $resource_id);
        $params[] = new \DataWarehouse\Query\Model\Parameter("local_job_id", "=", $local_job_id);

        $queryclass = "\\DataWarehouse\\Query\\$realm\\JobDataset";
        $query = new $queryclass($params, "brief");

        $allroles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allroles);

        $dataset = new \DataWarehouse\Data\RawDataset($query);

        $results = array();
        foreach ($dataset->getResults() as $res) {
            $res['text'] = $res['resource'] . "-" . $res['local_job_id'];
            $res['dtype'] = "jobid";
            array_push($results, $res);
        }

        if( ! $dataset->hasResults() )
        {
            // No data returned for the query. This could be because the roleParameters
            // caused the data to be filtered. In this case we will return access-denied.
            // need to rerun the query without the role params to see if any results come back.
            // note the data for the priviledged query is not returned to the user.

            $priv_query = new $queryclass($params, "brief");
            $priv_results = $priv_query->execute(1);

            if( $priv_results['count'] != 0 ) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException();
            }
        }

        return array( "results" => $results, "totalCount" => count($results) );
    }

    private function processJobSearchByDimension($user, $realm, $params)
    {
        $start_date = $this->getStringParam("start_date", True);
        $end_date = $this->getStringParam("end_date", True);

        $role = $user->getActiveRole();
        $realms = $role->getAllQueryRealms("tg_usage");

        $offset = $this->getIntParam("start", true);
        $limit = $this->getIntParam("limit", true);

        $allowedSearchDims = array_keys($realms[$realm]);

        $searchterms = array_intersect_key(array_keys($params), $allowedSearchDims);

        if( count($searchterms) < 1 ) {
            throw new \DataWarehouse\Query\Exceptions\BadRequestException();
        }

        $queryclassname = "\\DataWarehouse\\Query\\$realm\\RawData";
        $query = new $queryclassname( "day", $start_date, $end_date, null, "", array(), "tg_usage", array(), false);

        $allroles = $user->getAllRoles();
        $query->setMultipleRoleParameters($allroles);

        $query->setRoleParameters($params);

        $dataset = new \DataWarehouse\Data\SimpleDataset($query);

        $results = array();
        foreach ($dataset->getResults($limit, $offset) as $res) {
            $res['text'] = $res['resource'] . "-" . $res['local_job_id'];
            $res['dtype'] = "jobid";
            array_push($results, $res);
        }

        $totalCount = $dataset->getTotalPossibleCount();

        if( $totalCount == 0 )
        {
            // No data returned for the query. This could be because the roleParameters
            // caused the data to be filtered. In this case we will return access-denied.
            // need to rerun the query without the role params to see if any results come back.
            // note the data for the priviledged query is not returned to the user.

            $privquery = new $queryclassname( "day", $start_date, $end_date, null, "", array(), "tg_usage", array(), false);
            $privquery->setRoleParameters($params);

            $dataset = new \DataWarehouse\Data\SimpleDataset($privquery);
            $privresults = $dataset->getResults();

            if( count($privresults) != 0 ) {
                throw new \DataWarehouse\Query\Exceptions\AccessDeniedException();
            }
        }

        return array("results" => $results, "totalCount" => $totalCount );
    }

    private function jobsDocumentation()
    {
	    $doc = new \RestDocumentation();
        $doc->setAuthenticationRequirement(true);
        return $doc;
    }
}

?>
