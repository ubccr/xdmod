---
title: New Charting Library
---

The charting library used in Open XDMoD has changed in version 11.0 from [Highcharts](https://www.highcharts.com/) to [Plotly JS](https://plotly.com/javascript/), an open source library. This transition removes the non-commercial license required from the Highcharts library. Please refer to the [license notices](notices.md) for more information about the open source licenses bundled with Open XDMoD.

## Chart Feature Changes
Some chart features have been changed, added, and removed in Open XDMoD 11.0.

### Features Changed
The following chart features have changed.

- Resetting the chart zoom for Metric Explorer charts appears in the chart context menus instead of a button on the chart. Resetting the chart zoom for charts in all other tabs now requires a double click on the plotting area instead of a button on the chart.

### Features Added
The following are new chart features available.

- The range for charts in the Metric Explorer can be reset to default through the axis context menu.
- Charts can zoom by both 'x' and 'y' axis.
- Chart axes are draggable to adjust chart range.

### Features Removed
The following chart features are no longer available but are planned to be added back.

- Change layering for multiple axis charts.
- Hover animations.
- Shadow

## Know Issues
The following are known issues in Open XDMoD 11.0.

- Charts exported as images can have legend displacement based on the chart size. The current workaround is to make the exported chart larger until the legend fits. Customizing legend entry names to be shorter may also help.
- The legend double-click feature is disabled due to an interaction with legend single-click events.

Please [request support](support.md) for any issues not listed above.
