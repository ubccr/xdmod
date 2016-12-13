/**
 * Internal operations summary panel.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Summary');

XDMoD.Summary.TabPanel = Ext.extend(Ext.TabPanel, {

    // TODO: This isn't the top tab panel anymore, so it should be renamed.
    id: 'top-tab-panel',

    frame: false,
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    constructor: function (config) {
        config = config || {};

        config.items = config.items || [];

        this.configStore = new XDMoD.Summary.ConfigStore();

        this.logLevelsStore = new XDMoD.Log.LevelsStore();

        XDMoD.Summary.TabPanel.superclass.constructor.call(this, config);
    },

    initComponent: function () {

        var loadMask = function () {
            if (!this.loadMask) {
                this.loadMask = new Ext.LoadMask(this.el, {msg: 'Loading...'});
            }
            this.loadMask.show();
        };
        this.on('afterlayout', loadMask);

        this.on('beforerender', function () {
            this.loadLogLevels({
                callback: function () {
                    this.loadConfig({
                        callback: function () {
                            this.add(this.configStore.getItems({
                                logLevelsStore: this.logLevelsStore
                            }));
                            this.doLayout();
                            this.setActiveTab(0);
                            this.un('afterlayout', loadMask);
                            if (this.loadMask) { this.loadMask.hide(); }
                        },
                        scope: this
                    });
                },
                scope: this
            });
        }, this, { single: true });

        XDMoD.Summary.TabPanel.superclass.initComponent.call(this);
    },

    loadLogLevels: function (options) {
        this.logLevelsStore.load(options);
    },

    loadConfig: function (options) {
        this.configStore.load(options);
    }
});

