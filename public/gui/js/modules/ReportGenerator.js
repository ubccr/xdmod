/**
 * Report Generator Module
 *
 * @author Ryan Gentner
 */

Ext.namespace('XDMoD');

XDMoD.Module.ReportGenerator = Ext.extend(XDMoD.PortalModule, {
    module_id: 'report_generator',
    title: 'Report Generator',

    /**
     * Public reference to the store associated with the
     * XDMoD.AvailableCharts instance in this class (so that any chart
     * add/remove operation callbacks handled elsewhere in the portal
     * can directly reload the store as-needed.)
     */
    chartPoolStore: null,

    listeners: {
        beforerender: function(panel) {
            panel.initialize(panel);
        },
        load_report: function (reportId) {
            var tabPanel = Ext.getCmp('main_tab_panel');
            tabPanel.setActiveTab('report_generator');
            var reportGrid = this.find('itemId', 'reportQueueGrid');
            reportGrid[0].fireEvent('load_report', reportId);
        }
    }, 

    /* ==========================================================================================
     * Initialize this module. This is not done directly in initComponent() so we don't
     * automatically load all of the stores until necessary upon rendering the component. This cuts
     * down on extraneous rest calls.
     * ==========================================================================================
     */

    initialize: function(panel) {
        // Don't create the tab components until the tab is activated. Otherwise, the stores get
        // loaded sending several potentially unused rest requests.
        
        var reportManager = new XDMoD.ReportManager({
            region: 'center'
        });
        
        var chartPool = new XDMoD.AvailableCharts({
            region: 'east',
            split: true,
            width: 460,
            minSize: 460,
            maxSize:460
        });
        
        this.chartPoolStore = chartPool.reportStore;
        
        panel.add(reportManager);
        panel.add(chartPool);
    }
});

