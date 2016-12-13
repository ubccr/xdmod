<?php
// ----------------------------------------------------------------------------------------------------
// Return the most current version of XDMoD. This is useful for providing a "check for updates"
// service to compare the installed version to the current version.
// ----------------------------------------------------------------------------------------------------

namespace Version;
use \Exception;
use xd_versioning;
use \CCR\DB;

require_once("Log.php");

class XDMoDVersion extends \aRestAction
{

// --------------------------------------------------------------------------------
// @see aRestAction::__call()
// --------------------------------------------------------------------------------

  public function __call($target, $arguments)
  {
    // Verify that the target method exists and call it.

    $method = $target . ucfirst($this->_operation);

    if ( ! method_exists($this, $method) )
    {

      if ($this->_operation == 'Help')
      {
        // The help method for this action does not exist, so attempt to generate a response
        // using that action's Documentation() method.

        $documentationMethod = $target . 'Documentation';

        if ( ! method_exists($this, $documentationMethod) )
        {
          throw new Exception("Help cannot be found for action '$target'");
        }

        return $this->$documentationMethod()->getRESTResponse();

      }
      else
      {
        throw new Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
      }

    }  // if ( ! method_exists($this, $method) )

    return $this->$method($arguments);

  } // __call()

  // --------------------------------------------------------------------------------

  public function __construct($request)
  {
    parent::__construct($request);

    // Initialize the logger

    $params = $this->_parseRestArguments("");
    $verbose = ( isset($params['debug']) && $params['debug'] );
    $maxLogLevel = ( $verbose ? PEAR_LOG_DEBUG : PEAR_LOG_INFO );
    $logConf = array('mode' => 0644);
    $logfile = LOG_DIR . "/" . \xd_utilities\getConfiguration('general', 'rest_general_logfile');
    $this->logger = \Log::factory('file', $logfile, 'VERSION', $logConf, $maxLogLevel);

  }  // __construct

// --------------------------------------------------------------------------------
// @see aRestAction::factory()
// --------------------------------------------------------------------------------

  public static function factory($request)
  {
    return new XDMoDVersion($request);
  }

  // --------------------------------------------------------------------------------
  // Return the current version of XDMoD as defined in the configuration file
  // --------------------------------------------------------------------------------

  private function currentAction()
  {
    $params = $this->_parseRestArguments("");

    // The Open XDMoD version may be different than the XSEDE XDMoD
    // version.  The production XSEDE XDMoD environment must be updated
    // when a new version of Open XDMoD is released.
    if (isset($params['open-xdmod']) && $params['open-xdmod']) {
      $version = OPEN_XDMOD_VERSION;
    } else {
      $version = xd_versioning\getPortalVersion(true);
    }

    $queryParams = array(':ip' => null,
                         ':name' => null,
                         ':email' => null,
                         ':organization' => null,
                         ':current_version' => null,
                         ':all_params' => null);

    $logStr = "ip=" . $_SERVER['REMOTE_ADDR'];
    $queryParams[':ip'] = $_SERVER['REMOTE_ADDR'];

    foreach ($params as $k => $v) {
      $logStr .= "; $k=$v";
      $pKey = ":" . $k;
      if ( array_key_exists($pKey, $queryParams) ) $queryParams[$pKey] = urldecode($v);
    }


    $this->logger->info($logStr);
    $queryParams[':all_params'] = implode("&", explode("; ", $logStr));

    // Explicitly store the ip, name, email, organization, and current version (null if not present)
    // as well as all query parameters as a text blob for later processing.

    $query = "insert into VersionCheck (entry_date, ip_address, name, email, organization, current_version, all_params)
 values (now(), :ip, :name, :email, :organization, :current_version, :all_params)";

    try {
      $pdo = DB::factory('database');
      $res = $pdo->insert($query, $queryParams);
    } catch(\PDOException $e) {
      $this->logger->info("Error inserting version check: " . print_r($e, 1));
    }

    return array('success' => true,
                 'results' => $version);

  }  // currentVersionAction()

  // --------------------------------------------------------------------------------

  private function currentDocumentation()
  {
    $doc = new \RestDocumentation();
    $doc->setDescription("Return the most recent version of XDMoD. This is useful for checking if a new version is available");
    $doc->setAuthenticationRequirement(false);
    // $doc->addReturnElement("current", "The most recent version of XDMoD");

    return $doc;

  }  // currentVersionDocumentation()

  // --------------------------------------------------------------------------------
  // Generate data for a report on who has installed Open XDMoD.  The install script pings the
  // "current" rest service and an entry is logged in the database.  This endpoint will generate
  // data that can be used for a report.
  // --------------------------------------------------------------------------------

  private function reportAction()
  {
    $params = $this->_parseRestArguments("");

/*
    $user = $this->_authenticateUser();
    $role = $user->getMostPrivilegedRole();
    $role_parameters = $user->getMostPrivilegedRole()->getParameters();
*/

    $query = "select ip_address,
date(max(entry_date)) as entry_date,
group_concat(distinct if('' = name, null, name) order by name asc separator ';') as name,
group_concat(distinct if('' = email, null, email)) as email,
group_concat(distinct if('' = organization, null, organization)) as organization,
group_concat(distinct if('' = current_version, null, current_version)) as version
from moddb.VersionCheck
group by ip_address
order by date(max(entry_date)) asc, ip_address";

    $results = array();
    try {
      $db = DB::factory('database');
      $results = $db->query($query);
    } catch(\PDOException $e) {
      $this->logger->info("Error inserting version check: " . print_r($e, 1));
    }

    return array('success' => true,
                 'results' => $results);

  }  // currentVersionAction()

  // --------------------------------------------------------------------------------

  private function reportDocumentation()
  {
    $doc = new \RestDocumentation();
    $doc->setDescription("Return data for a report on sites that have installed Open XDMoD and have pinged the current version endpoint.  Note that the IP address is the only information that is required to be collected and other fields may be empty. If multiple pings were made in a single day they are grouped into one report entry.");
    $doc->setAuthenticationRequirement(true);
    $doc->addReturnElement("ip_address", "Reporting IP address");
    $doc->addReturnElement("entry_date", "Date that the ping was made");
    $doc->addReturnElement("name", "Name supplied by admin");
    $doc->addReturnElement("email", "Email address supplied by admin");
    $doc->addReturnElement("organization", "Organization supplied by admin");

    return $doc;

  }  // currentVersionDocumentation()

}  // class XDMoDVersion
