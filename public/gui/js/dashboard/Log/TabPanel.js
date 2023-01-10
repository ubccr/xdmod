/**
 * Log tab panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.TabPanel = Ext.extend(Ext.TabPanel, {
    id: 'log-tab-panel',
    title: 'Log Data',
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    constructor: function (config) {
        config = config || {};

        if (Ext.isEmpty(config.logLevelsStore)) {
            this.logLevelsStore = new XDMoD.Log.LevelsStore();
        } else {
            this.logLevelsStore = config.logLevelsStore;
        }

        config.items = [];

        Ext.each(config.logConfigList, function (item) {
            item.logLevelsStore = this.logLevelsStore;

            config.items.push(new XDMoD.Log.GridPanel(item));
        }, this);

        XDMoD.Log.TabPanel.superclass.constructor.call(this, config);
    }
});

