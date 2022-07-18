const metricExplorerSelectors ={
    tab: '#main_tab_panel__metric_explorer',
    startDate: '#metric_explorer input[id^=start_field_ext]',
    endDate: '#metric_explorer input[id^=end_field_ext]',
    toolbar: {
        buttonByName: function (name){
            return '//div[@id="metric_explorer"]//table[@class="x-toolbar-ct"]//button[text()="' + name + '"]/ancestor::node()[5]';
        },
        addData: function (name){
            return '//div[@id="metric-explorer-chartoptions-add-data-menu"]//span[contains(text(), "' + name + '")]';
        },
        addDataGroupBy: function (groupBy){
            return "//div[contains(@class, 'x-menu')][contains(@class, 'x-menu-floating')][contains(@class, 'x-layer')][contains(@style, 'visibility: visible')]//span[contains(text(), '" + groupBy + "')]";
        }
    },
    container: '#metric_explorer',
    load: {
        button: function meLoadButtonId() {
            return 'button=Load Chart';
        },
        firstSaved: '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body .x-grid3-row-first',
        chartNum: function meChartByIndex(number) {
            var mynumber = number + 1;
            return '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body > div:nth-child(' + mynumber + ')';
        },
        dialog: '//div[contains(@class,"x-grid3-header-inner")]//div[contains(@class,"x-grid3-hd-name") and text() = "Chart Name"]/ancestor::node()[8]',
        chartByName: function (name) {
            return metricExplorerSelectors.load.dialog + '//div[contains(@class,"x-grid3-cell-inner") and text() = "' + name + '"]';
        }
    },
    newChart: {
        topMenuByText: function (name) {
            return '//div[@id="me_new_chart_menu"]//span[@class="x-menu-item-text" and text() = "' + name + '"]';
        },
        subMenuByText: function (topText, name) {
            return '//div[@id="me_new_chart_submenu_' + topText + '"]//span[@class="x-menu-item-text" and contains(text(),"' + name + '")]';
        },
        modalDialog: {
            box: '//span[@class="x-window-header-text" and text() = "New Chart"]/ancestor::node()[5]',
            textBox: function () {
                return metricExplorerSelectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-text")]';
            },
            checkBox: function () {
                return metricExplorerSelectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-checkbox")]';
            },
            ok: function () {
                return metricExplorerSelectors.newChart.modalDialog.box + '//button[text() = "Ok"]';
            },
            cancel: function () {
                return metricExplorerSelectors.newChart.modalDialog.box + '//button[text() = "Cancel"]';
            }
        }
    },
    dataSeriesDefinition: {
        dialogBox: '//div[contains(@class,"x-panel-header")]/span[@class="x-panel-header-text" and contains(text(),"Data Series Definition")]/ancestor::node()[4]',
        addButton: '#adp_submit_button'
    },
    deleteChart: {
        dialogBox: '//div[contains(@class,"x-window-header")]/span[@class="x-window-header-text" and contains(text(),"Delete Selected Chart")]/ancestor::node()[5]',
        buttonByLabel: function (label) {
            return metricExplorerSelectors.deleteChart.dialogBox + '//button[text()="' + label + '"]';
        }
    },
    addData: {
        button: '.x-btn-text.add_data',
        secondLevel: '.x-menu-floating:not(.x-hide-offsets):not(.x-menu-nosep)'
    },
    data: {
        button: 'button=Data',
        container: '',
        modal: {
            updateButton: 'button=Update',
            groupBy: {
                input: 'input[name=dimension]'
            }
        }
    },
    options: {
        aggregate: '#aggregate_cb',
        button: '#metric_explorer button.chartoptions',
        trendLine: '#me_trend_line',
        swap: '#me_chart_swap_xy',
        title: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] #me_chart_title'
    },
    chart: {
        svg: '//div[@id="metric_explorer"]//div[@class="highcharts-container"]//*[local-name() = "svg"]',
        titleByText: function (title) {
            return metricExplorerSelectors.chart.svg + '/*[name()="text" and contains(@class, "title")]/*[name()="tspan" and contains(text(),"' + title + '")]';
        },
        credits: function () {
            return metricExplorerSelectors.chart.svg + '/*[name()="text"]/*[name()="tspan" and contains(text(),"Powered by XDMoD")]';
        },
        yAxisTitle: function () {
            return metricExplorerSelectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-axis")]/*[name() = "text" and contains(@class,"highcharts-yaxis-title")]';
        },
        legend: function () {
            return metricExplorerSelectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-legend-item")]/*[name()="text"]';
        },
        seriesMarkers: function (seriesId) {
            return metricExplorerSelectors.chart.svg + '//*[local-name() = "g" and contains(@class, "highcharts-series-' + seriesId.toString() + '") and contains(@class, "highcharts-markers")]/*[local-name() = "path"]';
        },
        title: '#hc-panelmetric_explorer svg .undefinedtitle',
        titleInput: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] input[type=text]',
        titleOkButton: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility:     visible"] table.x-btn.x-btn-noicon.x-box-item:first-child button',
        titleCancelButton: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibili    ty: visible"] table.x-btn.x-btn-noicon.x-box-item:last-child button',
        contextMenu: {
            menuByTitle: function (title) {
                return '//div[contains(@class, "x-menu x-menu-floating") and contains(@style    , "visibility: visible;")]//span[contains(@class, "menu-title") and contains(text(), "' + title + '"    )]//ancestor::node()[4]/ul';
            },
            menuItemByText: function (menuTitle, itemText) {
                return metricExplorerSelectors.chart.contextMenu.menuByTitle(menuTitle) + '//li/a//span[text()="' + itemText + '"]';
            },
            container: '#metric-explorer-chartoptions-context-menu',
            legend: '#metric-explorer-chartoptions-legend',
            addData: '#metric-explorer-chartoptions-add-data',
            addFilter: '#metric-explorer-chartoptions-add-filter'
        },
        axis: '#metric_explorer .highcharts-yaxis-labels'
    },
    catalog: {
        panel: '//div[@id="metric_explorer"]//div[contains(@class,"x-panel")]//span[text()="Metric Catalog"]/ancestor::node()[2]',
        collapseButton: '//div[@id="metric_explorer"]//div[contains(@class,"x-panel")]//span[text()="Metric Catalog"]/ancestor::node()[2]//div[contains(@class,"x-tool-collapse-west")]',
        expandButton: '//div[@id="metric_explorer"]//div[contains(@class,"x-panel")]//div[contains(@class,"x-tool-expand-west")]',
        container: '#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder)',
        tree: '#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder) .x-tree-root-ct',
        rootNodeByName: function (name) {
            return '//div[@id="metric_explorer"]//div[@class="x-tree-root-node"]/li/div[contains(@class,"x-tree-node-el")]//span[text() = "' + name + '"]';
        },
        nodeByPath: function (topname, childname) {
            return metricExplorerSelectors.catalog.rootNodeByName(topname) + '/ancestor::node()[3]//span[text() = "' + childname + '"]';
        },
        addToChartMenu: {
            container: '//span[@class="x-menu-text"]/span[contains(text(),"Add To Chart:")]/ancestor::node()[3]',
            itemByName: function (name) {
                return metricExplorerSelectors.catalog.addToChartMenu.container + '//span[@class="x-menu-item-text" and text() = "' + name + '"]';
            }
        }
    },
    buttonMenu: {
        firstLevel: '.x-menu-floating:not(.x-hide-offsets)'
    },
    filters: {
        toolbar: {
            byName: function (name) {
                return '//div[@id="grid_filters_metric_explorer"]//div[contains(@class, "x-grid3-col-value_name") and contains(text(), "' + name + '")]/ancestor::node()[2]/td[contains(@class, "x-grid3-td-checked")]/div/div[contains(@class, "x-grid3-check-col-on")]';
            }
        }
    }
};
export default metricExplorerSelectors;
