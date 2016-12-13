<?php
/*
 * @author Jeanette Sperhac
 * @date 2014-07-23 (date revised and renamed)
 *
 * Enable retrieval of user resource Allocations data from the data warehouse.
 * Some functions previously provided by this class have been elided as they
 * were unused.
 *
 * Historically, this class also provides a connect() method used by the Query
 * classes.
 *
 * Uses CCR\DB PDODB class for database queries and prepared statements.
 *
 */

use CCR\DB;

/*
 *	@Class DataWarehouse
 *
 */
class DataWarehouse
{

	private static $db = null;

	/*
	 * @function connect()
	 * @access public
	 */
	public static function connect()
	{
		if(!self::$db)
		{
			self::$db = DB::factory('datawarehouse');
		}
		return self::$db;

	}
	/*
	 * @function destroy()
	 * @access public
	 */
	public function destroy()
	{
		if(self::$db !== null)
		{
			self::$db = null;
		}
	}

	/*
	 * @function __destruct()
	 * @access public
	 */
	function __destruct()
	{
		destroy();
	}

	/************************************************************
	 * @function getPersonIdByUsername
	 * @access public
	 *
	 * @param string username
	 *
	 * @return person_id or -2
	 ************************************************************/
	 public function getPersonIdByUsername($username = NULL){
		 if(!empty($username)){
			$dbh = self::connect();
			$personId = $dbh->query(" SELECT person_id
				FROM modw.systemaccount
				WHERE username = :username
				LIMIT 1",
			 array(
				':username' => $username,
			));
			if(count($personId) > 0){
				return $personId[0]['person_id'];
			}
		}
		 return -2;
	 }
	/************************************************************
	 * @function getAllocations()
	 * @access public
	 *
	 * @param array $config (Parameters determining allocations to be fetched:
	 *											person_id: person for whom allocations are being determined
	 *											show_active: boolean, display active or inactive allocations
	 *											allocation_id: int, display a particular allocaion
	 *											is_pi_of_allocation: boolean, is specified person the pi?
	 *											)
	 * @returns Array values consisting of the formatted query results
	 *
	 * Used by: Allocations tab controller, html/controllers/ui_data/allocations.php
	 *
	 ************************************************************/
	public static function getAllocations($config = array())
	{

		$person_id = $config['person_id'];
		$showActive = isset($config['show_active']) ? $config['show_active'] : true;
		$allocation_id = isset($config['allocation_id']) ? $config['allocation_id'] : -1;

		$pi = "";

		if (isset($config['is_pi_of_allocation'])) {
			 $pi = "and als.principalinvestigator_person_id ".($config['is_pi_of_allocation'] ? "" : "!")."= $person_id";
		}


		$query = "SELECT DISTINCT
				als.allocation_id,
				als.principalinvestigator_person_id,
				als.person_id,
				als.charge_number,
				als.project_title,
				t.name as request_type,
				als.resource_name,
				als.base_allocation as base,
				FORMAT(als.base_allocation,2) as base_formatted,
				als.remaining_allocation as remaining,
				FORMAT(als.remaining_allocation,2) as remaining_formatted,
				als.pi_last_name,
				als.pi_first_name,
				als.project_title,
				als.status,
				als.initial_start_date as start,
				als.end_date as end
			FROM modw_aggregates.allocation_summary als, resourcefact re, person pti, allocation a, transactiontype t, request req
			WHERE als.person_id = $person_id
				AND als.resource_id = re.id
				AND als.allocation_id = a.id
				AND a.request_id = req.id
				AND t.id = req.request_type_id
				$pi
				AND pti.id = als.person_id
				".($allocation_id > -1 ? " " :" AND als.status = '".($showActive?'active':'expired')."' ")."
				AND als.allocation_id ".($allocation_id > 0?" = ".$allocation_id:" > -1 ")."
			GROUP BY
				als.allocation_id
			ORDER BY
				end
			DESC";

		self::connect();
		$results = self::$db->query($query);

		$results_b = array();

		$charge_allocation_map = array();

		// =================================================================================================

		$rct = 0;

		// Consolidation Phase (eliminating redundant records based on a common charge number)
		// The redundancies are also due to the same charge number across multiple resources (each of which has a unique allocation id)
		// Constructing a 1:M (M >= 1) map of charge number to allocation id(s) and/or resource(s)

		foreach ($results as $r) {

			$cn = $r['charge_number'];

			if (!isset($charge_allocation_map[$cn])) {
				$charge_allocation_map[$cn] = array(
					'pi' => 0,
					'allocation_ids' => array(),
					'resources' => array(),
					'base' => 0,
					'remaining' => 0,
					'total_base_formatted' => 0,
					'total_remaining_formatted' => 0
				);
			}

			$charge_allocation_map[$cn]['pi'] = $r['principalinvestigator_person_id'];
			$charge_allocation_map[$cn]['allocation_ids'][$r['allocation_id']] = array(
				'name' => $r['resource_name'],
				'timeframe' => $r['start'].' to '.$r['end'],
				'type' => $r['request_type']
			);

			$charge_allocation_map[$cn]['resources'][] = array(
				'allocation_id' => $r['allocation_id'],
				'resource_name' => $r['resource_name'],
				'timeframe' => $r['start'].' to '.$r['end'],
				'type' => $r['request_type'],
				'base' => $r['base'],
				'remaining' => $r['remaining'],
				'base_formatted' => number_format($r['base'],2),
				'remaining_formatted' => number_format($r['remaining'],2)
			);

			// General project details =======================================

			$charge_allocation_map[$cn]['project_title'] = $r['project_title'];
			$charge_allocation_map[$cn]['charge_number'] = $r['charge_number'];
			$charge_allocation_map[$cn]['status'] = $r['status'];
			$charge_allocation_map[$cn]['start'] = $r['start'];
			$charge_allocation_map[$cn]['end'] = $r['end'];

			// Base & Remaining SU details ===================================

			$charge_allocation_map[$cn]['base'] += $r['base'];
			$charge_allocation_map[$cn]['remaining'] += $r['remaining'];

			$charge_allocation_map[$cn]['base_formatted'] = number_format($charge_allocation_map[$cn]['base'],2);
			$charge_allocation_map[$cn]['remaining_formatted'] = number_format($charge_allocation_map[$cn]['remaining'],2);

		}//foreach ($results as $r)

		// =================================================================================================

		foreach ($charge_allocation_map as &$c) {

			$allocation_ids = implode(',', array_keys($c['allocation_ids']));

			$query = "SELECT ab.allocation_id, ab.person_id, p.last_name, p.first_name, ab.used_allocation
				FROM modw.allocationbreakdown AS ab, modw.person AS p
				WHERE ab.person_id = p.id
				AND ab.allocation_id
				AND FIND_IN_SET(ab.allocation_id, :allocation_ids)
				ORDER BY ab.person_id";

			$results = self::$db->query($query, array(':allocation_ids' => $allocation_ids));

			$user_pool = array();

			// Construct an inverse map (allocation_id to user listing)
			$inverseMap = array();

			foreach ($results as $r) {

				$pid = $r['person_id'];

				// Force the (upcoming) sort procedure to place the PI at the top
				//$prefix = ($c['pi'] == $pid) ? "0" : "";

				if (!isset($user_pool[$pid])) {

					$user_pool[$pid] = array(
						'name' => $r['last_name'].", ".$r['first_name'],
						'is_pi' => ($c['pi'] === $pid),
						'total' => 0,
						'resources' => array()
					);

				}

				if (!isset($inverseMap[$r['allocation_id']])) {
					$inverseMap[$r['allocation_id']] = array();
				}

				if (number_format($r['used_allocation']) != 0) {

					// Append any utilized resources to the current user, each differentiated by allocation id

					$user_pool[$pid]['resources'][$r['allocation_id']] = array(
						//'allocation_id' => $r['allocation_id'],
						'name' => $c['allocation_ids'][$r['allocation_id']]['name'],
						'timeframe' => $c['allocation_ids'][$r['allocation_id']]['timeframe'],
						'type' => $c['allocation_ids'][$r['allocation_id']]['type'],
						'used' => number_format($r['used_allocation'],2)
					);

					$inverseMap[$r['allocation_id']][] = array(
						'name' => $user_pool[$pid]['name'],
						'is_pi' => ($c['pi'] === $pid),
						'consumption' => $r['used_allocation'],
						'used' => number_format($r['used_allocation'],2)
					);

				}

				$user_pool[$pid]['total'] += $r['used_allocation'];
				$user_pool[$pid]['total_formatted'] = number_format($user_pool[$pid]['total'],2);

			}//foreach ($results as $r)

			// ----------------------------------------------------

			foreach ($c['resources'] as &$cr) {

				$cr['users'] = $inverseMap[$cr['allocation_id']];

				// Sort users on each resource (by decreasing usage)
				usort($cr['users'], function($a, $b){
					return $b['consumption'] - $a['consumption'];
					//return strcmp($a['name'], $b['name']);	<-- former sort method was alphabetical, ascending

				});

				unset($cr['allocation_id']);

			}//foreach

			unset($c['allocation_ids']);

			// Sort the resources (associated with a user) by (ascending) resource name
			foreach ($user_pool as $p => &$v) {

				$v['resources'] = array_values($v['resources']);

				usort($v['resources'], function($a, $b){
					return strcmp($a['name'], $b['name']);
				});

			}//foreach

			// Cache the PI user entry and remove it from $user_pool (prior to being sorted)
			$pi_slot = $user_pool[$c['pi']];
			unset($user_pool[$c['pi']]);
			unset($c['pi']);

			$c['users'] = array_values($user_pool);

			// Sort users by total usage (descending)
			usort($c['users'], function($a, $b){

				return $b['total'] - $a['total'];
				// return strcmp($a['name'], $b['name']);	<-- former sort method was alphabetical, ascending

			});

			// PI will be @ top, regardless of sort outcome
			array_unshift($c['users'], $pi_slot);

			// Sort the resources alphabetically

			usort($c['resources'], function($a, $b){
				return strcmp($a['resource_name'], $b['resource_name']);
			});

		}//foreach ($charge_allocation_map as &$c)

		// =================================================================================================

		return array_values($charge_allocation_map);

	}	//getAllocations()


	/************************************************************
	 * @function getAllocationsByChargeNumber()
	 * @access public
	 *
	 * Determine allocations by charge number
	 *
	 * @param $person_id (Person for whom allocation applies)
	 * @param $start_date (Allocation start date)
	 * @param $end_date (Allocation end date)
	 * @param Boolean $is_pi
	 *
	 * @returns array of allocations
	 *
	 * Used by: /html/oauth/user_check.php
	 ************************************************************/

	 public static function getAllocationsByChargeNumber($person_id, $start_date, $end_date, $is_pi = false)
	 {
		$query = "SELECT
			als.charge_number,
			als.allocation_id,
			GROUP_CONCAT(
				DISTINCT(CONCAT(
					TRIM(als.project_title),
					'|',
					COALESCE(als.proposal_number,'NA'),
					'|',
					COALESCE(als.grant_number,'NA')
				))
				ORDER BY
				als.project_title SEPARATOR ';')
			AS project_titles,
			GROUP_CONCAT(
				DISTINCT(CONCAT(
					als.fos_name,
					', ',
					als.fos_parent_name,
					', ',
					als.fos_directorate_name
				))
				ORDER BY
					als.fos_name SEPARATOR ';')
			AS fos_names,
			GROUP_CONCAT(
				DISTINCT(CONCAT(
					alr_rs.code,
					' - ',
					alr_rs.name
				))
				ORDER BY
					alr_rs.code)
			AS resource_names,
			GROUP_CONCAT(
				DISTINCT(CONCAT(
					als.pi_last_name,
					', ',
					als.pi_first_name))
				ORDER BY
					als.pi_last_name SEPARATOR ';')
			AS pi_names,
			als.status,
			MIN(als.initial_start_date) AS start,
			MAX(als.end_date) AS end,
			SUM(als.base_allocation) AS base,
			FORMAT(SUM(als.base_allocation),0) AS base_formatted,
			SUM(als.initial_allocation) AS initial,
			FORMAT(SUM(als.initial_allocation),0) AS initial_formatted,
			SUM(als.remaining_allocation) AS remaining,
			FORMAT(SUM(als.remaining_allocation),1) AS remaining_formatted,
			SUM(als.total_used_allocation) AS used,
			FORMAT(SUM(als.total_used_allocation),1) AS used_formatted,
			SUM(als.total_used_allocation_by_user) AS used_by_user,
			FORMAT(SUM(als.total_used_allocation_by_user),1) AS used_by_user_formatted
		FROM modw_aggregates.allocation_summary als, person pti, allocationonresource alr, resourcefact alr_rs
		WHERE als." . ($is_pi ? 'principalinvestigator_person_id' : 'person_id' ) . " = :person_id
			AND pti.id = als.person_id
			AND alr.allocation_id = als.allocation_id
			AND alr.allocation_state_id = 2
			AND alr_rs.id = alr.resource_id
			AND
				(als.initial_start_date between :start_date and :end_date or
				:start_date between als.initial_start_date and als.end_date)
		GROUP BY
			als.charge_number,
			als.allocation_id,
			als.status
		ORDER BY
			status,
			als.allocation_id,
			end
		DESC";

		self::connect();
		$results = self::$db->query($query,
			array('person_id' => $person_id,
			'start_date' => $start_date,
			'end_date' => $end_date)
		);
		return $results;
	} // getAllocationsByChargeNumber()

} // class DataWarehouse
?>
