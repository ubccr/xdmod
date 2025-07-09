const selectors ={
    tab : '#main_tab_panel__tg_usage',
    startField : '//div[@id="tg_usage"]//table[@class="x-toolbar-ct"]//input[contains(@id,"start_field_ext")]',
    endField : '//div[@id="tg_usage"]//table[@class="x-toolbar-ct"]//input[contains(@id,"end_field_ext")]',
    legendText : function() {
        return selectors.chart0 + '/*[name()="g" and contains(@class, "infolayer")]/*[name()="g" and @class="legend"]'
    },
    refreshButton : '//div[@id="tg_usage"]//table[contains(@id, "refresh_button")]',
    toolbar : {
        exportButton: '//div[@id="tg_usage"]//button[text()="Export"]/ancestor::node()[5]'
    },
    panel : '//div[@id="tg_usage"]',
    availableForReportCheckbox : '//div[@id="tg_usage"]//label[text()="Available For Report"]/parent::node()/input[@type="checkbox"]',
    mask : '.ext-el-mask',
    topTreeNodeByName : function (name) {
        return '//div[@id="tg_usage"]//div[@class="x-tree-root-node"]/li/div[contains(@class,"x-tree-node-el")]//span[text() = "' + name + '"]';
    },
    treeNodeByPath : function (topname, childname) {
        return selectors.topTreeNodeByName(topname) + '/ancestor::node()[3]//span[text() = "' + childname + '"]';
    },
    unfoldTreeNodeByName : function (name) {
        return selectors.topTreeNodeByName(name) + '/ancestor::node()[2]/img[contains(@class,"x-tree-ec-icon")]';
    },
    chart : '//div[@id="tg_usage"]//div[contains(@class, "plot-container")]//*[local-name() = "svg"][1]',
    chart0: '//div[@id="tg_usage"]//div[contains(@class, "plot-container")]//*[local-name() = "svg"]',
    chartByTitle : function (title, zero=false) {
        let chart = zero ? selectors.chart0 : selectors.chart;
        return chart + '/*[name()="g" and contains(@class, "infolayer")]//*[name()="g" and contains(@class, "annotation") and @data-index="0"]//*[local-name() = "text" and contains(text(),"' + title +'")]';
    },
    chartXAxisLabelByName : function (name) {
        return '(' + selectors.chart + '//*[name()="g" and @class="xaxislayer-below"]/*[name()="g" and @class="xtick"])[1]';
    },
    durationButton : function(){
        return selectors.panel + '//button[contains(@class,"custom_date")]';
    },
    durationMenu : '//div[contains(@class,"x-menu-floating")]',
    durationMenuItem : function(name){
        return `${selectors.durationMenu}//li/a[./span[text()="${name}"]]`;
    },
    toolbarButtonByText : function (text) {
        return `//div[contains(@class, "x-toolbar")]//button[contains(text(), "${text}")]`;
    },
    displayMenuItemByText : function (text) {
        return `//div[@id='chart_config_menu_chart_toolbar_tg_usage']//span[contains(text(), '${text}')]//ancestor::li[contains(@class, 'x-menu-list-item')]`;
    },
    signInLink: '#sign_in_link',
    summaryChartLinkByName : function (text) {
        return `//div[@id="tg_usage"]//div[contains(@class, "chart_thumb")]//span[contains(text(), "${text}")]/..//a`
    }
}
export default selectors;
