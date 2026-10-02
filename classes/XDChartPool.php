<?php

use CCR\DB;

	/*
	 * @Class XDChartPool
	 *
	 * Used for keeping managing a 'chart pool' that gets built as the result
	 * of visiting the portal.
	 *
	 */

	class XDChartPool {

		private $_user = null;

		private $_user_id = null;
		private $_person_id = null;
		private $_user_full_name = null;
		private $_user_email = null;
		private $_user_token = null;

		private $_table_name = 'ChartPool';

		private $_pdo = null;

		// --------------------------------------------

		public function __construct($user) {

         $this->_pdo = DB::factory('database');

			$this->_user = $user;
			$this->_user_id = $user->getUserID();
			$this->_person_id = $user->getPersonID();
			$this->_user_full_name = $user->getFormalName();
			$this->_user_token = $user->getToken();

         $this->_user_email  = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on') ?
                     xd_utilities\getConfiguration('general', 'debug_recipient') :
                     $user->getEmailAddress();

		}//__construct

		// --------------------------------------------

      public function emptyCache() {

         $this->_pdo->execute(
            'UPDATE ChartPool SET image_data=NULL WHERE user_id=:user_id',
            array(
               'user_id' => $this->_user_id
            )
         );

      }//emptyCache

		public function addChartToQueue($chartIdentifier, $chartTitle, $chartDrillDetails, $chartDateDesc) {

         if (empty($chartIdentifier)){
            throw new Exception("A chart identifier must be specified");
			}

         if (empty($chartTitle)){
            throw new Exception("A chart title must be specified");
			}

         // Since we are now letting the user have full control over the titles of charts (courtesy of the Metric Explorer),
         // we need to make sure the title is escaped properly such that the thumbnails in the Report Generator don't break.

         $chartIdentifier = str_replace("title=".$chartTitle, "title=".urlencode($chartTitle), $chartIdentifier);

         if ($this->chartExistsInQueue($chartIdentifier)){
            throw new Exception("chart_exists_in_queue");
         }

         $insertQuery = "INSERT INTO {$this->_table_name} (user_id, chart_id, chart_title, chart_drill_details, chart_date_description, type) VALUES " .
                        "(:user_id, :chart_id, :chart_title, :chart_drill_details, :chart_date_description, 'image')";

         $this->_pdo->execute(
            $insertQuery,
            array(
               'user_id' => $this->_user_id,
               'chart_id' => $chartIdentifier,
               'chart_title'=> $chartTitle,
               'chart_date_description' => $chartDateDesc,
               'chart_drill_details'=> $chartDrillDetails
            )
         );

		}//addChartToQueue

		// --------------------------------------------

		public function removeChartFromQueue($chartIdentifier) {

         if (empty($chartIdentifier)){
            throw new Exception("A chart identifier must be specified");
			}

         if (!$this->chartExistsInQueue($chartIdentifier)){
            throw new Exception("chart_does_not_exist_in_queue");
         }

         $this->_pdo->execute("DELETE FROM {$this->_table_name} WHERE user_id = :user_id AND chart_id = :chart_id", array('user_id' => $this->_user_id, 'chart_id' => $chartIdentifier));

		}//removeChartFromQueue

		// --------------------------------------------

		public function chartExistsInQueue($chartIdentifier, $chartTitle = '') {

         if (empty($chartIdentifier)){
            //throw new Exception("A chart identifier must be specified");
			}

            // This has been added due to urlencode no longer supporting nulls ( PHP 8.2 )
            if (is_null($chartTitle)) {
                $chartTitle = '';
            }

			$chartIdentifier = str_replace("title=".$chartTitle, "title=".urlencode($chartTitle), $chartIdentifier);

			$results = $this->_pdo->query("SELECT * FROM {$this->_table_name} WHERE user_id = :user_id AND chart_id = :chart_id", array('user_id' => $this->_user_id, 'chart_id' => $chartIdentifier));

			return (count($results) != 0);

		}//chartExistsInQueue

	}//XDChartPool
