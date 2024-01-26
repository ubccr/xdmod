/* eslint no-param-reassign: ["error", { "props": false }]*/
// TODO: Convert label and axis formatters for Plotly
Ext.namespace('XDMoD.utils');

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    var baseChartOptions = {};
    let configs = { displayModeBar: false, doubleClick: 'reset', doubleClickDelay: 500};
    let isEmpty = false;
    jQuery.extend(true, baseChartOptions, chartOptions);

    let adjustTitles = (baseChartOptions) => {
        let subtitle = baseChartOptions.layout.annotations[1];
        const len = subtitle.text.length;
        if (len > 0) {
           const axWidth = baseChartOptions.layout.width -  baseChartOptions.layout.margin.l - baseChartOptions.layout.margin.r;
           const subtitle_lines = CCR.xdmod.ui.lineSplit(subtitle.text, Math.trunc(axWidth / 8));
           baseChartOptions.layout.margin.t += (subtitle_lines.length * 15);
           baseChartOptions.layout.annotations[1].text = subtitle_lines.join('<br />');
           baseChartOptions.layout.annotations[0].y += (0.05 * subtitle_lines);
        }
    }

    if (baseChartOptions.data && baseChartOptions.data.length === 0) {
        isEmpty = true;
        let errorConfig = getNoDataErrorConfig();
        const width = baseChartOptions.layout.width;
        const height = baseChartOptions.layout.height;
        let margin;
        let titleAndSubtitle
        if (baseChartOptions.layout.annotations) {
            margin = baseChartOptions.layout.margin;
            titleAndSubtitle = baseChartOptions.layout.annotations.slice(0,2);
        }   
        baseChartOptions.layout = errorConfig;
        baseChartOptions.layout.width = width;
        baseChartOptions.layout.height = height;
        if (baseChartOptions.realmOverview) {
            baseChartOptions.layout.images[0].sizex = 4;
            baseChartOptions.layout.images[0].sizey = 4;
            baseChartOptions.layout.images[0].y = 2.5;
        }
        else if (margin && titleAndSubtitle) {
            baseChartOptions.layout.margin = margin;
            baseChartOptions.layout.annotations = titleAndSubtitle;
            baseChartOptions.layout.annotations[0].y = 1.0;
            adjustTitles(baseChartOptions);
        }
    } else {
        if ('annotations' in baseChartOptions.layout && baseChartOptions.layout.annotations.length != 0) {
            if (baseChartOptions.data[0].type === 'pie') {
                baseChartOptions.layout.margin.t += 40;
                baseChartOptions.layout.annotations[0].y = 1.1;
                baseChartOptions.layout.annotations[1].y = 1.085;
            }
            adjustTitles(baseChartOptions); 
            // Place credits in bottom right corner
            if (baseChartOptions.layout.thumbnail) {
                baseChartOptions.layout.annotations[2].yshift = ((baseChartOptions.layout.height - baseChartOptions.layout.margin.t) * -1);
                baseChartOptions.layout.annotations[2].xshift = baseChartOptions.layout.margin.r;
            }
            else {
                baseChartOptions.layout.annotations[2].yshift = ((baseChartOptions.layout.height - baseChartOptions.layout.margin.t) * -1);
                baseChartOptions.layout.annotations[2].xshift = baseChartOptions.layout.margin.r;
            }
        }
    }
    if (baseChartOptions.metricExplorer) {
        configs.showTips = false;
    }

    if (baseChartOptions.layout.thumbnail && !isEmpty) {
        const endIndex = baseChartOptions.layout.annotations.findIndex((elem) => elem.name === 'data_label');
        if (endIndex === -1) {
            baseChartOptions.layout.annotations = [];
        }
        baseChartOptions.layout.annotations.splice(0, endIndex);
    }

    if (baseChartOptions.layout.thumbnail && baseChartOptions.layout.xaxis.type != 'category' && !isEmpty) {
       const axesLabels = getMultiAxisObjects(baseChartOptions.layout);
       if (baseChartOptions.swapXY) {
           baseChartOptions.layout.yaxis.tickmode = 'auto';
           baseChartOptions.layout.yaxis.nticks = 5;
       }
       else {
           baseChartOptions.layout.xaxis.tickmode = 'auto';
           baseChartOptions.layout.xaxis.nticks = 5;
       }
       for (let i = 0; i < axesLabels.length; i++) {
           baseChartOptions.layout[axesLabels[i]].nticks = 5;
       }
    }
    
    return Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, configs);
}
