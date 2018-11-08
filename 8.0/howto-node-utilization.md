---
title: HOWTO Enable Node Utilization Statistics
---

In addition to the "Utilization" statistics, Open XDMoD includes a "Node
Utilization" statistic.  This statistic is disabled by default because
it is not appropriate for all resource configurations.  Specifically, if
your resource allows node sharing, this statistic will not be accurate.
All nodes are assumed to be exclusive when this statistic is calculated.

To enable the statistic add an object to the `statistics` array for the
`Jobs` realm in `datawarehouse.json`:

    [
        {
            "realm": "Jobs",
            ...
            "statistics": [
                ...
                {
                    "name": "node_utilization",
                    "class": "NodeUtilizationStatistic"
                }
            ]
        }
    ]

After that just reload the portal and the Node Utilization statistic
will appear in the list of Job statistics.
