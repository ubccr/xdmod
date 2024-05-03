---
title: New Charting Library
---

As of version 11.0, Open XDMoD is now no longer bundled with any non-commercial licenses. The charting library used in Open XDMoD has changed in version 11.0 from [Highcharts](https://www.highcharts.com/) to [Plotly JS](https://plotly.com/javascript/), an open source library. This transition removes the non-commercial license required from the Highcharts library. Please refer to the [license notices](notices.md) for more information about the open source licenses bundled with Open XDMoD.

## Chart Feature Changes
Some chart features have been changed, added, and removed in Open XDMoD 11.0.

### Features Changed

- For line charts, the context menu for a data series is brought up by clicking on its points, not its lines. As before, the context menu can also be brought up by clicking on it in the legend.

- The button for resetting the chart zoom in Metric Explorer charts appears in the chart context menus instead of a button on the chart. Resetting the chart zoom for charts in all other tabs now requires a double click on the plotting area instead of a button on the chart.

### Features Added

- Chart axes are draggable to adjust the chart range. For charts in the Metric Explorer, the axis range can be reset to the default through the axis context menu. For other charts, the chart can be reset to the default by double-clicking.

- Rendering support for a subset of HTML tags in a chart's main title. Please refer to the [list](https://plotly.com/javascript/reference/layout/annotations/#layout-annotations-items-annotation-text) of supported HTML tags. Unsupported tags will not render but still display in the title.

### Features Temporarily Removed
The following chart features are no longer available but are planned to be added back in a future version.

- The ability to change the layering order for charts with multiple axes.
- Hover animations.
- Shadow.

## Known Issues

- Charts exported as images can have legend displacement based on the chart size. The current workaround is to make the exported chart larger until the legend fits. Customizing legend entry names to be shorter may also help.
- The legend double-click feature is disabled due to an interaction with legend single-click events.

Please [request support](support.md) for any issues not listed above.
