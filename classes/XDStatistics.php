<?php

use CCR\DB;

class XDStatistics
{

    function getUserVisitStats($aggregation_type = 'month', $user_types = array())
    {
   
        $db = DB::factory('database');

        // The query does not consider internal and/or testing users

        $user_type_filter = '';
      
        if (count($user_types) > 0) {
            $user_type_filter = 'AND u.user_type IN ('.implode(',', $user_types).') ';
        }
      
        $query = "SELECT u.id, u.last_name, u.first_name, u.email_address, sm.init_time, u.username, 
                GROUP_CONCAT(r.description ORDER BY r.description) AS role_list, u.user_type  
                FROM SessionManager AS sm, UserRoles AS ur, Users AS u, Roles AS r 
                WHERE u.id = ur.user_id AND ur.role_id = r.role_id AND sm.user_id = u.id 
                $user_type_filter 
                GROUP BY CONCAT(u.id, '-', sm.init_time) ORDER BY sm.init_time DESC, r.description DESC";

        $results = $db->query($query);

        foreach ($results as &$r) {
            $time_frags = explode('.', $r['init_time']);
   
            //$r['init_time_datestamp'] = date('m/d/Y, g:i:s A', $time_frags[0]);
         
            if ($aggregation_type == 'month') {
                $r['init_time_datestamp'] = date('Y-m', $time_frags[0]);
            }

            if ($aggregation_type == 'year') {
                $r['init_time_datestamp'] = date('Y', $time_frags[0]);
            }
        }

        $monthAgg = array();

        foreach ($results as $r) {
            if (!isset($monthAgg[$r['init_time_datestamp']])) {
                $monthAgg[$r['init_time_datestamp']] = array();
            }

            $monthAgg[$r['init_time_datestamp']][] = $r;
        }
   
        $allData = array();
        $visit_freqs = array();

        foreach ($monthAgg as $m => $recs) {
            $userCounts = array();
            $userEntries = array();

            foreach ($recs as $rr) {
                if (!isset($userCounts[$rr['id']])) {
                    $userCounts[$rr['id']] = 0;
                }
                $userCounts[$rr['id']]++;
            }//foreach

            foreach ($recs as &$rr) {
                unset($rr['init_time']);

                $dd = $rr;

                $dd['timeframe'] = $dd['init_time_datestamp'];

                unset($dd['init_time_datestamp']);
                unset($dd['id']);

                $dd['visit_frequency'] = $userCounts[$rr['id']];

                $userEntries[$rr['id']] = $dd;
            }//foreach

            $allData = array_merge($allData, array_values($userEntries));
        }//foreach

        //return ($allData);

        foreach ($allData as $ad) {
            $visit_freqs[] = $ad['timeframe'].';'.str_pad($ad['visit_frequency'], 5, "0", STR_PAD_LEFT);
        }//foreach ($allData as $ad)

        array_multisort($visit_freqs, SORT_DESC, $allData);
         
        return $allData;
    }//getUserVisitStats
}//XDStatistics
