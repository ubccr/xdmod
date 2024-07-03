---
title: HOWTO Change Summary Page Charts
---

If a user wants to add a chart to their personal Summary tab, they can use Metric Explorer to create a chart,
then open the "Chart Options" context menu by left clicking in the chart, then check the "Show in Summary
tab" checkbox.

To change the charts that appear in users' Summary tabs by default, you'll need to create
a chart and add its JSON definition to the XDMoD configuration files.

The easiest way to create a JSON definition for a chart is interactively,
using Metric Explorer's JSON export function:

* Login to Open XDMoD with a user account that has the "Developer" role.
* Create a chart in the Metric Explorer.
* Open the "Chart Options" context menu by left clicking in the chart.
* Select the "View chart json" menu item.

Default summary charts are defined as lists of charts within the configuration files for user roles.
These are located in two different sets of locations: in the top level `roles.json` file
(`/etc/xdmod/roles.json` if you installed the RPM or `PREFIX/etc/roles.json` if you did a manual install),
and in any `.json` files located in `roles.d` (`/etc/xdmod/roles.d/` or `PREFIX/xdmod/roles.d/` respectively).

The base summary charts (the ones included with a install of Open XDMoD) are included implicity
for any user role if the field `summary_charts` is absent for that role in both `roles.json` and
in `roles.d`. If `summary_charts` contains any charts, those override displaying any of the summary charts.

By default:

* The role "pub" - which is applied to any users who are not logged in - has no `summary_charts` field and so implictly uses the base summary charts.
* The role "default" - which all other user roles inherit - has an empty `summary_charts` field in `roles.json`, but
  has explicitly defined summary charts appended in `roles.d/jobs.json` or your custom summary chart `.json` file that are equivalent to the base summary charts.

The recommended method to define new summary charts is to create a new `.json` file in `roles.d` that specifically handles
any summary chart customization, rather than using the existing `.json` files.If you wish to keep the default charts
included with `roles.d/jobs.json` and any other files that contain `+summary_charts`, please move them to the newly created file.

This set of configuration locations lets you edit the default summary charts in many different ways.
As an example, if you wanted to override the default charts for the "default" role, you could edit your summary chart customization `.json` file in `roles.d`, overwriting
any defined summary charts with the JSON pulled from Metric Explorer as described earlier. In the example below, you'd replace the `...`
in the `+summary_charts` list with the JSON you copied.

```json
{
    "+roles": {
        "+default": {
            ...
            "+summary_charts": [
                ...
            ]
        }
        ...
    }
}

```

This would only change the summary charts for logged-in users - if you wanted to make this same change for non-logged-in users,
you could do the same for the role "pub".

There's a known issue displaying charts with no `global_filters` field
in the Summary page. If the chart you're adding has no global filters,
add an empty `global_filters` field as shown below
(see the premade ones in `roles.d/jobs.json` for more examples).

```json
"+summary_charts": [
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
