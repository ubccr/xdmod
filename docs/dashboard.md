---
title: User Dashboard
---

The User Dashboard is a configurable tab that is displayed
for logged Open XDMoD users. The dashboard tab displays multiple
configurable dashboard components. The components that are shown
are tailored to the role settings for a user. For example,
a Center Director's dashboard shows data about the overall system
over the long term, whereas an end-users' dashboard shows information
about the jobs that they ran recently.

The dashboard tab is enabled globally. When the tab is enabled
all logged in users have the dashboard as the first tab. If the
dashboard is disabled then the summary tab is the first tab. The public
(non-logged in) view always shows the summary tab.

Configuration
-------------

The global setting to enable the dashboard is in `portal_settings.ini`. The
'General Settings' menu in the `xdmod-setup` script includes the option
to enable the dashboard. Alternatively the dashboard can be enable by manually
editing the `portal_setting.ini` file:
```ini
[features]
; Enable the user dashboard interface. This replaces the existing
; summary page with a tab that displays information specific
; to each user's role
user_dashboard = "on"
```
The `user_dashboard` setting should be set to `on` to enable the dashboard
and `off` to disable the dashboard.

The dashboard components are configured in the `/etc/roles.d/dashboard.json` file.
This defines list of components under the `dashboard_components` property for each of the possible user roles. For each
component it is possible to specify the default location within the dashboard tab and also
the component's configuration. The same component may be specified for multiple user roles with
different configuration settings.

Many components also have user configurable overrides. This section details the configuration
settings for an Open XDMoD system administrator. See the User Manual for details about user
configurable settings.

### Common Configuration

An example configuration is shown below. This defines the saved charts component
to be displayed by default in the second row on the left hand side (row and
columns are indexed starting at zero).
```json
{
    "name": "Job Component",
    "type": "xdmod-dash-job-cmp",
    "config": {
        "multiuser": true,
        "timeframe": "30 day"
    },
    "location": {
        "column": 0,
        "row": 0
    }
},
```

The common configuration settings are described below:

| Parameter         | Description   |
| ----------------- | ------------- |
| `name`            | The string that will be displayed in the title bar of the component in the dashboard tab. |
| `type`            | The internal unique identifier for the component. |
| `location.column` | The default column to position the component in the dashboard. The column index starts at zero for the leftmost column. |
| `location.row`    | The default row to position the component in the dashboard. The row index starts at zero from the top of the tab downwards. |
| `region`          | Some dashboard components (such as the Center Summary Component) are designed to be displayed full width at the top of the tab. These should have <code>region</code> set to <code>top</code>. The location setting is ignored and may be omitted in this case. |
| `config`          | The configuration to pass to the component. The specific configuration options for each component are documented below. If a component does not have any configuration settings or the defaults are to be used then the config property may be ommitted. |

The dashboard tab does not support gaps between the components so the row number
specifies the relative order of the components not their absolute position.
If multiple components specify the same location then they will be placed
in the specified column and their relative row position will be in alphabetical
order by the `name` property. All components specified for a given role must
have a unique name.

## Chart Component

The Chart Component (`type` = `xdmod-dash-chart-cmp`) is used to display charts from the Metric Explorer.
The configuration porperties are shown below:

| Parameter     | Description   |
| ------------- | ------------- |
| `chart`       | A chart configuration object from the Metric Explorer. |


The chart configuration objects can be generated using the Metric Explorer:
* Login to Open XDMoD with a user account that has the "Developer" role.
* Create a chart in the Metric Explorer.
* Open the "Chart Options" context menu by left clicking in the chart.
* Select the "View chart json" menu item.

Clicking the "View chart json" menu item opens a window that contains the
chart configuration json object for the current chart. This can be copied
directly into the chart component configuration.

The component also supports some macros in the chart configuration.

| Macro Name   | Description |
| ------------ | ----------- |
| `${PERSON_ID}` | The internal Open XDMoD identifier used by the "User" chart filter for the current logged in user. |
| `${PERSON_NAME}` | The text string for the "User" chart filter for the current logged in user. |

These macros can be used to add chart filters that are tailored to the
logged in user. For example, the `${PERSON_ID}` macro can be used to add a
chart filter to only show the jobs for that user.


## Saved Charts and Reports Component

The Saved Charts and Reports Component (`type` = `xdmod-dash-savedchart-cmp`) displays a list that contains
the saved charts from the Metric Explorer and saved reports from the
Report Generator. This component does not have any additional
configuration beyond the common configuration settings.

## Chart Thumbnails Component

The Chart Thumbnails Component (`type` = `xdmod-dash-reportthumb-cmp`) shows a
 set of chart thumbnails. Clicking a thumbnail image brings up a window with
 the interactive version of the chart. The charts displayed in the component
are based on a report that is generated from the corresponding report template
the first time a user logs in.

| Parameter     | Description   |
| ------------- | ------------- |
| `timeframe`   | The default timeframe for the chart thumbnails |


## Jobs Component

The Jobs Component (`type` = `xdmod-dash-job-cmp`) displays a list of recent jobs.
The specific jobs available depend on the role of the user.

| Parameter     | Description   |
| ------------- | ------------- |
| `timeframe`   | The timeframe of jobs to display |
| `multiuser`   | Whether to display a drop down box that allows filtering by the person who ran the job. This also enables a column in the jobs table with the name of the job's owner. This value is typically set to true for roles that have access to job information about multiple users, such as PI or Center Staff. It is typically set to false for the user role since that role can only see information about a single person. |
