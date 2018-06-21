<?php

namespace Rest\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Rest\Utilities\Authentication;
use Rest\XdmodApplicationFactory;
use CCR\DB;

/** ==========================================================================================
 * Class SummaryStatisticsControllerProvider
 *
 * Provide summary statistics on past usage. This was origainally designed for display on the XSEDE
 * User Portal.
 * ==========================================================================================
 */

class SummaryStatisticsControllerProvider extends BaseControllerProvider
{
  // ------------------------------------------------------------------------------------------
  // Valid intervals for querying usage
  // See https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-add

    private static $validIntervals =
    array(
      "day",
      "week",
      "month",
      "year"
      );

  // ------------------------------------------------------------------------------------------
  // Valid group bys for querying usage

    private static $validGroupBys =
    array(
      "none",
      "state"
      );


  /** ------------------------------------------------------------------------------------------
   * @see BaseControllerProvider::setupRoutes
   * ------------------------------------------------------------------------------------------
   */

    public function setupRoutes(Application $app, \Silex\ControllerCollection $controller)
    {
        $root = $this->prefix;
        $myClass = get_class($this);
        $controller->get("$root/valid", "$myClass::getValidValues");
        $controller->get("$root/past_usage", "$myClass::getPastUsage");
    }  // setupRoutes()

  /** ------------------------------------------------------------------------------------------
   * Return a list of valid values for the requested type.
   *
   * Supported parameters:
   *  type - The type to request
   *
   * @param Request $request The request used to make this call.
   * @param Application $app The router application.
   * @return array Response data containing the following information:
   *   success: A boolean indicating if the call was successful.
   *   num: Number of records returned
   *   results: The current version of (Open) XDMoD.
   * ------------------------------------------------------------------------------------------
   */

    public function getValidValues(Request $request, Application $app)
    {
        $logger = $app['logger.db'];

        $params = new ParameterBag();
        $params->add($request->query->all());
        $params->add($request->request->all());

        if ( ! $params->has("type") ) {
            $msg = "Must supply a type: [interval, group_by]";

            // We should have a rest exception that automatically includes ip and route information and
            // logs to the database.

            $logger->info($this->formatLogMesssage($msg, $request));
            throw new \Exception($msg);
        }

        switch ( $params->get("type") ) {

            case 'interval':
                $result = self::$validIntervals;
                break;

            case 'group_by':
                $result = self::$validGroupBys;
                break;

            default:
                $msg = "Unknown type requested: '" . $params->get("type") . "'";
                $logger->info($this->formatLogMesssage($msg, $request));
                throw new \Exception($msg);
            break;

        }  // switch ( $param->get("type") )

        return $app->json(array(
                        'success' => true,
                        'num'     => count($result),
                        'results' => $result,
                        ));
    }  // getValidValues()

  /** ------------------------------------------------------------------------------------------
   * Return a summary of the past usage.  This is a stub that validates input parameters and calls
   * the appropriate private method for querying the database based on the grouping.  The default
   * period is the previous week with no grouping, but this may be customized.
   *
   * Supported parameters:
   *  interval - The previous interval to return, defaults to "week".
   *  num_intervals - The number of intervals to return, defaults to 1.
   *  group_by - The dimension to group by, defaults to "none".
   *
   * @param Request $request The request used to make this call.
   * @param Application $app The router application.
   * @return array Response data containing the following information:
   *   success: A boolean indicating if the call was successful.
   *   num: Number of records returned
   *   results: The current version of (Open) XDMoD.
   * ------------------------------------------------------------------------------------------
   */

    public function getPastUsage(Request $request, Application $app)
    {
        // Ensure that the user is a manager.
        // $this->authorize($request, array(ROLE_ID_MANAGER));

        $logger = $app['logger.db'];

        // Set default values (past 1 week, no grouping)

        $sqlParams = array('num_intervals' => 1,
                       'group_by'      => "none");

        $options = array('interval'      => "week",
                     'num_intervals' => 1,
                     'group_by'      => "none");

        $requestParams = new ParameterBag();
        $requestParams->add($request->query->all());
        $requestParams->add($request->request->all());

        // Set and verify request parameters

        foreach ($requestParams as $k => $v) {

            switch ( $k ) {

                case 'num_intervals':
                    if ( ! is_numeric($v) ) {
                        $msg = "Invalid number of intervals: '$v'";
                        $logger->info($this->formatLogMesssage($msg, $request, true));
                        throw new \Exception($msg);
                    }
                    // We only support positive numbers
                    $sqlParams[':' . $k] = $options[$k] = abs($v);
                    break;

                case 'interval':
                    $v = strtolower($v);
                    if ( ! in_array($v, self::$validIntervals) ) {
                        $msg = "Unsupported interval value: '$v'";
                        $logger->err($this->formatLogMesssage($msg, $request, true));
                        throw new \Exception($msg);
                    }
                    $options[$k] = $v;
                    break;

                case 'group_by':
                    $v = strtolower($v);
                    if ( ! in_array($v, self::$validGroupBys) ) {
                        $msg = "Unsupported group by value: '$v'";
                        $logger->info($this->formatLogMesssage($msg, $request, true));
                        throw new \Exception($msg);
                    }
                    $sqlParams[':' . $k] = $options[$k] = $v;
                    break;

                default:
                    break;

            }  // switch ( $k )

        } // foreach ($params as $k => $v)

        // Override the interval so requests don;t crush the database

        switch ( $options['interval'] ) {
            case 'day':
                if ( $options['num_intervals'] > 365 ) {
                    $options['num_intervals'] = 365;
                }
                break;
            case 'week':
                if ( $options['num_intervals'] > 52 ) {
                    $options['num_intervals'] = 52;
                }
                break;
            case 'month':
                if ( $options['num_intervals'] > 60 ) {
                    $options['num_intervals'] = 60;
                }
                break;
            case 'year':
                if ( $options['num_intervals'] > 5 ) {
                    $options['num_intervals'] = 5;
                }
                break;
        }  // switch ( $options['interval'] )

        if ( "state" ==  $options['group_by'] ) {
            $result = $this->getPastUsageByState($options);
        } else {
            $result = $this->getPastUsageByNone($options);
        }

        return $app->json(array(
                        'success' => true,
                        'num'     => count($result),
                        'interval' => $options['interval'],
                        'num_intervals' => $options['num_intervals'],
                        'group_by' => $options['group_by'],
                        'results' => $result,
                        ));
    }  // getPastUsage()


  /** ------------------------------------------------------------------------------------------
   * Return a summary of the past usage with no grouping. This includes:
   *   num_projects
   *   num_people
   *   num_jobs_run
   *   xdsu_charged
   *
   * @param array $options Options verified and set by getPastUsage()
   * @return array Result of the query to the database (associative array)
   * ------------------------------------------------------------------------------------------
   */

    private function getPastUsageByNone(array $options)
    {

        if ( ! array_key_exists('interval', $options) ||
         ! array_key_exists('num_intervals', $options) )
        {
            throw new \Exception("interval or num_intervals not provded");
        }

        // return numeric values to XUP for number of active users in last (day, week, month, year) and
        // XD resources consumed (in some number of units).

        $sqlParams = array(
        ':num_intervals' => $options['num_intervals']
        );

        $sql = "select
count(distinct account_id) as num_projects, count(distinct person_id) as num_people,
sum(job_count) as num_jobs_run, sum(local_charge_su) as xdsu_charged
from modw_aggregates.jobfact_by_day job
join modw.days d on d.id = job.day_id
where d.day_start between date(date_sub(now(), interval :num_intervals " . $options['interval'] . ")) and now()";

        try {
            $pdo = DB::factory('database');
            $result = $pdo->query($sql, $sqlParams);
        } catch(\PDOException $e) {
            throw $e;
        }

        return $result;

    }  // getPastUsageByNone()


  /** ------------------------------------------------------------------------------------------
   * Return a summary of the past usage grouped by state. This includes:
   *   state_abbrev
   *   state_name
   *   epscor
   *   num_projects
   *   num_people
   *   num_jobs_run
   *   xdsu_charged
   *
   * @param array $options Options verified and set by getPastUsage()
   * @return array Result of the query to the database (associative array)
   * ------------------------------------------------------------------------------------------
   */

    private function getPastUsageByState(array $options)
    {
        if ( ! array_key_exists('interval', $options) ||
         ! array_key_exists('num_intervals', $options) )
        {
            throw new \Exception("interval or num_intervals not provded");
        }

        $sqlParams = array(
        ':num_intervals' => $options['num_intervals']
        );

        // Select all states along with their metadata and then left outer join with the statistics This
        // will list all states and allow us to include zeros for states where no jobs ran.
        //
        // The inner query calculates statistics per state but states where no jobs ran won't be in this
        // list so we left outer join with the list of all US states first.

        $sql = "select
state.abbrev, state.name, ifnull(state.epscor, 0) as epscor,
ifnull(stats.num_projects, 0) as num_projects,
ifnull(stats.num_people, 0) as num_people,
ifnull(stats.num_jobs_run, 0) as num_jobs_run,
ifnull(stats.xdsu_charged, 0) as xdsu_charged
from modw.state state
left outer join (
  select
  state.id as state_id,
  count(distinct account_id) as num_projects, count(distinct person_id) as num_people,
  sum(job_count) as num_jobs_run, sum(local_charge_su) as xdsu_charged
  from modw_aggregates.jobfact_by_day job
  join modw.days d on d.id = job.day_id
  join modw.organization org on org.id = job.piperson_organization_id
  join modw.state state on state.id = org.state_id
  where d.day_start between date(date_sub(now(), interval :num_intervals " . $options['interval'] . ")) and now()
  and state.country_id = 210
  group by state.abbrev
) stats on stats.state_id = state.id
where state.country_id = 210
order by state.abbrev asc";

        try {
            $pdo = DB::factory('database');
            $result = $pdo->query($sql, $sqlParams);
        } catch(\PDOException $e) {
            throw $e;
        }

        return $result;

    }  // getPastUsageByState()
}  // class SummaryStatisticsControllerProvider
