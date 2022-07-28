const selectors ={
    tab : '#main_tab_panel__tg_usage',
    startField : '//div[@id="tg_usage"]//table[@class="x-toolbar-ct"]//input[contains(@id,"start_field_ext")]',
    endField : '//div[@id="tg_usage"]//table[@class="x-toolbar-ct"]//input[contains(@id,"end_field_ext")]',
    legendText : 'g.highcharts-legend-item',
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
    chart : '//div[@id="tg_usage"]//div[@class="highcharts-container"]//*[local-name() = "svg"]',
    chartByTitle : function (title) {
        return selectors.chart + '/*[name()="text" and contains(@class, "title")]/*[name()="tspan" and contains(text(),"' + title + '")]';
    },
    chartXAxisLabelByName : function (name) {
        return '(' + selectors.chart + '/*/*[name() = "tspan"])[2]';
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
}
export default selectors;
