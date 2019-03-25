<?php
namespace Xdmod\About;
require_once __DIR__ . '/../../configuration/linker.php';
use CCR\DB;
?>
<h1>Federated Open XDMoD</h1>
<p>
    Imagine a collection of independent XDMoD instances, each monitoring its own set of computing resources. Each instance may comprise very different underlying resources, configurations, and aggregation levels. Reporting on resource consumption on any one of these XDMoD instances could be done easily through the powerful user interface. Reporting on the collection, however, would be difficult without a way to associate the data (and the aggregations) from these different instances. Federation is the answer to reporting on this imagined collection of XDMoD instances. Federation provides a combined, master view of job and performance data collected from individual XDMoD instances.
</p>
<p>
    <div style="text-align:center; width:65%;">
        <img src="/about/images/federated-diagram-1.gif" />
        <div>
            <fig>
                <em>
                    Example data flow from heterogeneous computing resources to an XDMoD federated hub. XDMoD instances X and Y ingest data into their databases from the computing resources that they monitor. Following ingestion on the satellite instances, job data are replicated to the federated hub's database, where they are aggregated for use in the federated XDMoD user interface.
                </em>
            </fig>
        </div>
    </div>
</p>
<p>
    Thus, federation provides resource managers with a unified XDMoD monitor that consolidates the data from a network of disjoint XDMoD instances. Once data is ingested on the individual XDMoD instances, it undergoes live replication to the central federation hub database, where it is then aggregated as appropriate to the requirements of the whole collection (aggregation is customized on each instance using local configuration files). The federation hub can then provide an integrated view of job and performance data collected from entirely independent XDMoD instances.
</p>
<p>
    The independent XDMoD instances in a federation need have no knowledge of one another. They may be closely related, and managed with the intent to fully share all data. Alternatively, their data can be kept isolated from one another, and only made visible to the federation hub. The only requirement is that each individual XDMoD instance must run the same version of XDMoD.
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
function getConfigValue($section, $key, $default=null)
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
else if ($role === 'hub'){
    $db = DB::factory('datawarehouse');
    $instanceResults = $db->query('SELECT * FROM federation_instances;');
    $instances = array();
    $lastCloudQuery = array();
    $derived = 'A';
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
            '(SELECT \'' . $prefix . '\' AS prefix, FROM_UNIXTIME(event_time_ts) as event_ts FROM `' . $prefix . '-modw_cloud`.`event` ORDER BY 2 DESC LIMIT 1) ' . $derived
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
