/* eslint no-underscore-dangle: [
   "error",
   {
      "allow" : [
         "_getPath",
         "_processViewRequest",
         "_copy",
         "_createHistoryEntry",
         "_createHistoryToken",
         "_createHistoryTokenFromArray",
         "_find",
         "_fromArray",
         "_generateURL",
         "_generateView",
         "_getParams",
         "_makeRequest",
         "_panelActivation",
         "_performLoad",
         "_truncatePath",
         "_updateHistoryFromPanel",
         "_upsertSearch"
      ]
   }
] */

// TODO: Move this someplace else, just here for testing...
if (!String.prototype.trim) {
    String.prototype.trim = function () {
        return this.replace(/^\s+|\s+$/g, '');
    };
}

var exceptionhandler = function (proxy, type, action, exception, response) {
    switch (response.status) {
        case 403:
        case 500:
            var details = Ext.decode(response.responseText);
            Ext.Msg.alert("Error " + response.status + " " + response.statusText, details.message);
            break;
        case 401:
            // Do nothing
            break;
        default:
            Ext.Msg.alert(response.status + ' ' + response.statusText, response.responseText);
    }
};

/*
 * JobViewer panel
 * @author Joe White
 * @date 2014-09-23
 *
 */
XDMoD.Module.JobViewer = Ext.extend(XDMoD.PortalModule, {
    INSTANCE: null,

    // PORTAL MODULE PROPERTIES ===============================================
    title: 'Job Viewer',
    module_id: 'job_viewer',

    // PORTAL MODULE TOOLBAR CONFIG ===========================================
    usesToolbar: true,

    toolbarItems: {
        exportMenu: {
            enable: true,
            config: {
                allowedExports: ['png', 'svg', 'csv', 'pdf']
            }
        },
        printButton: true
    },

    // PROPERTIES =============================================================
    token: XDMoD.REST.token, /*NOTE: This is populated via PHP. So will this render only once? */
    timeSeriesURL: '/rest/supremm/explorer/hctimeseries/',
    optionWhiteList: ['host'],
    storePropertyWhiteList: ['jobid'],

    children_ids: ['nodeid', 'cpuid'],

    child_to_parent: {
        nodeid: 'tsid',
        cpuid: 'tsid'
    },

    tabpanel_id: 'info_display',
    analyticsContainerId: 'analytics_container',
    treeLoaded: false,
    parameters: ['realm', 'recordid', 'jobid', 'jobid', 'infoid', 'tsid'],
    rest: {
        warehouse: 'warehouse'
    },

    // DATA STORES ============================================================
    timeseriesstore: null,
    memusedstore: null,
    simdinsstore: null,
    membwstore: null,
    lnetstore: null,
    ib_lnetstore: null,
    accountdatastore: null,
    acctstore: null,
    mdatastore: null,
    jobrecordstore: null,
    store: null,

    /**
     * Find the active sub tab under the job tabs
     * @returns the component or false if non found
     */
    getActiveJobSubPanel: function () {
        var activeJobPanels = Ext.getCmp(this.tabpanel_id).getActiveTab().findByType('tabpanel');
        if (activeJobPanels.length < 1) {
            return false;
        }
        return activeJobPanels[0].getActiveTab();
    },

    /**
     * This tabs constructor, here we take care to setup everything this tab
     * will need throughout it's lifecycle.
     */
    initComponent: function () {
        // ROUTE: the unused toolbar events to a special no-opt function.
        //        Just to be sure that it doesn't go somewhere it's not
        //        supposed to.
        this.on('role_selection_change', this.noOpt);
        this.on('duration_change', this.noOpt);

        this.addEvents(
                'record_loaded',
                'data_account_loaded',
                'data_acct_loaded',
                'data_mdata_loaded',
                'data_jobrecord_loaded',
                'data_store_loaded'
        );

        // SETUP: the components for this tab and add them to this tabs 'items'
        // property.
        Ext.apply(this, {
            items: this.setupComponents(),
            customOrder: this.getToolbarConfig()
        });

        // Make sure to call the superclasses initComponent ( constructor )
        XDMoD.Module.JobViewer.superclass.initComponent.apply(this, arguments);

        this.loading = false;

        /*
         * The timezone setting for highcharts is a global option that applies
         * to all charts in the browser window. Individual charts in the job viewer
         * can change the timezone as appropriate. The job viewer tab resets the
         * timezone on deactivate to avoid impacting the highcharts plots in
         * other tabs in the interface. The timezone settings are cached in the
         * tab so that when it is activated again they are restored.
         */
        this.cachedHighChartTimezone = null;

    }, // initComponent

    /**
     * Apply implements an immutable merge (ie. returns a new object containing
     * the properties of both objects.) of two javascript objects,
     * lhs ( left hand side) and rhs ( right hand side). A property that exists
     * in both lhs and rhs will default to the value of rhs.
     *
     * @param {object} lhs Left hand side of the merge.
     * @param {object} rhs Right hand side of the merge.
     *
     **/
    apply: function (lhs, rhs) {
        if (typeof lhs === 'object' && typeof rhs === 'object') {
            var results = {};
            for (var property in lhs) {
                if (lhs.hasOwnProperty(property)) {
                    results[property] = lhs[property];
                }
            }
            for (property in rhs) {
                if (rhs.hasOwnProperty(property)) {
                    var rhsExists = rhs[property] !== undefined
                            && rhs[property] !== null;
                    if (rhsExists) {
                        results[property] = rhs[property];
                    }
                }
            }
            return results;
        }
        return lhs;
    },// apply

    /**
     * Helper function that handles setting up this components toolbar.
     */
    getToolbarConfig: function () {
        var self = this;

        var searchPanel = new XDMoD.Module.JobViewer.SearchPanel({
            id: 'job-viewer-search-panel',
            jobViewer: this,
            token: self.token
        });

        self.searchHistoryPanel.relayEvents(searchPanel, ['reload_root']);
        self.searchHistoryPanel.relayEvents(this, ['reload_root']);
        searchPanel.relayEvents(self, ['edit_search']);

        self.searchWindow = new Ext.Window({
            id: 'search-window',
            closable: true,
            closeAction: 'hide',
            modal: true,
            title: 'Search',
            layout: 'fit',
            resizable: true,
            boxMaxHeight: 641,
            boxMaxWidth: 1014,
            items: [searchPanel]
        });
        searchPanel.relayEvents(self.searchWindow, ['show', 'hide', 'move']);

        var searchButton = new Ext.Button({
            text: 'Search',
            iconCls: 'search',
            tooltip: 'Search for some subset of jobs',
            handler: function (b) {
                self.searchWindow.show();
            }
        });

        return [
            searchButton,
            {
                item: ' ',
                separator: false
            },
            XDMoD.ToolbarItem.EXPORT_MENU,
            XDMoD.ToolbarItem.PRINT_BUTTON
        ];
    },

    /**
     * Build the components that will make up this tabs UI. Return an array of the top level components for display in a
     * 'border' layout ( note: one of the returned components *must* have 'region: center' as a property ).
     *
     * @returns {Array} of components to display as this tabs UI.
     */
    setupComponents: function () {
        var self = this;

        self.sortMode = 'age';

        // SEARCH FORM ( CHILD OF BASIC SEARCH / NAVIGATION ) ==================
        self.searchHistoryPanel = new XDMoD.Module.JobViewer.SearchHistoryPanel({
            id: 'jobviewer_search_history_panel',
            jobViewer: this,
            region: 'center',
            listeners: {
                data_loaded: function (data) {
                    self.treeLoaded = true;
                    if (self.historyEventWaiting) {
                        self.createHistoryCallback.call(self.createHistoryCallbackScope, self.createHistoryCallbackData);
                    }
                }
            }
        });

        self.treeSorter = new Ext.tree.TreeSorter(self.searchHistoryPanel, {
                folderSort: false,
                dir: "asc",
                sortType: function(value, node) {
                    if (node.attributes.dtype == 'recordid') {
                        if (self.sortMode == 'age') {
                            return 9007199254740991 - parseInt(node.attributes.recordid, 10);
                        } else {
                            return node.attributes.text;
                        }
                    } else {
                        return node.attributes[node.attributes.dtype];
                    }
                }
        });

        // NAVIGATION ( PARENT WESTERN PANEL ) =================================
        var searchHistory = new Ext.Panel({
            region: 'west',
            title: 'Search History',
            collapsible: true,
            collapsed: false,
            layout: 'card',
            activeItem: 0,
            collapseFirst: false,
            pinned: false,
            plugins: new Ext.ux.collapsedPanelTitlePlugin('Navigation'),
            width: 250,
            items: [
                new CCR.xdmod.ui.AssistPanel({
                    region: 'center',
                    border: true,
                    headerText: 'No saved searches',
                    subHeaderText: 'Use the search button above to find jobs',
                    userManualRef: 'job+viewer'
                }),
                self.searchHistoryPanel
            ],
            listeners: {
                collapse: function (p) {

                },
                history_exists: function (hasHistory) {
                    this.getLayout().setActiveItem(hasHistory ? 1 : 0);
                },
                expand: function (p) {
                    if (p.pinned) {
                        p.getTool('pin').hide();
                        p.getTool('unpin').show();
                    } else {
                        p.getTool('pin').show();
                        p.getTool('unpin').hide();
                    }
                }
            },
            tools: [{
                id: 'pin',
                qtip: 'Prevent auto hiding of the Search History',
                hidden: false,
                handler: function (ev, toolEl, p, tc) {
                    p.pinned = true;
                    if (p.collapsed) {
                        p.expand(false);
                    }
                    p.getTool('pin').hide();
                    p.getTool('unpin').show();
                }
            }, {
                id: 'unpin',
                qtip: 'Allow auto hiding of the Search History',
                hidden: true,
                handler: function (ev, toolEl, p, tc) {
                    p.pinned = false;
                    p.getTool('pin').show();
                    p.getTool('unpin').hide();
                }
            }]
        }); // navbar ==========================================================

        var tabPanel = new Ext.TabPanel({
            id: 'info_display',
            enableTabScroll: true,
            region: 'center'
        });

        var assistPanel = new CCR.xdmod.ui.AssistPanel({
            region: 'center',
            border: false,
            headerText: 'No job is selected for viewing',
            subHeaderText: 'Please refer to the instructions below:',
            graphic: 'gui/images/job_viewer_instructions.png',
            userManualRef: 'job+viewer'
        });

        var viewPanel = new Ext.Panel({
            id: 'info_display_container',
            frame: false,
            layout: 'card',
            border: false,
            activeItem: 0,
            region: 'center',
            items: [assistPanel, tabPanel]
        });

        // RETURN: an array of the top level components. To be used as the
        //         'items' for another component with a border layout.
        return new Array(
                searchHistory,// WEST
                viewPanel     // CENTER
        );
    }, // setupComponents

    /**
     * Helper function that formats the provided val based on the provided units.
     *
     * @param val
     * @param units
     * @returns {*}
     */
    formatData: function (val, units) {
        var formatBytes = function (value, unitname, precision) {
            if (value < 0) {
                return 'NA';
            }
            return XDMoD.utils.format.convertToBinaryPrefix(value, unitname, precision);
        };

        switch (units) {
            case "TODO":
            case "":
            case null:
                return val;
                break;
            case "seconds":
                return XDMoD.utils.format.humanTime(val);
                break;
            case "boolean":
                return (val == 1) ? "True" : "False";
                break;
            case "packets":
            case "messages":
                return Math.ceil(val);
                break;
            case "ratio":
            case "1":
                return XDMoD.utils.format.convertToSiPrefix(val, '', 3);
                break;
            case 'bytes':
            case 'B':
            case 'B/s':
                return formatBytes(val, units, 4);
                break;
            case 'kilobyte':
                return formatBytes(val * 1024.0, 'byte', 4);
                break;
            case 'megabyte':
                return formatBytes(val * 1024.0 * 1024.0, 'byte', 4);
                break;
            case 'joules':
                return Ext.util.Format.number(val / 3.6e6, '0,000.000') + ' kWh';
            default:
                return XDMoD.utils.format.convertToSiPrefix(val, units, 4);
                break;
        }
    }, // formatData

    /**
     * This is a marker function that is used to explicitly indicate that
     * certain events are to take no action.
     */
    noOpt: function () {
        /** NO-OPT, what did you expect? **/
    }, // noOpt

    /**
     * Helper function that retrieves the requested parameter from the provided
     * source string via the provided name.
     *
     * @param name that will be used when looking for the the parameter in
     *             source.
     * @param source that will be used to search for parameter 'name'.
     * @returns {String} an empty string if not found, else the value of the
     *                   parameter.
     */
    getParameterByName: function (name, source) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(source);
        return results === null
                ? ""
                : decodeURIComponent(results[1].replace(/\+/g, " "));
    }, // getParameterByName

    /**
     * Generate a URL based on the provided base path. This URL will include
     * the required XDMoD REST token.
     *
     * @param {String} base
     * @param {Array} path
     * @returns {String}
     * @private
     */
    _generateURL: function (base, path) {

        var isType = CCR.isType;
        var encode = CCR.encode;
        if (isType(base, CCR.Types.String) && isType(path, CCR.Types.Array)) {
            var params = this._getParams(path);
            var encoded = encode(params);
            var result = base + '?' + encoded + '&token=' + XDMoD.REST.token;
            return result;
        }
        return base;
    }, // _generateURL

    /**
     * Helper function that will generate a new view or informational tab based
     * on the 'attributes.type' property.
     *
     * @param {Object} attributes
     * @param {Array}  path
     * @param {String} title
     * @param {Ext.tree.TreeNode} parent
     * @return {*}
     * @private
     */
    _generateView: function (attributes, path, title, parent) {
        var self = this;
        var exists = CCR.exists;

        var dtype = attributes['dtype'];
        var id = attributes[dtype];
        var jobId = attributes['jobid'];

        var uniqueId = [dtype, id].join('_');

        var nestedId = [uniqueId, jobId, 'nested'].join('_');
        var textId = [uniqueId, jobId, 'text'].join('_');
        var kvId = [uniqueId, jobId, 'kv'].join('_');
        var kvStoreId = [uniqueId, jobId, 'kv', 'store'].join('_');
        var metricsId = [uniqueId, jobId, 'metrics'].join('_');
        var chartId = [uniqueId, jobId, 'chart'].join('_');

        var valueColumnId = [uniqueId, 'column', 'value'].join('_');
        var nestedValueColumnId = [uniqueId, 'nested', 'column', 'value'].join('_');

        var type = attributes.type;
        var base = attributes.url;

        var tab;
        var url = self._generateURL(base, path);
        switch (type) {
            case 'nested':
                tab = new XDMoD.Module.JobViewer.NestedViewPanel({
                    updateHistory: true,
                    preferWindowPath: true,
                    id: nestedId,
                    dtypes: [],
                    dtype: dtype,
                    dtypeValue: id,
                    jobId: jobId,
                    path: path,
                    title: title,
                    dataUrl: url,
                    autoExpandColumn: nestedValueColumnId,
                    columns: [
                        {"header": "Key", "dataIndex": "key", mapping: "key", "width": 250},
                        {"header": "Value", "dataIndex": "value", mapping: "value", id: nestedValueColumnId}
                    ]

                });
                break;
            case 'utf8-text':
                tab = new Ext.Panel({
                    id: textId,
                    dtypes: [],
                    dtype: dtype,
                    dtypeValue: id,
                    jobId: jobId,
                    path: path,
                    closable: false,
                    updateHistory: true,
                    preferWindowPath: true,
                    title: title,
                    flex: 1,
                    layout: 'fit',

                    items: [
                        {
                            id: 'jobscript',
                            html: 'Loading',
                            autoScroll: true,
                            layout: 'fit',
                            url: url,
                            listeners: {
                                afterrender: function (panel) {
                                    this._performLoad(this.url, panel);
                                }
                            },
                            _performLoad: function (url, panel) {
                                Ext.Ajax.request({
                                    url: url,
                                    success: function (response) {
                                        if (response && response.responseText) {
                                            var exists = CCR.exists;

                                            var json = JSON.parse(response.responseText);
                                            var success = exists(json) && exists(json.success) && json.success;
                                            var data = json.data || [];
                                            var hasData = exists(data) && data.length > 0;

                                            if (success && hasData) {
                                                panel.update('<pre>' + json.data[0].value + '</pre>');
                                            } else if (success && !hasData) {
                                                panel.update('<pre> No Data Retrieved.</pre>');
                                            } else if (!success) {
                                                panel.update('<pre> An error occurred while attempting to perform the requested operation.</pre>');
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    ]

                });
                break;
            case 'metrics':
            case 'keyvaluedata':
                tab = new Ext.grid.GridPanel({
                    dtypes: [],
                    title: title,
                    layout: 'fit',
                    dtype: dtype,
                    dtypeValue: id,
                    jobId: jobId,
                    path: path,
                    id: kvId,
                    closable: false,
                    updateHistory: true,
                    preferWindowPath: true,
                    height: '100%',
                    width: '100%',
                    store: new Ext.data.GroupingStore({
                        id: kvStoreId,
                        url: url,
                        autoLoad: true,
                        proxy: new Ext.data.HttpProxy({
                            api: {
                                read: {
                                    url: url,
                                    method: 'GET'
                                }
                            }
                        }),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            successProperty: 'success',
                            fields: [
                                {name: 'key', mapping: 'key', type: 'string'},
                                {name: 'value', mapping: 'value', type: 'string'},
                                {name: 'units', mapping: 'units', type: 'string'},
                                {name: 'group', mapping: 'group', type: 'string'},
                                {name: 'help', mapping: 'help', type: 'string'},
                                {name: 'documentation', mapping: 'documentation', type: 'string'}
                            ]
                        }),
                        groupField: 'group'
                    }),
                    columnLines: true,
                    flex: 2,
                    autoExpandColumn: valueColumnId,
                    columns: [
                        {
                            "header": "Key",
                            "width": 250,
                            "sortable": true,
                            "dataIndex": "key",
                            renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                var help = record.get('documentation');
                                metadata.attr = 'ext:qtip="' + help + '"';
                                return value;
                            }
                        },
                        {
                            "header": "Value",
                            "width": 150,
                            "sortable": true,
                            dataIndex: 'value',
                            id: valueColumnId,
                            renderer: function (value, metadata, record) {
                                var fmt = String(self.formatData(value, record.get('units')));
                                if (fmt === 'NA') {
                                    metadata.attr = 'ext:qtip="This information is not available"';
                                } else if (fmt.indexOf(value) !== 0) {
                                    metadata.attr = 'ext:qtip="' + value + ' ' + record.get('units') + '"';
                                }
                                return fmt;
                            },
                            editor: new Ext.form.TextField({
                                allowBlank: false
                            })
                        },
                        {
                            "header": "Category",
                            "hidden": true,
                            "dataIndex": "group"
                        }
                    ],
                    view: new Ext.grid.GroupingView({
                        forceFit:true,
                        groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',

                        /* To enable users to be able to copy / paste text from this component we need to override the
                           template used to render each cell. Specifically we needed to remove the `x-unselectable`
                           class, the `unselectable="on"` attribute, and to be ensure that we're being explicit in our
                           intentions for this content, I've added a new css class that specifically enables text
                           selection.
                         */
                        cellTpl: new Ext.Template(
                            '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} {css}" style="{style}" tabIndex="0" {cellAttr}>',
                            '<div class="x-grid3-cell-inner x-grid3-col-{id} selectable-text" {attr}>{value}</div>',
                            '</td>'
                        )
                    })
                });
                break;
            case 'detailedmetrics':
                var renderValueWithUnits = function(value, node) {
                    if (typeof value === 'undefined') {
                        return '';
                    }
                    if (node.unit) {
                        return self.formatData(value, node.unit);
                    }
                    return value;
                };
                tab = new Ext.ux.tree.TreeGrid({
                    id: metricsId,
                    dtypes: [],
                    dtype: dtype,
                    dtypeValue: id,
                    jobId: jobId,
                    path: path,
                    closable: false,
                    updateHistory: true,
                    preferWindowPath: true,
                    title: title,
                    autoScroll: true,
                    columnResize: false,
                    enableDD: true,
                    columns: [
                        {
                            header: "Device",
                            dataIndex: "name",
                            width: 225,
                            tpl: new Ext.XTemplate('{name:this.render}', {
                                render: function(value, node) {
                                    if (node.documentation) {
                                        return '<span ext:qtip="' + node.documentation + '">' + value + '</span>';
                                    }
                                    return value;
                                }
                            })
                        },
                        {
                            header: "Average",
                            dataIndex: "avg",
                            width: 125,
                            tpl: new Ext.XTemplate('{avg:this.render}', {
                                render: renderValueWithUnits
                            })
                        },
                        {header: "Count", dataIndex: "cnt", "width": 65},
                        {header: "Standard Dev.", dataIndex: "std", "width": 125},
                        {
                            header: "Median",
                            dataIndex: "med",
                            width: 125,
                            tpl: new Ext.XTemplate('{med:this.render}', {
                                render: renderValueWithUnits
                            })
                        },
                        {header: "Skew", dataIndex: "skw", "width": 125},
                        {
                            header: "Minimum",
                            dataIndex: "min",
                            width: 125,
                            tpl: new Ext.XTemplate('{min:this.render}', {
                                render: renderValueWithUnits
                            })
                        },
                        {
                            header: "Maximum",
                            dataIndex: "max",
                            width: 125,
                            tpl: new Ext.XTemplate('{max:this.render}', {
                                render: renderValueWithUnits
                            })
                        },
                        {header: "Coefficient of variation", dataIndex: "cov", "width": 125},
                        {header: "Kurtosis", dataIndex: "krt", "width": 125}
                    ],
                    dataUrl: url
                });
                break;
            case 'ganttchart':
                tab = new XDMoD.Module.JobViewer.GanttChart({
                    id: chartId,
                    title: title,
                    url: base,
                    baseParams: this._getParams(path),
                    historyToken: '#' + this.module_id + '?' + this._createHistoryTokenFromArray(path),
                    path: path,
                    dtypes: [],
                    dtype: dtype,
                    dtypeValue: id
                });
                break;
            case 'timeline':
            case 'timeseries':
                var tsid = this._find('tsid', 'tsid', path);
                if (exists(tsid)) {
                    title = this._find('text', 'tsid', this.currentNode);
                    dtype = this._find('dtype', 'tsid', this.currentNode);
                    id = this._find(dtype, 'tsid', this.currentNode);
                }
                tab = new XDMoD.Module.JobViewer.ChartPanel({
                    id: chartId,
                    dtypes: [],
                    dtype: dtype,
                    dtypeValue: id,
                    jobId: jobId,
                    path: path,
                    closable: false,
                    preferWindowPath: true,
                    updateHistory: true,
                    title: title,
                    layout: 'fit',
                    jobViewer: self,
                    jobTab: parent,
                    baseUrl: base,
                    store: new Ext.data.JsonStore({
                        proxy: new Ext.data.HttpProxy({url: url}),
                        autoLoad: true,
                        root: 'data',
                        fields: ["series", "schema"]
                    })
                });
                break;
            case 'vmstate':
                tab = new XDMoD.Module.JobViewer.VMStateChartPanel({
                  id: chartId,
                  title: title,
                  url: base,
                  baseParams: this._getParams(path),
                  historyToken: '#' + this.module_id + '?' + this._createHistoryTokenFromArray(path),
                  path: path,
                  dtypes: [],
                  dtype: dtype,
                  dtypeValue: id,
                  store: new Ext.data.JsonStore({
                      proxy: new Ext.data.HttpProxy({ url: url }),
                      autoLoad: true,
                      root: 'data',
                      fields: ['series', 'schema']
                  })
                });
                break;
            case 'analytics':
                Ext.Ajax.request({
                    dtype: dtype,
                    dtypeValue: id,
                    url: url,
                    method: 'GET',
                    success: function (response) {
                        var data = JSON.parse(response.responseText);
                        var success = exists(data) && exists(data.success) && data.success;
                        if (success) {
                            parent.fireEvent('update_analytics', data.data, true);
                        }
                    },
                    failure: function (data) {
                    }
                });
                break;
            default:
                break;
        }
        if (exists(tab)) {
            tab.addListener('activate', self._panelActivation, self);
            tab.helptext = {
                title: tab.title,
                documentation: attributes.documentation
            };
        }
        return tab;
    }, // _generateView

    listeners: {

        /**
         * Fired when this tab is either clicked on or activated.
         *
         * @param panel
         **/
        activate: function () {
            if (!this.loadMask) {
                this.getExportMenu().setDisabled(true);
                this.getPrintButton().setDisabled(true);
                this.loadMask = new Ext.LoadMask(this.id);
            }
            Highcharts.setOptions({ global: { timezone: this.cachedHighChartTimezone } });

            if (this.clearing) {
                return;
            }

            var token = CCR.tokenize(document.location.hash);
            var params = Ext.urlDecode(token.params);

            if (params.job) {
                this.loadMask.show();
                this.fireEvent('create_history_entry', Ext.decode(window.atob(params.job)));
                return;
            }

            if (!params.realm) {
                return;
            }

            if (params.action) {
                this.loadMask.show();
                this.fireEvent('run_search_action', params);
                return;
            }

            this.loadMask.hide();

            var selectionModel = this.searchHistoryPanel.getSelectionModel();

            var path = this._getPath(token.raw);
            var isSelected = this.compareNodePath(this.currentNode, path) && selectionModel && CCR.exists(selectionModel.getSelectedNode());

            if (!isSelected) {
                this.searchHistoryPanel.fireEvent('expand_node', path);
                return;
            }

            if (params.recordid && params.jobid) {
                this.getExportMenu().setDisabled(!params.tsid);
                this.getPrintButton().setDisabled(!params.tsid);
                if (params.infoid) {
                    this.fireEvent('process_view_node', path);
                } else {
                    this.fireEvent('process_job_node', path, this._processViewRequest);
                }
            }
        }, // activate

        deactivate: function() {
            this.cachedHighChartTimezone = Highcharts.getOptions().global.timezone;
            Highcharts.setOptions({global: {timezone: null}});
        },

        /**
         * Takes care of clearing the whole informational display area.
         * This includes the job tab panel and the analytics container.
         * Also, since we just removed the whole display area, go ahead and
         * reload the search history root. This will also ensure that our
         * History Token is in sync with what the user is currently viewing,
         * which is nothing.
         */
        clear_display: function () {
            this.clearing = true;

            var tabs = Ext.getCmp(this.tabpanel_id);
            var analytics = Ext.getCmp(this.analyticsContainerId);

            // IF: the analytics is showing then hide it.
            if (analytics && !analytics.hidden) analytics.hide();

            // REMOVE: all of the tabs.
            tabs.removeAll();

            // FORCE: the container panel to re-lay itself out.
            tabs.ownerCt.doLayout(false, true);

            this.fireEvent('reload_root');

            this.clearing = false;
        }, // clear_display

        export_option_selected: function (exportParams) {
            var chartPanel = this.getActiveJobSubPanel();
            if (chartPanel) {
                chartPanel.fireEvent('export_option_selected', exportParams);
            }
        },

        print_clicked: function () {
            var chartPanel = this.getActiveJobSubPanel();
            if (chartPanel) {
                chartPanel.fireEvent('print_clicked');
            }
        },

        /**
         * Process the given path for a job node history event.
         *
         * @param {Array} path
         * @param {Boolean} isSelected
         */
        process_job_node: function (path, callback) {
            var self = this;
            var exists = CCR.exists;
            var isType = CCR.isType;

            var hasCurrentNode = exists(this.currentNode);
            var hasAttributes = hasCurrentNode && exists(this.currentNode.attributes);

            if (!hasCurrentNode) {
                console.log('No node... crying now ;-(');
            } else if (hasCurrentNode && hasAttributes) {

                var tabs = Ext.getCmp(this.tabpanel_id);

                var jobId = this._find('jobid', 'jobid', path);
                var title = this._find('text', 'jobid', this.currentNode);

                var jobPath = this._truncatePath('jobid', path);

                if (exists(jobId)) {

                    jobId = parseInt(jobId);

                    var found = tabs.find('jobId', jobId);
                    if (isType(found, CCR.Types.Array) && found.length > 0) {
                        var tab = found[0];
                        tabs.activate(tab);
                    } else {

                        var jobTab = new XDMoD.Module.JobViewer.JobPanel({
                            itemId: 'jobid_' + jobId.toString(),
                            jobViewer: self,
                            jobId: jobId,
                            title: title,
                            path: jobPath,
                            listeners: {
                                beforeshow: function() {
                                    // Set the tab panel to be active since a new tab is to be added.
                                    Ext.getCmp('info_display_container').getLayout().setActiveItem(1);
                                },
                                destroy: function () {
                                    if (Ext.getCmp('info_display').items.length < 1) {
                                        // All tabs have been destroyed. Set the assist image to be active
                                        Ext.getCmp('info_display_container').getLayout().setActiveItem(0);
                                    }
                                }
                            }
                        });

                        tabs.add(jobTab);
                        tabs.activate(jobTab);

                        var base = this._find('url', 'jobid', this.currentNode) || this.searchHistoryPanel.url;

                        var params = this._getParams(jobPath);
                        var encoded = CCR.encode(params);
                        var url = base + '?' + encoded + '&token=' + XDMoD.REST.token;
                        Ext.Ajax.request({
                            url: url,
                            method: 'GET',
                            success: function (response) {

                                var data = JSON.parse(response.responseText);
                                var success = exists(data) && exists(data.success) && data.success;

                                if (success) {
                                    var views = data.results;

                                    var jobTabs = jobTab.getComponent('job_tabs');

                                    var tab;
                                    for (var i = 0; i < views.length; i++) {
                                        var view = views[i];
                                        view['jobid'] = jobId;

                                        var dtype = view['dtype'];
                                        var id = view[dtype];


                                        var jobPath = self._copy(path, [], true);
                                        jobPath[jobPath.length] = {dtype: dtype, value: id};

                                        var currentViews = exists(view.text) ? jobTabs.find('title', view.text) : null;
                                        var viewsExists = exists(currentViews) && exists(currentViews.length) && currentViews.length > 0;

                                        if (!viewsExists) {
                                            tab = self._generateView(view, jobPath, view.text, jobTab);
                                            if (exists(tab)) jobTabs.add(tab);
                                        }
                                    }
                                }
                                if (isType(callback, CCR.Types.Function)) callback.apply(self);
                            }
                        })
                    }
                }


            }
        }, // process_job_node

        /**
         * Process the given path for the view_node history event.
         *
         * @param {Array} path
         * @param {Boolean} isSelected
         */
        process_view_node: function (path) {
            var exists = CCR.exists;
            var isType = CCR.isType;
            var self = this;

            var hasCurrentNode = exists(this.currentNode);
            var hasAttributes = hasCurrentNode && exists(this.currentNode.attributes);

            if (!hasCurrentNode) {
                // TODO: write code to populate the currentNode.
                console.log('No node... crying now :(');
            } else if (hasCurrentNode && hasAttributes) {

                // RETRIEVE: The currentNodes attributes.
                var attributes = this.currentNode.attributes;

                var jobId = this._find('jobid', 'jobid', this.currentNode);
                jobId = isType(jobId, CCR.Types.Number) ? jobId : isType(jobId, CCR.Types.String) ? parseInt(jobId) : 0;

                var dType = attributes.dtype;
                if (!attributes.jobid) attributes.jobid = jobId;

                // RETRIEVE: the tabs component
                var tabs = Ext.getCmp(this.tabpanel_id);

                // RETRIEVE: The job tab if it exists.
                var found = tabs.find('jobId', jobId);
                var jobTab = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;

                /**
                 * Helper function that does the bulk of the work for
                 * processing a view node request.
                 */
                var processView = function () {
                    var toInt = CCR.toInt;
                    var currentPath = self._getPath(document.location.hash);

                    var jobId = toInt(self._find('jobid', 'jobid', currentPath));
                    var title = self._find('text', dType, self.currentNode);

                    // RETRIEVE: the tabs component
                    var tabs = Ext.getCmp(self.tabpanel_id);

                    // RETRIEVE: The job tab if it exists.
                    var jobTab = self._fromArray(tabs.find('jobId', jobId), 0);

                    if (exists(jobTab)) {

                        var currentlyActive = tabs.activeTab && tabs.activeTab.jobId
                                ? tabs.activeTab.jobId === jobTab.jobId
                                : false;

                        if (!currentlyActive) {
                            jobTab.revert = false;
                            tabs.setActiveTab(jobTab);
                            return;
                        }

                        // RETRIEVE: the tab panel that holds the informational tabs.
                        var infoTabs = jobTab.getComponent('job_tabs');

                        if (exists(infoTabs)) {
                            var isChild = self.children_ids.indexOf(dType) >= 0;
                            var infoDType = isChild ? self.child_to_parent[dType] : dType;
                            var infoValue = self._find(infoDType, infoDType, self.currentNode);

                            var view = exists(title) ? infoTabs.findBy(function (component, container) {
                                var dTypes = component.dtypes;
                                var compDType = component.dtype;
                                var compValue = component.dtypeValue;

                                return ((dTypes && dTypes.indexOf(infoDType) >= 0) || (compDType === infoDType)) && (infoValue === compValue);
                            }) : null;
                            var viewExists = exists(view) && exists(view.length) && view.length > 0;

                            // CREATE: the view tab. and add it to the jobs' tabs.
                            if (!viewExists) {

                                var tab = self._generateView(attributes, path, title, jobTab);

                                // IF: we ended up with a tab being created then go ahead
                                // and add it and activate it.
                                if (exists(tab)) {
                                    infoTabs.add(tab);

                                    infoTabs.activate(tab);
                                }
                            } else {
                                var toActivate = view[0];
                                toActivate.dtypes[dType] = attributes[dType];

                                var reload = Ext.encode(toActivate.path) !== Ext.encode(currentPath);

                                toActivate.path = currentPath;
                                toActivate.revert = true;

                                infoTabs.setActiveTab(toActivate);
                                toActivate.fireEvent('activate', toActivate, reload);

                            }
                        }
                    }
                }; // processView


                if (!exists(jobTab)) {
                    var jobPath = this._truncatePath('jobid', path);
                    this.fireEvent('process_job_node', jobPath, processView);
                } else {
                    processView.apply(this);
                }
            }
        }, // process_view_node

        /**
         * Process a search deletion request by realm.
         *
         * @param realm of searches that is to be deleted.
         */
        search_delete_by_realm: function (realm) {
            var self = this;
            var isType = CCR.isType;
            var exists = CCR.exists;
            if (isType(realm, CCR.Types.String)) {
                Ext.MessageBox.confirm('Delete All Saved Searches?', 'Do you want to delete all saved searches for the realm: ' + realm + ' ?',
                        function (btn) {
                            if (btn === 'ok' || btn === 'yes') {
                                Ext.Ajax.request({
                                    /*'/rest/datawarehouse/search/history?realm=' + realm + '&token=' + XDMoD.REST.token,*/
                                    url: XDMoD.REST.url + '/' + self.rest.warehouse + '/search/history?realm=' + realm + '&token=' + XDMoD.REST.token,
                                    method: 'DELETE',
                                    success: function (response) {
                                        var data = JSON.parse(response.responseText);
                                        var success = exists(data) && exists(data.success) && data.success;
                                        if (success) {
                                            self.fireEvent('clear_display');
                                            var current = Ext.History.getToken();
                                            if (isType(current, CCR.Types.String)) {
                                                var currentToken = CCR.tokenize(current);
                                                var token = currentToken.tab + '?realm=' + realm;
                                                Ext.History.add(token);
                                            } else if (isType(current, CCR.Types.Object)) {
                                                var token = current.tab + '?realm=' + realm;
                                                Ext.History.add(token);
                                            }
                                        } else {
                                            Ext.MessageBox.show({
                                                title: 'Deletion Error',
                                                msg: 'There was an error removing all searches for the realm: [' + realm + '].',
                                                icon: Ext.MessageBox.ERROR,
                                                buttons: Ext.MessageBox.OK
                                            });
                                        }
                                    },
                                    failure: function (response) {
                                        Ext.MessageBox.show({
                                            title: 'Deletion Error',
                                            msg: 'There was an error removing all searches for the realm: [' + realm + '].',
                                            icon: Ext.MessageBox.ERROR,
                                            buttons: Ext.MessageBox.OK
                                        });
                                    }
                                });
                            } else {
                                Ext.MessageBox.alert('Ok', 'All your searches are belong to you.');
                            }
                        });
            }
        }, // search_delete_by_realm

        /**
         * Process a search deletion request for a given node.
         *
         * @param {Ext.tree.TreeNode} node on which to process the deletion request.
         */
        search_delete_by_node: function (node) {
            var self = this;
            var isType = CCR.isType;
            var exists = CCR.exists;
            var encode = CCR.encode;
            if (isType(node, CCR.Types.Object)) {
                var title = node.text || node.attributes ? node.attributes.text : undefined;
                Ext.MessageBox.confirm('Delete All Saved Searches?', 'Do you want to delete the search: ' + title + ' ?',
                        function (text) {
                            if (text === 'ok' || text === 'yes') {
                                var recordId = node.attributes.recordid !== undefined ? node.attributes.recordid : null;
                                var fragment = recordId !== null ? '/search/history/' + recordId : '/search/history';
                                var path = self._getPath(node);
                                /*'/rest/datawarehouse/search/history'*/
                                var url = self._generateURL(XDMoD.REST.url + '/' + self.rest.warehouse + fragment, path);
                                Ext.Ajax.request({
                                    url: url,
                                    method: 'DELETE',
                                    success: function (response) {
                                        var data = JSON.parse(response.responseText);
                                        var success = exists(data) && exists(data.success) && data.success;
                                        if (success) {
                                            self.fireEvent('clear_display');
                                            var current = Ext.History.getToken();
                                            var path = self._getPath(node);
                                            if (path && path.length && path.length > 0) delete path[path.length - 1];
                                            var params = self._getParams(path);
                                            var encoded = encode(params);
                                            if (isType(current, CCR.Types.String)) {
                                                var currentToken = CCR.tokenize(current);
                                                var token = currentToken.tab + '?' + encoded;
                                                Ext.History.add(token);
                                            } else if (isType(current, CCR.Types.Object)) {
                                                var token = current.tab + '?' + encoded;
                                                Ext.History.add(token);
                                            }
                                        } else {
                                            Ext.MessageBox.show({
                                                title: 'Deletion Error',
                                                msg: 'There was an error removing search: [' + title + '].',
                                                icon: Ext.MessageBox.ERROR,
                                                buttons: Ext.MessageBox.OK
                                            });
                                        }
                                    },
                                    failure: function (response) {
                                        Ext.MessageBox.show({
                                            title: 'Deletion Error',
                                            msg: 'There was an error removing search: [' + title + '].',
                                            icon: Ext.MessageBox.ERROR,
                                            buttons: Ext.MessageBox.OK
                                        });
                                    }
                                })
                            }
                        }
                );

            }
        }, // search_delete_by_node


        /**
         * Attempt to create a history entry. This will queue the work to be
         * done if the Search History Tree has no
         *
         * @param data
         */
        create_history_entry: function (data) {
            if (this.treeLoaded) {
                this._createHistoryEntry(data);
            } else {
                this.historyEventWaiting = true;
                this.createHistoryCallback = this._createHistoryEntry;
                this.createHistoryCallbackData = data;
                this.createHistoryCallbackScope = this;
            }
        },

        /**
         * Run a job search and save the first result in the search history
         */
        run_search_action: function (searchparams) {
            var self = this;

            Ext.Ajax.request({
                url: XDMoD.REST.url + '/' + this.rest.warehouse + '/search/jobs',
                method: 'GET',
                params: {
                    token: XDMoD.REST.token,
                    realm: searchparams.realm,
                    params: JSON.stringify(searchparams)
                },
                success: function (response) {
                    var data = JSON.parse(response.responseText);
                    if (data.success === false || data.totalCount < 1) {
                        Ext.Msg.show({
                            title: 'No results',
                            msg: 'No jobs were found that meet the requested search parameters.',
                            buttons: Ext.Msg.OK,
                            fn: Ext.History.add(self.module_id + '?realm=' + searchparams.realm),
                            icon: Ext.MessageBox.INFO
                        });
                        return;
                    }
                    var historyEntry = {
                        title: searchparams.title || 'Linked Search',
                        realm: searchparams.realm,
                        text: data.results[0].text,
                        job_id: data.results[0].jobid,
                        local_job_id: data.results[0].local_job_id
                    };
                    self.fireEvent('create_history_entry', historyEntry);
                },
                failure: function (response) {
                    var message;
                    try {
                        var result = JSON.parse(response.responseText);
                        if (result.message) {
                            message = result.message;
                        }
                    } catch (e) {
                        message = 'Error processing request';
                    }

                    Ext.Msg.show({
                        title: 'Error ' + response.status + ' ' + response.statusText,
                        msg: message,
                        buttons: Ext.Msg.OK,
                        fn: Ext.History.add(self.module_id + '?realm=' + searchparams.realm),
                        icon: Ext.MessageBox.ERROR
                    });
                }
            });
        },

        /**
         * Update the sort order for the nodes in the search history tree.
         * Does nothing if the current sort order is same as requested
         *
         * @param the requested sort order
         */
        update_tree_sort: function(sortMode) {
            if (this.sortMode != sortMode) {
                this.sortMode = sortMode;
                this.searchHistoryPanel.getRootNode().reload(function() {
                    this.currentNode = this.searchHistoryPanel.getRootNode();
                    this.fireEvent('activate');
                }, this);
            }
        },

        /**
         * Process a node selected event. This results in the History Token
         * ( document.location.hash ) being updated to reflect the node
         * that is currently selected. Also ensure that the node selected is
         * recorded as the current node.
         *
         * @param node that has been selected.
         */
        node_selected: function (node) {
            var exists = CCR.exists;

            this.currentNode = node;

            var token = this._createHistoryToken(node);

            var raw = Ext.History.getToken();
            var current = typeof raw === 'string' ? CCR.tokenize(raw) : raw;

            if (current.params === token) {
                this.fireEvent('activate');
            } else {
                if (exists(token)) Ext.History.add(this.module_id + '?' + token, false);
            }
        }, // node_selected

    }, // listeners ===========================================================

    /**
     * Create a history token from the provided `node`.
     *
     * @param {Ext.tree.TreeNode} node
     * @returns {null|String}
     * @private
     */
    _createHistoryToken: function (node) {
        var exists = CCR.exists;
        if (exists(node) && exists(node.attributes)) {

            var results = [];
            for (var next = node; next.parentNode !== null; next = next.parentNode) {
                var key = next.attributes['dtype'];
                var value = exists(key) ? next.attributes[key] : null;
                if (exists(value)) results.push(key + '=' + value);
            }

            return results.reverse().join('&');
        }
        return null;
    }, // _createHistoryToken

    /**
     * Create a history token from the provided array of objects which provide
     * minimally:
     * {
     *   dtype: string,
     *   value: *
     * }
     *
     * @param data
     * @returns {*}
     * @private
     */
    _createHistoryTokenFromArray: function (data) {
        var exists = CCR.exists;
        if (exists(data) && exists(data.length) && data.length > 0) {

            var results = [];
            for (var i = 0; i < data.length; i++) {
                var next = data[i];
                var key = next['dtype'];
                var value = next['value'];
                if (exists(key) && exists(value)) results.push(key + '=' + value);
            }
            var reverse = results && results.length > 0 && results[0].indexOf('realm') < 0;
            if (reverse) results.reverse();
            return results.join('&');
        }
        return null;
    }, // _createHistoryTokenFromArray

    /**
     * Truncate the path at the first object that has the provided 'property'.
     *
     * @param {String} dtype
     * @param {Array} path
     * @returns {Array}
     * @private
     */
    _truncatePath: function (dtype, path) {
        var isType = CCR.isType;

        if (!isType(dtype, CCR.Types.String)) return path;
        if (!isType(path, CCR.Types.Array)) return path;
        var results = [];
        for (var i = 0; i < path.length; i++) {
            var entry = path[i];
            if (entry.hasOwnProperty('dtype') && entry['dtype'] !== dtype) {
                results.push(entry);
            } else if (entry['dtype'] === dtype) {
                results.push(entry);
                break;
            }
        }
        return results;
    },// _truncatePath

    /**
     * Attempt to find ( return ) the provided property of the provided node,
     * or one of it's children, iff the node or child has the provided dtype.
     *
     * @param property to be found.
     * @param dtype that will be used to qualify the node.
     * @param node the root node to be used in the search.
     * @returns {*} the value of the property, if found.
     * @private
     */
    _find: function (property, dtype, node) {
        var isType = CCR.isType;
        var exists = CCR.exists;

        if (!isType(property, CCR.Types.String)) return undefined;
        if (!exists(node)) return undefined;

        var hasDtype = isType(dtype, CCR.Types.String);
        var isObject = isType(node, CCR.Types.Object);
        var isArray = isType(node, CCR.Types.Array);

        if (isObject) {
            for (var current = node; exists(current); current = current.parentNode) {
                var attributes = current.attributes || {};
                if (hasDtype && attributes.hasOwnProperty('dtype') && attributes.dtype === dtype && attributes.hasOwnProperty(property)) {
                    return attributes[property];
                } else if (!hasDtype && attributes.hasOwnProperty(property)) {
                    return attributes[property];
                }
            }
        } else if (isArray) {
            for (var i = 0; i < node.length; i++) {
                var attributes = node[i];
                if (hasDtype && attributes.hasOwnProperty('dtype') && attributes.dtype === dtype && attributes.hasOwnProperty('value')) {
                    return attributes['value'];
                } else if (!hasDtype && attributes.hasOwnProperty(property)) {
                    return attributes[property];
                }
            }
        }
        return null;
    }, // _find

    /**
     * Retrieve the 'path' values from the provided tree node or window hash.
     *
     * @param {String|Ext.tree.TreeNode} node
     * @returns {Array}
     * @private
     */
    _getPath: function (node, keys) {
        var exists = CCR.exists;
        var isType = CCR.isType;
        var results = [];
        if (isType(node, CCR.Types.Object)) {
            for (; exists(node); node = node.parentNode) {
                var attributes = node.attributes || [];
                var dtype = attributes['dtype'];
                var value = exists(dtype) ? attributes[dtype] : undefined;
                if (exists(value)) results.push({dtype: dtype, value: value});
            }
            return results.reverse();
        } else if (isType(node, CCR.Types.String)) {

            var results = [];

            var token = CCR.tokenize(node);
            var params = token && token.params && token.params.split ? token.params.split('&') : [];
            for (var i = 0; i < params.length; i++) {
                var param = params[i].split('=');
                var key = param[0];
                var value = param[1];

                var keyIndex = !exists(keys) || keys.indexOf(key);
                if (keyIndex >= 0) {
                    results.push({dtype: key, value: value});
                }
            }
            return results;
        }
    }, // _getPath

    /**
     * Accepts an Array of objects of the correct format and returns a query parameter style string.
     *
     *
     * path should be provided in the following format:
     * [
     *   { dtype: string, value: string },
     *   ....
     * ]
     *
     * @param {Array} path
     * @return {Object} in the form { dtype: value, dtype: value, ... }
     * @private
     */
    _getParams: function (path) {
        var exists = CCR.exists;

        if (exists(path)) {
            var results = {};
            for (var i = 0; i < path.length; i++) {
                var part = path[i];
                var key = exists(part) && exists(part.dtype) ? part.dtype : null;
                var value = exists(part) && exists(part.value) ? part.value : null;
                if (exists(key) && exists(value)) results[key] = value;
            }
            return results;
        }
        return {}
    }, //_getParams

    /**
     *
     * @param panel
     * @private
     */
    _panelActivation: function (panel) {
        if (panel.updateHistory) this._updateHistoryFromPanel(panel);
    }, // _panelActivation

    /**
     * Attempt to update the History Token from a panel being activated. This
     * captures the user scenario of clicking on an informational tab and not
     * on a Search History Node.
     *
     * @param panel
     * @private
     */
    _updateHistoryFromPanel: function (panel) {
        var token;
        if (panel.path) {
            token = '#' + this.module_id + '?' + this._createHistoryTokenFromArray(panel.path);
        } else {

            var path = this._getPath(window.location.hash);

            var dtype = panel.dtype;
            var value = panel.dtypeValue;

            path = this._truncatePath(dtype, path);

            var dtypeFound = false;
            var dtypeValuesDiffer = true;
            for (var i = 0; i < path.length; i++) {
                var entry = path[i];
                if (entry.dtype === dtype) {
                    dtypeFound = true;

                    dtypeValuesDiffer = entry.value != value;
                    entry.value = value;
                    break;
                }
            }

            if (!dtypeFound) {
                path.push({
                    dtype: dtype,
                    value: value
                });
                token = '#' + this.module_id + '?' + this._createHistoryTokenFromArray(path);
            } else if (dtypeValuesDiffer) {
                token = '#' + this.module_id + '?' + this._createHistoryTokenFromArray(path);
            }
        }
        Ext.History.add(token, true);
    }, // _updateHistoryFromPanel

    /**
     * Copy 'from' array -> 'to' array. If the append flag is set then it
     * appends, else if overwrites.
     *
     * @param {Array} from
     * @param {Array} to
     * @param {Boolean} append if true then appends, else overwrites.
     * @private
     */
    _copy: function (from, to, append) {
        var exists = CCR.exists;
        var isType = CCR.isType;

        if (!isType(from, CCR.Types.Array)) throw new Error('Can only copy from arrays.');
        if (!isType(to, CCR.Types.Array)) return new Error('Can only copy to arrays.')
        append = exists(append);

        for (var i = 0; i < from.length; i++) {
            if (append) {
                to.push(from[i]);
            } else if (i <= to.length) {
                to[i] = from[i];
            }
        }
        return to;
    }, // _copy

    /**
     * Attempt to process a request to show the currentNodes view. This occurs
     * after a job_node has been processed ( expanded ).
     *
     * @private
     */
    _processViewRequest: function () {
        var isType = CCR.isType;
        var exists = CCR.exists;

        var jobId = this._find('jobid', 'jobid', this.currentNode);
        var title = this._find('text', 'jobid', this.currentNode);

        jobId = isType(jobId, CCR.Types.Number) ? jobId : isType(jobId, CCR.Types.String) ? parseInt(jobId) : 0;

        // RETRIEVE: the tabs component
        var tabs = Ext.getCmp(this.tabpanel_id);

        // RETRIEVE: The job tab if it exists.
        var found = tabs.find('jobId', jobId);

        var jobTab = isType(found, CCR.Types.Array) && found.length > 0 ? found[0] : null;
        if (exists(jobTab)) {

            var currentlyActive = tabs.activeTab && tabs.activeTab.jobId
                    ? tabs.activeTab.jobId === jobTab.jobId
                    : false;

            if (!currentlyActive) {
                tabs.setActiveTab(jobTab);
            }

            var jobTabs = jobTab.getComponent('job_tabs');
            if (exists(jobTabs)) {
                var hasTabs = jobTabs.items.length > 0;
                if (hasTabs) {
                    jobTabs.activate(jobTabs.items.get(0));
                }
            }
        }
    }, // _processViewRequest

    /**
     * Replace the property found in the paths' array of objects
     * with the provided value if found.
     *
     * @param {String} property
     * @param {*} value
     * @param {Array} path
     * @private
     */
    _replace: function (property, value, path) {
        var isType = CCR.isType;
        if (!isType(path, CCR.Types.Array)) return;
        for (var i = 0; i < path.length; i++) {
            var entry = path[i];
            if (entry.dtype === property) {
                entry.value = value;
                return;
            }
        }
        return;
    }, // _replace


    /**
     * Retrieve the value at the index provided from 'data'. This is done in a
     * null-safe / index-safe way.
     *
     * @param {Array} data
     * @param {Number} index
     * @returns {*}
     * @private
     */
    _fromArray: function (data, index) {
        var isType = CCR.isType;
        if (!isType(data, CCR.Types.Array) || (!isType(index, CCR.Types.Number) & index < 0)) return undefined;
        if (index >= data.length) return undefined;

        return data[index];
    }, // _fromArray

    /**
     * Compare the given search history tree node with the provided path array.
     * The path encoding from the _getPath function.
     *
     * @param Ext.tree.TreeNode node
     * @param Array path
     * @returns true if the path array matches the tree node, false otherwise
     */
    compareNodePath: function (node, path) {
        var i;
        var np;
        for (np = node, i = path.length - 1; np && np.attributes && np.attributes.dtype; np = np.parentNode, --i) {
            if (i < 0) {
                return false;
            }
            if (path[i].dtype !== np.attributes.dtype || path[i].value !== String(np.attributes[np.attributes.dtype])) {
                return false;
            }
        }
        return i === -1;
    },

    /**
     * Attempt to create a history entry ( node located in the Search History
     * Panel ) from the provided options. This occurs when interpreting the
     * results of a History Token activation from Metric Explorer.
     *
     * @param options
     * @private
     */
    _createHistoryEntry: function (options) {
        var self = this;
        var searchTitle = options.title;
        var realm = options.realm;
        var jobTitle = options.text;
        var jobId = options.job_id;
        var jobLocalId = options.local_job_id;

        var jobData = {
            resource: "",
            name: "",
            jobid: jobId,
            text: jobTitle,
            dtype: 'jobid',
            local_job_id: jobLocalId
        };

        var searchPromise = this._makeRequest(
            'GET',
            XDMoD.REST.url + '/' + this.rest.warehouse + '/search/history',
            null,
            {
                realm: realm,
                title: searchTitle,
                token: XDMoD.REST.token
            }
        );

        searchPromise.then(function (results) {
            var data = results.data;
            var recordId = data.recordid;
            var jobs = data.results || [
                        jobData
                    ];
            var jobFound = false;
            for (var i = 0; i < jobs.length; i++) {
                var job = jobs[i];
                if (job.jobid == jobId) {
                    jobFound = true;
                }
            }
            if (!jobFound) jobs.push(jobData);
            var upsertPromise = self._upsertSearch(realm, searchTitle, recordId, jobs,  null);
            upsertPromise.then(function (data) {
                var realmNode = self.searchHistoryPanel.root.findChild('text', realm);
                var path = self._getPath(realmNode);
                recordId = recordId || data.results.recordid;
                path.push({
                    dtype: 'recordid',
                    value: recordId
                });
                path.push({
                    dtype: 'jobid',
                    value: jobId
                });
                self.fireEvent('reload_root', path);
                self.historyEventWaiting = false;
            })['catch'](function (response) {
                var message = response.msg || response.message || 'Updating the provided search.';
                CCR.error('Error', 'Unable to complete the requested operation: ' + message);
            });
        })['catch'](function (response) {
            // It wasn't found, so we need to create it.
            var upsertPromise = self._upsertSearch(realm, searchTitle, null, [jobData], null);
            upsertPromise.then(function (data) {
                var path = [{
                    dtype: 'realm',
                    value: realm
                }, {
                    dtype: 'recordid',
                    value: data.results.recordid
                }, {
                    dtype: 'jobid',
                    value: jobId
                }];
                self.fireEvent('reload_root', path);
                self.historyEventWaiting = false;
            });
            /* var message = response.msg || response.message || 'Retrieving search info.';
            CCR.error('Error', 'Unable to complete the requested operation: ' + message);*/
        });
    }, // _createHistoryEntry

    /**
     * Attempts to execute an 'upsert' ( either an update or an insert depending
     * on the context ) of a 'search' which is identified by the provided
     * properties. Note that the results will be provided via a Promise so
     * the caller will need to call:
     *
     * _upsertSearch(realm, title, id, jobs).then( function(results) {
     *   ... processing logic goes here ...
     * }).catch( function(errorResponse) {
     *   ... error logic goes here ...
     * });
     *
     * @param {String} realm       in which this search took place
     * @param {String} title       to give the search.
     * @param {String} id          optional, if provided, then this will be used as the 'recordid'
     *                             which will indicate an update.
     * @param {Object} jobs        that should be associated with this search.
     * @param {Array}  searchTerms optional, that should be included with this search.
     * @returns {*|Promise.<T>}
     * @private
     */
    _upsertSearch: function (realm, title, id, jobs, searchTerms) {

        var url = XDMoD.REST.url + '/' + this.rest.warehouse + '/search/history?realm=' + realm + '&token=' + XDMoD.REST.token;
        searchTerms = searchTerms || {};
        var params = {
            'data': JSON.stringify(
                    {
                        "text": title,
                        "searchterms": searchTerms,
                        "results": jobs
                    })
        };
        if (CCR.exists(id)) params['recordid'] = id;

        return this._makeRequest('POST', url, null, params);
    }, // _upsertSearch

    /**
     * Helper function that wraps making an AJAX call via Exts' Ext.Ajax.request
     * method in a Promise. It also expects the response to be JSON and that it
     * it will have a 'success' root property. This is for the proper handling
     * of the resolve, reject methods. If the method returns anything other than
     * a '200' status code then the reject ( catch ) method will be called. Also
     * , if the root property 'success' does not exist or if it does exist but
     * is false then the reject ( catch ) method will be called as well. In all
     * other cases the resolve ( then ) method will be called.
     *
     * @param {String} method to use when making the request: GET|PUT|POST
     *                        |DELETE|PATCH
     * @param {String} url    to use when making the request.
     * @param {Object} data   optional, will be included as the request options
     *                        'data' property.
     * @param {Object} params optional, will be included as the request options
     *                        'params' property.
     * @returns {Promise<T>}  that can be used to complete the requested request
     * @private
     */
    _makeRequest: function (method, url, data, params) {
        return new RSVP.Promise(function (resolve, reject) {
            var options = {
                method: method,
                url: url,
                success: function (response) {
                    var data = JSON.parse(response.responseText);
                    var success = CCR.exists(data) && CCR.exists(data.success) && data.success;
                    if (success) {
                        resolve(data);
                    } else {
                        reject(response);
                    }
                },
                failure: function (response) {
                    reject(response);
                }
            };

            if (CCR.exists(data)) options['data'] = data;
            if (CCR.exists(params)) options['params'] = params;
            Ext.Ajax.request(options);
        });
    } // _makeRequest
})
; //XDMoD.Module.JobViewer
