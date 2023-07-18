const selectors = {
    mask: '.ext-el-mask',
    logoutLink: '#logout_link',
    logo: '.xtb-text.logo93',
    tab: '#main_tab_panel__metric_explorer',
    startDate: '//div[@id="metric_explorer"]//input[contains(@id,"start_field")]',
    endDate: '//div[@id="metric_explorer"]//input[contains(@id,"end_field")]',
    toolbar: {
        toolbars: function () {
            return selectors.container + ' .x-toolbar';
        },
        configureTime: {
            frameButton: "(//div[@id='main_tab_panel']//div[@id='metric_explorer']//table[@class='x-toolbar-ct']/tbody/tr/td[@class='x-toolbar-left']/table/tbody/tr[@class='x-toolbar-left-row']//tbody[@class='x-btn-small x-btn-icon-small-left'])[1]",
            UserDefinedSelect: '//div[@class="x-menu x-menu-floating x-layer x-menu-nosep"]//ul//li//a//span[text()="User Defined"]'
        },
        buttonByName: function (name){
            return '//div[@id="metric_explorer"]//table[@class="x-toolbar-ct"]//button[text()="' + name + '"]/ancestor::node()[5]';
        },
        saveChanges:'//span[@class="x-menu-item-text" and contains(text(),"Save Changes")]',
        addDataMenu: '//div[@id="metric-explorer-chartoptions-add-data-menu"]',
        addData: function (name){
            return '//div[@id="metric-explorer-chartoptions-add-data-menu"]//span[contains(text(), "' + name + '")]';
        },
        groupByMenu: '//div[@class="x-menu x-menu-floating x-layer"]',
        addDataGroupBy: function (groupBy){
            return "//div[contains(@class, 'x-menu')][contains(@class, 'x-menu-floating')][contains(@class, 'x-layer')][contains(@style, 'visibility: visible')]//span[contains(text(), '" + groupBy + "')]";
        },
        cannedDatePicker: function() {
            return selectors.container + ' table[id^=canned_dates]';
        }
    },
    container: '#metric_explorer',
    load: {
        button: function meLoadButtonId() {
            return 'button=Load Chart';
        },
        firstSaved: '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body .x-grid3-row-first',
        chartNum: function meChartByIndex(number) {
            const mynumber = number + 1;
            return '.x-menu-floating:not(.x-hide-offsets) .x-grid3-body > div:nth-child(' + mynumber + ')';
        },
        dialog: '//div[contains(@class,"x-grid3-header-inner")]//div[contains(@class,"x-grid3-hd-name") and text() = "Chart Name"]/ancestor::node()[8]',
        chartByName: function (name) {
            return selectors.load.dialog + '//div[contains(@class,"x-grid3-cell-inner") and text() = "' + name + '"]';
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
                return selectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-text")]';
            },
            checkBox: function () {
                return selectors.newChart.modalDialog.box + '//input[contains(@class,"x-form-checkbox")]';
            },
            ok: function () {
                return selectors.newChart.modalDialog.box + '//button[text() = "Ok"]';
            },
            cancel: function () {
                return selectors.newChart.modalDialog.box + '//button[text() = "Cancel"]';
            },
            noDataMessage: '//div[@class="x-grid-empty"]/b[text()="No data is available for viewing"]'
        }
    },
    dataSeriesDefinition: {
        dialogBox: '//div[contains(@class,"x-panel-header")]/span[@class="x-panel-header-text" and contains(text(),"Data Series Definition")]/ancestor::node()[4]',
        header: function(){
            return selectors.dataSeriesDefinition.dialogBox + '//div[contains(@class, "x-panel-header")]';
        },
        addButton: '#adp_submit_button',
        addFilter: function() {
            return selectors.dataSeriesDefinition.dialogBox + '//button[contains(@class, "add_filter") and text() = "Add Filter"]';
        },
        filterButton: function() {
            return selectors.dataSeriesDefinition.dialogBox + '//button[contains(@class, "filter") and contains(text(), "Filters")]';
        },
        box: '//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//td[contains(@class, "x-grid3-check-col-td")]',
        checkbox: '//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//div[contains(@class, "x-grid3-check-col-on")]',
        apply: '//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Apply")]',
        cancel: '//div[contains(@class, "x-menu x-menu-floating") and contains(@style,"visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Cancel")]',
        filter: function(filter) {
            return `//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//li/a//span[text()="${filter}"]`;
        },
        name: function(name) {
            return `//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//div[contains(text(), "${name}")]`;
        },
        ok: '//div[contains(@class, "x-menu x-menu-floating") and contains(@style, "visibility: visible;")]//button[@class=" x-btn-text" and contains(text(), "Ok")]',
    },
    filterMenu: {
        filterByDialogBox: '//div[contains(@class,"x-panel-header")]/span[@class="x-panel-header-text" and contains(text(),"Filter by")]/ancestor::node()[4]',
        addFilterMenuOption: function(filter) {
            return `//div[@id="metric-explorer-chartoptions-add-filter-menu"]//span[@class="x-menu-item-text" and text() = "${filter}"]`;
        },
        selectedCheckboxes: function() {
            return selectors.filterMenu.filterByDialogBox + '//div[@class="x-grid3-check-col x-grid3-cc-checked"]';
        },
        firstSelectedCheckbox: function() {
            return '(' + selectors.filterMenu.selectedCheckboxes() + ')[1]';
        },
        okButton: function() {
            return selectors.filterMenu.filterByDialogBox + '//button[@class=" x-btn-text" and contains(text(), "Ok")]';
        },
    },
    deleteChart: {
        dialogBox: '//div[contains(@class,"x-window-header")]/span[@class="x-window-header-text" and contains(text(),"Delete Selected Chart")]/ancestor::node()[5]',
        buttonByLabel: function (label) {
            return selectors.deleteChart.dialogBox + '//button[text()="' + label + '"]';
        }
    },
    addData: {
        button: '.x-btn-text.add_data',
        secondLevel: '.x-menu-floating:not(.x-hide-offsets):not(.x-menu-nosep)',
        secondLevelChild: function () {
            return selectors.addData.secondLevel + ' ul li:nth-child(3)';
        }
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
        menu: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"]',
        trendLine: '#me_trend_line',
        swap: '#me_chart_swap_xy',
        title: 'div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*="visibility: visible"] #me_chart_title'
    },
    chart: {
        svg: '//div[@id="metric_explorer"]//div[@class="highcharts-container"]//*[local-name() = "svg"]',
        subtitle: function() {
            return selectors.chart.svg + '//*[name()="text" and @class="undefinedsubtitle"]';
        },
        subtitleName: function(name) {
            return selectors.chart.subtitle() + `//*[contains(text(), "${name}")]`;
        },
        titleByText: function (title) {
            return selectors.chart.svg + '/*[name()="text" and contains(@class, "title")]/*[name()="tspan" and contains(text(),"' + title + '")]';
        },
        credits: function () {
            return selectors.chart.svg + '/*[name()="text"]/*[name()="tspan" and contains(text(),"Powered by XDMoD")]';
        },
        yAxisTitle: function () {
            return selectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-axis")]/*[name() = "text" and contains(@class,"highcharts-yaxis-title")]';
        },
        legend: function () {
            return selectors.chart.svg + '//*[name() = "g" and contains(@class, "highcharts-legend-item")]/*[name()="text"]';
        },
        firstLegendContent: function () {
            return '(' + selectors.chart.legend() + ')[1]';
        },
        legendContent: function(name){
            return selectors.chart.legend() + `//*[contains(text(), "${name}")]`;
        },
        seriesMarkers: function (seriesId) {
            return selectors.chart.svg + '//*[local-name() = "g" and contains(@class, "highcharts-series-' + seriesId.toString() + '") and contains(@class, "highcharts-markers")]/*[local-name() = "path"]';
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
                return selectors.chart.contextMenu.menuByTitle(menuTitle) + '//li/a//span[text()="' + itemText + '"]';
            },
            container: '#metric-explorer-chartoptions-context-menu',
            legend: '#metric-explorer-chartoptions-legend',
            addData: '#metric-explorer-chartoptions-add-data',
            addFilter: '#metric-explorer-chartoptions-add-filter'
        },
        axis: '#metric_explorer .highcharts-yaxis-labels',
        axisText: function () {
            return selectors.chart.axis + ' text';
        }
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
            return selectors.catalog.rootNodeByName(topname) + '/ancestor::node()[3]//span[text() = "' + childname + '"]';
        },
        addToChartMenu: {
            container: '//span[@class="x-menu-text"]/span[contains(text(),"Add To Chart:")]/ancestor::node()[3]',
            itemByName: function (name) {
                return selectors.catalog.addToChartMenu.container + '//span[@class="x-menu-item-text" and text() = "' + name + '"]';
            }
        }
    },
    buttonMenu: {
        firstLevel: '.x-menu-floating:not(.x-hide-offsets)',
        firstLevelChild: function () {
            return selectors.buttonMenu.firstLevel + ' ul li:nth-child(3)';
        }
    },
    filters: {
        grid: '//div[@id="grid_filters_metric_explorer"]',
        toolbar: {
            byName: function (name) {
                return '//div[@id="grid_filters_metric_explorer"]//div[contains(@class, "x-grid3-col-value_name") and contains(text(), "' + name + '")]/ancestor::node()[2]/td[contains(@class, "x-grid3-td-checked")]/div/div[contains(@class, "x-grid3-check-col-on")]';
            },
            apply: '//div[@id="grid_filters_metric_explorer"]//button[@class=" x-btn-text" and contains(text(), "Apply")]',
            checkBox: '//div[@id="grid_filters_metric_explorer"]//div[contains(@class, "x-grid3-check-col-on")]',
            firstCheckBox: '(//div[@id="grid_filters_metric_explorer"]//div[contains(@class, "x-grid3-check-col-on")])[1]',
            cancel: '//div[@id="grid_filters_metric_explorer"]//button[@class=" x-btn-text" and contains(text(), "Cancel")]',
        }
    },
    undo: '(//div[@id="metric_explorer"]//button[contains(@class, "x-btn-text-icon")])[1]'
};
export default selectors;
