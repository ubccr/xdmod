Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

// =============================================================================
// Class Definition                                                           ||
// =============================================================================

/**
 * This component is used to represent and contain the information available
 * about a single HPC Jobs performance / execution. It provides a number of
 * vehicles for the user to view
 */
XDMoD.Module.JobViewer.JobPanel = Ext.extend(Ext.Panel, {

    MAX_ANALYTICS: 6,

    COMPONENT_DEFAULTS: {
        closable: true,
        layout: 'border',
        items: [
            {
                itemId: 'analytics_container',
                xtype: 'panel',
                layout: 'hbox',
                layoutConfig: {
                    align: 'stretch'
                },
                defaults: {
                    margins: '0'
                },
                region: 'north',
                hidden: 'true',
                height: 95,
                minWidth: 775,
                items: []
            },
            {
                xtype: 'tabpanel',
                itemId: 'job_tabs',
                collapsible: false,
                enableTabScroll: true,
                region: 'center',
                flex: 1,
                defaults: {
                    collapsible: false
                },
                bubbleEvents: ['tabchange'],
                activeTab: 0,
                items: []
            },
            {
                title: 'Description',
                itemId: 'help',
                region: 'south',
                height: 100,
                collapsible: true,
                collapsed: false,
                autoScroll: true,
                layout: 'fit',
                tpl: new Ext.XTemplate('<div class="jobviewer_helpcontent"><tpl if="title"><ul><li><strong>{title}:</strong> {documentation}</li></ul></tpl></div>'),
                updateHelpText: function (data) {
                    this.update(this.tpl.apply(data));
                },
                plugins: new Ext.ux.collapsedPanelTitlePlugin('Help')
            }
        ]
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
        if (!this.jobId) {
            throw new Error('Must provide a Job Id (jobId) when constructing this component.');
        }

        for (var i = 0; i < this.MAX_ANALYTICS; i++) {
            this.COMPONENT_DEFAULTS.items[0].items[i] = {
                title: 'No Data Available',
                xtype: 'panel',
                margin: '0',
                padding: '1',
                flex: 1,
                itemId: 'metric' + i,
                tools: [{
                    id: 'help',
                    qtip: ''
                }],
                items: new XDMoD.Module.JobViewer.AnalyticChartPanel({
                    itemId: 'chart',
                    chartOptions: {
                        title: 'No Data Available'
                    }
                }),
                listeners: {
                    update_data: function (data) {
                        this.getComponent('chart').fireEvent('update_data', data);
                        this.getTool('help').dom.qtip = data.documentation;
                    },
                    resize: function (panel, adjWidth, adjHeight, rawWidth, rawHeight) {
                        this.getComponent('chart').fireEvent('resize', panel, adjWidth, adjHeight, rawWidth, rawHeight);
                    }
                }
            };
        }

        Ext.apply(this, this.COMPONENT_DEFAULTS);

        XDMoD.Module.JobViewer.JobPanel.superclass.initComponent.apply(this, arguments);

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

            var tabPanel = this.getComponent('job_tabs');
            var activeTab = tabPanel.getActiveTab();
            if (!activeTab) {
                activeTab = tabPanel.items.get(0);
            }

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
            var container = panel.getComponent('analytics_container');
            if (container) {
                container.doLayout(false, true);
            }
        }, // afterrender

        /**
         * Update the analytics components with the provided data.
         *
         * @param {array} data
         */
        update_analytics: function (data) {
            var analyticsPanel = this.getComponent('analytics_container');
            var i;
            var metricPanel;

            if (!analyticsPanel) {
                return;
            }

            // Shrink panel to available metric count
            if (data.length < this.MAX_ANALYTICS) {
                for (i = data.length; i < this.MAX_ANALYTICS; i++) {
                    analyticsPanel.remove('metric' + i);
                }
                this.MAX_ANALYTICS = data.length;
            }

            analyticsPanel.show();
            this.doLayout();

            for (i = 0; i < this.MAX_ANALYTICS; i++) {
                metricPanel = analyticsPanel.getComponent('metric' + i);
                metricPanel.fireEvent('update_data', {
                    name: data[i].key,
                    value: data[i].error === '' ? parseFloat(parseFloat(data[i].value).toFixed(3)) : 'N/A',
                    error: data[i].error,
                    documentation: data[i].documentation
                });
            }
        },

        display_help: function (data) {
            var help = this.getComponent('help');
            if (help) {
                help.updateHelpText(data);
            }
        },

        tabchange: function (tabpanel) {
            this.fireEvent('display_help', tabpanel.activeTab.helptext);
            return false;
        }
    }
});
