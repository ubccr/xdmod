---
title: HOWTO Change Summary Page Charts
---

If a user wants to add a chart to their Summary tab, they can use Metric Explorer to create a chart,
then open the "Chart Options" context menu by left clicking in the chart, then check the "Show in Summary
tab" checkbox.

To change the charts that appear in users' Summary tabs by default, you'll need to create
a chart and add its JSON definition to a list of summary charts for each user type.
The easiest way to create a chart to add to each user's list of charts is interactively, 
using Metric Explorer's JSON export function.

* Login to Open XDMoD with a user account that has the "Developer" role.
* Create a chart in the Metric Explorer.
* Open the "Chart Options" context menu by left clicking in the chart.
* Select the "View chart json" menu item.

To change the charts which appear in the Summary tab for all users, you need to update the
`roles.json` file (`/etc/xdmod/roles.json` if you installed the RPM or
`PREFIX/etc/roles.json` if you did a manual install).

The default summary charts (the ones included with a base intall of Open XDMoD) are included implicity 
for any user type if `summary_charts` is empty in `roles.json` for that role. As such, 
adding a chart to the `summary_charts` field for the role `pub`, which is non-logged-in users,
will override the default charts.

However, the default summary charts are also explicitly defined as added charts
for roles which inherit the `default` role (which is, by default, any logged-in users) 
by charts stored in `roles.d/jobs.json` (`/etc/xdmod/roles.d/jobs.json` if you installed the RPM or
`PREFIX/etc/roles.d/jobs.json` if you did a manual install) - to remove these default charts from 
logged-in users, the `+summary_charts` field will have to be deleted in this file.
 
In `roles.json`, copy the chart JSON into the list of summary charts, as shown below.

```json
"summary_charts": [
    {
        ...
    },
    {
        ...
    },
    ...
]
```

There's a known issue displaying charts with no `global_filters` field
in the Summary page. If the chart you're adding has no global filters,
add an empty `global_filters` field as shown below 
(see the premade ones in `jobs.json` for more examples).

```json
"summary_charts": [
    {
        "global_filters": {
            "data": [],
            "total": 0
        },
        ...
    },
    ...
]
```

By default, the only `summary_chart` field in roles.json is created for the `default` role,
which includes all logged in users. However, the `summary_charts` field can be created for any role,
allowing for changing which charts non-logged-in users can view, or created for other roles
to create summary charts for specific roles. 
