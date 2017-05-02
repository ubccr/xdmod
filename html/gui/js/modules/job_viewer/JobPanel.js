Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

// =============================================================================
// Global Defaults                                                            ||
// =============================================================================

// default ids of various important containers within the job panel.
var DEFAULT_IDS = {
    delim             : '-',
    analyticsContainer: 'analytics_container',
    jobTabs           : 'job_tabs',
    help              : 'job_help'
};

// Helper objects for use with the Analytic Metrics display.
var ANALYTIC_METRICS = [
    {
        key   : 'CPU User Balance',
        id    : 'metric1',
        format: function (value) {
            return parseFloat(value).toFixed(3);
        }
    },
    {
        key   : 'Homogeneity',
        id    : 'metric2',
        format: function (value) {
            return parseFloat(value).toFixed(3);
        }
    },
    {
        key   : 'CPU User',
        id    : 'metric3',
        format: function (value) {
            return parseFloat(value).toFixed(3);
        }
    }
];

// =============================================================================
// Class Definition                                                           ||
// =============================================================================

/**
 * This component is used to represent and contain the information available
 * about a single HPC Jobs performance / execution. It provides a number of
 * vehicles for the user to view
 */
XDMoD.Module.JobViewer.JobPanel = Ext.extend(Ext.Panel, {

    _DEFAULTS: {
        closable: true,
        layout  : 'border',
        items   : [
            {
                region  : 'center',
                layout  : 'border',
                minWidth: 775,
                items   : [
                    {
                        title   : 'Analytics',
                        id      : 'analytics_container',
                        xtype   : 'portal',
                        region  : 'north',
                        hidden  : 'true',
                        height  : 174,
                        minWidth: 775
                    },
                    {
                        xtype      : 'tabpanel',
                        id         : 'job_tabs',
                        collapsible: false,
                        enableTabScroll: true,
                        region     : 'center',
                        flex       : 1,
                        defaults   : {
                            collapsible: false
                        },
                        bubbleEvents : ['tabchange'],
                        activeTab  : 0,
                        items      : []
                    },
                    {
                        title: 'Description',
                        id: DEFAULT_IDS.help,
                        region: 'south',
                        height: 100,
                        collapsible: true,
                        collapsed: false,
                        autoScroll: true,
                        layout: 'fit',
                        tpl: new Ext.XTemplate('<div class="jobviewer_helpcontent"><tpl if="title"><ul><li><strong>{title}:</strong> {documentation}</li></ul></tpl></div>'),
                        updateHelpText: function(data) {
                            this.update(this.tpl.apply(data));
                        },
                        plugins: new Ext.ux.collapsedPanelTitlePlugin('Help')
                    }
                ]
            }
        ] // items
    }, // DEFAULTS

    // ========================================================================
    // User Supplied Parameters                                              ||
    // ========================================================================

    // A parameter of type number that uniquely identifies the job that this
    // component is displaying.
    jobId: null,

    /**
     * Constructor method
     */
    initComponent: function () {
        var exists = CCR.exists;

        if (!exists(this.jobId)) throw new Error('Must provide a Job Id (jobId) when constructing this component.');

        this.ids = {};
        this.metrics = [];

        Ext.apply(this, this._DEFAULTS);

        XDMoD.Module.JobViewer.JobPanel.superclass.initComponent.apply(this, arguments);

        this._modifyIds(this.jobId);
        this._populateAnalytics(ANALYTIC_METRICS, this.jobId);

        this.revert = true;
    }, // initComponent

    /**
     * An object that contains a function per event that this component responds to.
     */
    listeners: {

        /**
         * Event that's fired when this panel is 'activated' ie. selected as the
         * active tab.
         *
         * @param panel
         */
        activate: function (panel) {
            var exists = CCR.exists;
            var toInt = CCR.toInt;

            var jv = panel.jobViewer;
            var activeTab = this._getActiveTab();
            activeTab = !exists(activeTab) ? this._getTab(0) : activeTab;

            var nodePath = panel.path;
            var windowPath = jv._getPath(document.location.hash);
            var activeTabPath = exists(activeTab) ? activeTab.path : windowPath;

            var path = panel.revert ? activeTabPath : windowPath;

            var nodeJobId = toInt(jv._find('jobid', 'jobid', nodePath));
            var pathJobId = toInt(jv._find('jobid', 'jobid', path));

            var replaceJobId = nodeJobId !== pathJobId;

            if (replaceJobId) {
                path = nodePath.concat(path.slice(-1 * (path.length - nodePath.length)));
            }
            if (panel.revert || replaceJobId) {
                // just make sure the token is up to date.
                var token = '#' + jv.module_id + '?' + jv._createHistoryTokenFromArray(path);
                Ext.History.add(token);
            } else {
                jv.searchHistoryPanel.fireEvent('expand_node', path);
            }
            panel.revert = true;
        }, // activate

        /**
         * Event that is fired after this component has been rendered to the
         * page. During this event we attempt to find the analyticsContainer.
         * Which, if found, will be refreshed visually. This serves to both
         * hide it if it is currently hidden or show it if it is currently
         * shown.
         *
         * @param panel
         */
        afterrender: function (panel) {
            var isType = CCR.isType;
            var found = this.find('id', this.ids.analyticsContainer);
            var container = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;
            if (container) {
                container.doLayout(false, true);
            }
        }, // afterrender

        /**
         * Update the analytics components with the provided data. If the
         * 'show' parameter is provided and true then the components will
         * be shown if hidden.
         *
         * @param {array} data
         * @param {boolean} show
         */
        update_analytics: function (data, show) {
            var isType = CCR.isType;

            var found = this.find('id', this.ids.analyticsContainer);
            var analyticsPanel = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;

            if (analyticsPanel) {
                if (show === true) {
                    analyticsPanel.show();
                    this.doLayout(false, true);
                }
                var metrics = data;
                for (var i = 0; i < metrics.length; i++) {
                    var metric = metrics[i];
                    var value = metric['value'];
                    for (var j = 0; j < this.metrics.length; j++) {
                        var am = this.metrics[j];
                        if (am['key'] === metric['key']) {
                            var formatted = am.format(value);
                            found = analyticsPanel.find('id', am['id']);
                            var cmp = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;
                            if (cmp) {
                                cmp.fireEvent('update_data', {
                                    name: am.key,
                                    value: metric.error == '' ? parseFloat(formatted) : 'N/A',
                                    error: metric.error
                                });
                                am.container.getTool('help').dom.qtip = metric.documentation;
                            }
                            break;
                        }
                    }
                }

                analyticsPanel.doLayout(false, true);
            }
        }, // update_analytics

        display_help: function (data) {
            var help = this._getComponent(DEFAULT_IDS.help);
            if (help) {
                help.updateHelpText(data);
            }
        },

        tabchange: function(tabpanel) {
            this.fireEvent("display_help", tabpanel.activeTab.helptext);
            return false;
        }
    }, // listeners

    // ========================================================================
    // Public Methods                                                        ||
    // ========================================================================

    // ========================================================================
    // Private Methods                                                       ||
    // ========================================================================

    /**
     * Modify the id's of the components of interest w/ the provided jobId. Also
     * updates this instances 'id' object with the modified value.
     *
     * @param {number} jobId
     * @private
     */
    _modifyIds: function (jobId) {
        var isType = CCR.isType;
        for (var key in DEFAULT_IDS) {
            var found = this.find('id', DEFAULT_IDS[key]);
            if (isType(found, CCR.Types.Array) && found.length > 0) {
                var child = found[0];
                this.ids[key] = child.id += (DEFAULT_IDS.delim + jobId);
            }
        }
    }, // modifyIds

    /**
     * Populate the Analytic Container with Metrics components.
     *
     * @param {array} metrics to be used to populate the container.
     * @param {number} jobid to be used in the correct identification of each metric component.
     * @private
     */
    _populateAnalytics: function (metrics, jobId) {
        var isType = CCR.isType;

        var found = this.find('id', this.ids.analyticsContainer);
        var analyticsPanel = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;

        for (var i = 0; i < metrics.length; i++) {

            var metric = metrics[i];
            var id = i + 1;
            var metricId = 'metric' + DEFAULT_IDS.delim + id + DEFAULT_IDS.delim + jobId;
            var cmp = new XDMoD.Module.JobViewer.AnalyticChartPanel({
                id          : metricId,
                height      : 174,
                chartOptions: {
                    title: 'No Data Available',
                    yAxis: {
                        min: 0,
                        max: 1
                    }
                }
            });

            var container = new Ext.Panel({
                columnWidth: .33,
                style      : 'padding:8px 0 8px 8px',
                title      : metric.key,
                height     : 146,
                id         : metricId + DEFAULT_IDS.delim + 'container',
                tools: [{
                    id: 'help',
                    qtip: metric.key
                }],
                items      : cmp
            });

            this.metrics[i] = Ext.apply({}, metric);
            this.metrics[i].component = cmp;
            this.metrics[i].container = container;
            this.metrics[i].id = metricId;

            analyticsPanel.add(container);
        }
    }, // _populateAnalytics

    /**
     * Helper function that retrieves this panels active informational tab.
     *
     * @returns {*}
     * @private
     */
    _getActiveTab: function () {
        var isType = CCR.isType;
        var tabs = this.find('id', this.ids.jobTabs);
        return isType(tabs, CCR.Types.Array) && tabs.length > 0 ? tabs[0].getActiveTab() : undefined;
    }, // _getActiveTab

    /**
     * Helper function that attempts to retrieve the informational tab located
     * at the provided index. If it is not found then undefined is returned.
     *
     * @param {Number} index
     * @returns {*}
     * @private
     */
    _getTab: function (index) {
        var isType = CCR.isType;
        if (!isType(index, CCR.Types.Number) || index < 0) return undefined;
        var found = this.find('id', this.ids.jobTabs);
        return isType(found, CCR.Types.Array) && found.length > 0 && found[0].items
                ? found[0].items.get(index)
                : undefined;
    }, // _getTab

    /**
     * Helper function that generates a unique id from the provided id and this
     * components jobId.
     *
     * @param id
     * @returns {string}
     * @private
     */
    _generateId: function (id) {
        var base = CCR.isType(id, CCR.Types.String) ? id : String(id);
        return base + DEFAULT_IDS.delim + this.jobId
    }, // _generateId

    /**
     *
     * @param {String} id
     * @return {Null|Ext.Component}
     * @private
     */
    _getComponent: function (id) {
        if (!CCR.exists(id)) return undefined;

        var localId = this._generateId(id);
        var found = this.find('id', localId);
        return CCR.isType(found, CCR.Types.Array) && found.length > 0
                ? found[0]
                : null;
    } // _getComponent
});
