---
title: HOWTO Enable Node Utilization Statistics
---

In addition to the "Utilization" statistics, Open XDMoD includes a "Node
Utilization" statistic.  This statistic is disabled by default because
it is not appropriate for all resource configurations.  Specifically, if
your resource allows node sharing, this statistic will not be accurate.
All nodes are assumed to be exclusive when this statistic is calculated.

First add the statistic to the Jobs realm statistics configuration.

`/etc/xdmod/datawarehouse.d/ref/Jobs-statistics.json`:

```json
{
    ...
    "node_utilization": {
        "name": "Node Utilization",
        "description_html": "The percentage of node time that a resource has been running jobs.<br/><i>Node Utilization:</i> The ratio of the total node hours consumed by jobs over a given time period divided by the maximum node hours that the system could deliver (based on the number of nodes present on the resources). This value does not take into account downtimes or outages. It is just calculated based on the number of nodes in the resource specifications.",
        "precision": 2,
        "unit": "%",
        "aggregate_formula": {
            "$include": "datawarehouse.d/include/Jobs-node-util-agg.sql"
        },
        "timeseries_formula": {
            "$include": "datawarehouse.d/include/Jobs-node-util-time.sql"
        }
    }
}
```

Next, run `acl-config` and restart your web server. Note, depending on which Operating System you have XDMoD installed
on you may need to restart `php-fpm` in addition to `httpd`.

Reload the portal and the "Node Utilization" statistic will appear in the list
of Job statistics.
