Ext.namespace('XDMoD.UserManagement');

XDMoD.UserManagement.Panel = Ext.extend(Ext.TabPanel, {
    frame: false,
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    listeners: {
        beforerender: function(tabPanel) {
            tabPanel.initialize(tabPanel);
        }
    },

    /* ==========================================================================================
     * Initialize this module. This is not done directly in initComponent() so we don't
     * automatically load all of the stores until necessary upon rendering the component. This cuts
     * down on extraneous rest calls.
     * ==========================================================================================
     */

    initialize: function(tabPanel) {
        // Don't create the tab components until the tab is activated. Otherwise, the stores get
        // loaded sending several potentially unused rest requests.
        
        current_users = new XDMoD.CurrentUsers();
        var account_requests = new XDMoD.AccountRequests();
        var user_stats = new XDMoD.UserStats();
        
        tabPanel.add(account_requests);
        tabPanel.add(current_users);
        tabPanel.add(user_stats);
        tabPanel.activate(account_requests);
    }  // initialize()

});

