<?php
namespace Xdmod\About;

require_once __DIR__ . '/../../configuration/linker.php';
use CCR\DB;
?>
<h1>Federated Open XDMoD</h1>
<p>
    Federated XDMoD supports the collection and aggregation of data from a number of fully-functional and individually managed XDMoD instances into a single federated instance of XDMoD capable of displaying federation-wide metrics.
    Each participating institution deploys an XDMoD instance through which local data will be collected and shipped to a central Federation Hub where it is aggregated to provide a federation-wide view of the data.
    Data particular to an individual center is available from the Hub by applying filters and drill-downs.
</p>
<p>
    <div style="text-align:center; width:65%;">
        <img src="/about/images/federated-diagram-1.gif" />
        <div>
            <fig>
                <em>
                    Example data flow from heterogeneous computing resources to an XDMoD federated hub.
                    XDMoD instances X and Y ingest data into their databases from the computing resources that they monitor.
                    Following ingestion on the satellite instances, job data are replicated to the federated hub's database, where they are aggregated for use in the federated XDMoD user interface.
                </em>
            </fig>
        </div>
    </div>
</p>
<p>
    A simple example use of the federated module is:
    Three academic instituitions each with their own HPC resource.
    Each institution has its own XDMoD instance which contains the accounting data for only their HPC resource.
    These institutions federate their data to a central hub.
    HPC accounting data for all three HPC resources is shown on the central hub.
    This central hub can then be used to report on the combined data.
</p>
<p>
    This example illistrates only one use case.
    The federated module supports cloud data as well as HPC.  Support for other data realms is planned.
    There are no pre defined limits on the number of instances that can be part of a federation.
</p>
<p>
    For more information see Section II of <a href="https://ieeexplore.ieee.org/document/8514918">Federating XDMoD to Monitor Affiliated Computing Resources</a>.
</p>
<p>
    Documentation avialable at <a href="https://federated.xdmod.org">https://federated.xdmod.org</a>.
</p>
<p>
    Source code and downloads at <a href="https://github.com/ubccr/xdmod-federated">https://github.com/ubccr/xdmod-federated</a>.
</p>
<?php

/**
 * Attempt to retrieve a value from the configuration located at
 * $section->$key.
 *
 * @param str   $section the section in which the desired value resides.
 * @param str   $key     the key under which the desired value can be found.
 * @param mixed $default the default value to provide if there is nothing found.
 *
 * @return mixed
 **/
function getConfigValue($section, $key, $default = null)
{
    try {
        $result = \xd_utilities\getConfiguration($section, $key);
    } catch(\Exception $e) {
        $result = $default;
    }
    return $result;
}

$role = getConfigValue('federated', 'role');
if($role === 'instance'){
    $hubUrl = getConfigValue('federated', 'huburl');
    echo '<h2>This instance is part of a federation</h2>';
    echo 'Federation Hub: <a href="' . $hubUrl .'">' . $hubUrl .'</a>';
}
elseif ($role === 'hub'){
    $db = DB::factory('datawarehouse');
    $instanceResults = $db->query('SELECT * FROM federation_instances;');
    $instances = array();
    $lastCloudQuery = array();
    $derived = 1;
    foreach ($instanceResults as $instance) {
        $prefix = $instance['prefix'];
        $extra = json_decode($instance['extra'], true);
        $instances[$prefix] = array(
            'contact' => $extra['contact'],
            'url' => $extra['url'],
            'lastCloudEvent' => null,
            'lastJobTask' => null
        );
        unset($extra['contact']);
        unset($extra['url']);
        $instances[$prefix]['extra'] = $extra;
        array_push(
            $lastCloudQuery,
            '(SELECT \'' . $prefix . '\' AS prefix, FROM_UNIXTIME(event_time_ts) as event_ts FROM `' . $prefix . '-modw_cloud`.`event` ORDER BY 2 DESC LIMIT 1) `A' . $derived . '`'
        );
        $derived++;
    }
    $lastCloudResults = $db->query('SELECT * FROM ' . implode($lastCloudQuery, ' UNION ALL SELECT * FROM '));
    foreach ($lastCloudResults as $result) {
        $instances[$result['prefix']]['lastCloudEvent'] = $result['event_ts'];
    }
    echo '<h2>Instances that are part of this Federation</h2><ul>';
    foreach($instances as $instance){
        echo '<li><p><a href="' . $instance['url'] . '">' . $instance['url'] . '</a></p>last event retrieved (' . $instance['lastCloudEvent'] . ')</li>';
    }
    echo '</ul>';
}
else {
    echo 'This installation is not part of a federation.';
}
