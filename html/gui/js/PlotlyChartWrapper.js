/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Mar-07 (version 1)
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 * @author Andrew Stoltman
 * @date 2024-Feb-09 (version 3)
 *
 * Class to generate Plotly JS Chart. Also performs final configurations.
 */

Ext.namespace('XDMoD.utils');
/**
 * Determines if legend is located at the top of the chart.
 *
 * @param  {Object} layout - Plotly JS layout configuration object.
 * @return {boolean} if the legend is top center or not
 */
function topLegend(layout) {
    if (layout.legend) {
        return (layout.legend.xanchor === 'center'
                && layout.legend.yanchor === 'top'
                && layout.legend.yref !== 'paper');
    }
    return false;
}
/**
 * Word wrap subtitle text and adjust margin depending
 * on legend location and subtitle length.
 *
 * @param  {Object} layout - Plotly JS layout configuration object
 * @return {Integer} subtitle text line count
 */
function adjustTitles(layout) {
    if (layout.annotations && layout.annotations.length === 0) {
        return 0;
    }
    const subtitle = layout.annotations[1];
    const len = subtitle.text.length;
    let subtitleLineCount = 0;
    if (len > 0) {
        if (!layout.width) {
            layout.width = 1000; // default width -- need for custom queries because the width is not set for some reason
        }
        let axWidth = layout.width - layout.margin.l - layout.margin.r;
        if (layout.pieChart) {
            axWidth = layout.width / 1.1;
        }
        const subtitle_lines = CCR.xdmod.ui.lineSplit(subtitle.text, Math.trunc(axWidth / 7.5));
        layout.margin.t = 45 + (subtitle_lines.length * 15);
        layout.annotations[1].text = subtitle_lines.join('<br />');
        if (topLegend(layout)) {
            layout.legend.y = 0.95 - (0.025 * subtitle_lines.length);
            layout.annotations[1].yshift = (17 * subtitle_lines.length) * -1;
        }
        subtitleLineCount = subtitle_lines.length;
    } else if (topLegend(layout)) {
        layout.margin.t = 45 + 15;
    }

    return subtitleLineCount;
}

XDMoD.utils.createChart = function (chartOptions, extraHandlers) {
    const baseChartOptions = {};
    const configs = { displayModeBar: false, doubleClick: 'reset', doubleClickDelay: 500 };
    jQuery.extend(true, baseChartOptions, chartOptions);
    const isEmpty = (!baseChartOptions.data) || (baseChartOptions.data && baseChartOptions.data.length === 0);

    // Configure plot for 'No Data' image. We want to wipe the layout object except for a couple things
    if (isEmpty) {
        const errorConfig = getNoDataErrorConfig();
        const { width } = baseChartOptions.layout;
        const { height } = baseChartOptions.layout;
        // Save the title and subtitle except for thumbnail plots
        let margin;
        let titleAndSubtitle;
        if (baseChartOptions.layout.annotations) {
            margin = baseChartOptions.layout.margin;
            titleAndSubtitle = baseChartOptions.layout.annotations.slice(0, 2);
        }
        baseChartOptions.layout = errorConfig;
        baseChartOptions.layout.width = width;
        baseChartOptions.layout.height = height;
        baseChartOptions.layout.annotations = [];
        // Category chart summary view needs adjustments
        if (baseChartOptions.realmOverview) {
            baseChartOptions.layout.images[0].sizex = 4;
            baseChartOptions.layout.images[0].sizey = 4;
            baseChartOptions.layout.images[0].y = 2.5;
        } else if (margin && titleAndSubtitle) {
            baseChartOptions.layout.margin = margin;
            baseChartOptions.layout.annotations = titleAndSubtitle;
        }
    } else {
        if (baseChartOptions.metricExplorer) {
            configs.showTips = false;
        }

        if (baseChartOptions.data[0].type === 'pie') {
            baseChartOptions.layout.pieChart = true;
        }

        if (!baseChartOptions.credits && baseChartOptions.credits === false) {
            baseChartOptions.layout.annotations[2].text = '';
        }

        // Remove titles and credits from thumbnail plots
        if (baseChartOptions.layout.thumbnail) {
            const endIndex = baseChartOptions.layout.annotations.findIndex((elem) => elem.name === 'data_label');
            if (endIndex === -1) {
                baseChartOptions.layout.annotations = [];
            }
            baseChartOptions.layout.annotations.splice(0, endIndex);
        }
        // Set tickmode to auto for thumbnail plots. Large amount of tick labels for thumbnail plots cause them
        // to lag.
        if (baseChartOptions.layout.thumbnail && baseChartOptions.layout.xaxis.type !== 'category') {
            const axesLabels = getMultiAxisObjects(baseChartOptions.layout);
            if (baseChartOptions.swapXY) {
                baseChartOptions.layout.yaxis.nticks = 5;
            } else {
                baseChartOptions.layout.xaxis.nticks = 5;
            }
            for (let i = 0; i < axesLabels.length; i++) {
                baseChartOptions.layout[axesLabels[i]].nticks = 5;
            }
        }
        // Adjust trace ordering
        // Referenced https://stackoverflow.com/questions/45741397/javascript-sort-array-of-objects-by-2-properties
        // for comparison idea
        baseChartOptions.data.sort((trace1, trace2) => {
            if (baseChartOptions.layout.barmode !== 'group') {
                return Math.sign(trace2.zIndex - trace1.zIndex) || Math.sign(trace2.traceorder - trace1.traceorder);
            }
            const res = Math.sign(trace1.zIndex - trace2.zIndex) || Math.sign(trace1.traceorder - trace2.traceorder);
            return res;
        });
    }

    const chart = Plotly.newPlot(baseChartOptions.renderTo, baseChartOptions.data, baseChartOptions.layout, configs);
    const chartDiv = document.getElementById(baseChartOptions.renderTo);

    // Need this because of bug with Plotly JS pie chart automargin
    // which causes the chart to never fire the 'plotly_afterplot'
    // event. Therefore, we need to explictly call an event.
    if (chartDiv) {
        Plotly.relayout(baseChartOptions.renderTo, {});
    }

    chartDiv.once('plotly_relayout', (evt) => {
        if (baseChartOptions.layout.appkernels) {
            const appKernelsDiv = document.getElementById('app_kernels');
            const legendEntries = appKernelsDiv.getElementsByClassName('groups');
            for (let i = 0; i < legendEntries.length; i++) {
                for (let j = 0; j < legendEntries[i].children.length; j++) {
                    if (legendEntries[i].children[j].textContent === 'Change Indicator') {
                        const changeIndicatorIcon = legendEntries[i].children[j].children[1];
                        changeIndicatorIcon.innerHTML = '<image href="/gui/images/exclamation_ak.png" x="15" y="-12" width="20" height="20">';
                    }
                }
            }
        }

        if (baseChartOptions.layout.annotations.length === 0) {
            return;
        }
        const topCenter = topLegend(baseChartOptions.layout);
        const subtitleLineCount = adjustTitles(baseChartOptions.layout);
        const marginTop = (topCenter || subtitleLineCount > 0) ? baseChartOptions.layout.margin.t : chartDiv._fullLayout._size.t;
        const marginRight = chartDiv._fullLayout._size.r;
        const marginLeft = chartDiv._fullLayout._size.l;
        const legendHeight = (topCenter && !(baseChartOptions.layout.height <= 550)) ? chartDiv._fullLayout.legend._height : 0;
        const titleHeight = 31;
        const subtitleHeight = 15;
        let creditsHeight = 0;
        if (subtitleLineCount > 0) {
            creditsHeight = (15 * subtitleLineCount);
            if (topCenter) {
                creditsHeight -= 15;
            }
        }
        let titleIndex = -1;
        let subtitleIndex = -1;
        let creditsIndex = -1;
        let restrictedIndex = -1;

        for (let i = 0; i < baseChartOptions.layout.annotations.length; i++) {
            const name = baseChartOptions.layout.annotations[i].name;
            switch (name) {
                case 'title':
                    titleIndex = i;
                case 'subtitle':
                    subtitleIndex = i;
                case 'credits':
                    creditsIndex = i;
                case 'Restricted Data Warning':
                    restrictedIndex = i;
                default:
            }
        }
        const update = {};

        if (titleIndex > -1) {
            update[`annotations[${subtitleIndex}].yshift`] = (marginTop + legendHeight) - titleHeight;
        }

        if (subtitleIndex > -1) {
            update[`annotations[${subtitleIndex}].yshift`] = ((marginTop + legendHeight) - titleHeight) - (subtitleHeight * subtitleLineCount);
        }

        const marginBottom = chartDiv._fullLayout._size.b;
        const plotAreaHeight = chartDiv._fullLayout._size.h;
        let pieChartYShift = 0;
        let pieChartXShift = 0;
        if (chartDiv._fullData.length !== 0 && chartDiv._fullData[0].type === 'pie') {
            pieChartYShift = subtitleLineCount > 0 ? 30 : 0;
            pieChartXShift = subtitleLineCount > 0 ? 2 : 1;
        }

        if (creditsIndex > -1) {
            update[`annotations[${creditsIndex}].yshift`] = (plotAreaHeight + marginBottom) * -1 + creditsHeight - pieChartYShift;
            update[`annotations[${creditsIndex}].xshift`] = marginRight - pieChartXShift;
        }
        if (restrictedIndex > -1) {
           update[`annotations[${restrictedIndex}].yshift`] = (plotAreaHeight + marginBottom) * -1 + creditsHeight - pieChartYShift;
           update[`annotations[${restrictedIndex}].xshift`] = (marginLeft - pieChartXShift) * -1;
        }

        Plotly.relayout(baseChartOptions.renderTo, update);
    });

    return chart;
};
