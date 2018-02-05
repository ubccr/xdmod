/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-4-27
 *
 * the panel for adding data to a chart in the Metric Explorer
 */
CCR.xdmod.ui.AddDataPanel = function (config) {
    CCR.xdmod.ui.AddDataPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.AddDataPanel

Ext.apply(CCR.xdmod.ui.AddDataPanel, {
    display_types: [
        ['line', 'Line'],
        ['column', 'Bar'],
        //['bar', 'Horizontal Bar'],
        ['area', 'Area'],
        ['scatter', 'Scatter'],
        ['spline', 'Spline'],
        ['areaspline', 'Area Spline'],
        ['pie', 'Pie']
    ],
    line_types: [
        ['Solid', 'Solid', ''],
        ['ShortDash', 'ShortDash', '6,2'],
        ['ShortDot', 'ShortDot', '2,2'],
        ['ShortDashDot', 'ShortDashDot', '6,2,2,2'],
        ['ShortDashDotDot', 'ShortDashDotDot', '6,2,2,2,2,2'],
        ['Dot', 'Dot', '2,6'],
        ['Dash', 'Dash', '8,6'],
        ['LongDash', 'LongDash', '16,6'],
        ['DashDot', 'DashDot', '8,6,2,6'],
        ['LongDashDot', 'LongDashDot', '16,6,2,6'],
        ['LongDashDotDot', 'LongDashDotDot', '16,6,2,6,2,6']
    ],
    line_widths: [
        [1, '1'],
        [2, '2'],
        [3, '3'],
        [4, '4'],
        [5, '5'],
        [6, '6'],
        [7, '7'],
        [8, '8']
    ],
    combine_types: [
        ['side', 'Side by Side'],
        ['stack', 'Stacked'],
        ['percent', 'Percentage']
    ],
    sort_types: [
        ['none', 'None'],
        ['value_asc', 'Values Ascending'],
        ['value_desc', 'Values Descending'],
        ['label_asc', 'Labels Ascending'],
        ['label_desc', 'Labels Descending']
    ],
    defaultConfig: function (timeseries) {
        return {
            group_by: 'none',
            color: 'auto',
            log_scale: false,
            std_err: false,
            value_labels: false,
            display_type: timeseries != undefined && timeseries ? 'line' : 'column',
            combine_type: 'side',
            sort_type: 'value_desc',
            ignore_global: false,
            long_legend: true,
            x_axis: false,
            has_std_err: false,
            trend_line: false,
            line_type: 'Solid',
            line_width: 2,
            shadow: false,
            filters: null,
            z_index: null,
            visibility: null,
            enabled: true
        };
    },
    initRecord: function (store, config, selectedFilters, timeseries) {
        var conf = {};
        jQuery.extend(true, conf, CCR.xdmod.ui.AddDataPanel.defaultConfig(timeseries));
        if (config) jQuery.extend(true, conf, config);
        conf.id = Math.random();
        conf.z_index = store.getCount();
        conf.filters = selectedFilters ? selectedFilters : {
            data: [],
            total: 0
        };
        return new store.recordType(conf);
    }
});
Ext.extend(CCR.xdmod.ui.AddDataPanel, Ext.Panel, {
    record: null,
    store: null,
    update_record: false,
    getSelectedFilters: function () {
        var ret = [];
        if (this.filtersStore) {
            this.filtersStore.each(
                function (record) {
                    var data = jQuery.extend({}, record.data);
                    ret.push(data);
                });
        }
        return {
            data: ret,
            total: ret.length
        };
    },
    initComponent: function () {
        var filterButtonHandler;

        if (!this.record && this.store) {
            this.record = CCR.xdmod.ui.AddDataPanel.initRecord(this.store, this.config, this.getSelectedFilters(), this.timeseries);
        }
        this.originalData = {};
        jQuery.extend(this.originalData, this.record.data);
        var filtersMenu = new Ext.menu.Menu({
            showSeparator: false,
            ignoreParentClicks: true
        });
        var filterItems = [];
        var filterMap = {};
        filtersMenu.removeAll(true);

        this.addFilterButton = new Ext.Button({
            text: 'Add Filter',
            xtype: 'button',
            iconCls: 'add_filter',
            scope: this,
            menu: filtersMenu
        });

        var realm_dimensions = this.realms[this.record.data.realm]['dimensions'];
        for (x in realm_dimensions) {
            if (x == 'none' || realm_dimensions[x].text == undefined) continue;
            if (filterMap[x] == undefined) {
                filterMap[x] = filterItems.length;
                filterItems.push({
                    text: realm_dimensions[x].text,
                    iconCls: 'menu',
                    realms: [this.record.data.realm],
                    dimension: x,
                    scope: this,
                    handler: function (b, e) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected filter from menu', b.text);
                        filterButtonHandler.call(b.scope, b.dimension, b.text, b.realms);
                    }
                });
            } else {
                if (filterItems[filterMap[x]].realms.indexOf(this.record.data.realm) == -1) {
                    filterItems[filterMap[x]].realms.push(this.record.data.realm);
                }
            }
        }
        filterItems.sort(
            function (a, b) {
                var nameA = a.text.toLowerCase(),
                    nameB = b.text.toLowerCase();
                if (nameA < nameB) //sort string ascending
                    return -1;
                if (nameA > nameB)
                    return 1;
                return 0; //default return value (no sorting)
            }
        );
        filtersMenu.addItem(filterItems);
        filterButtonHandler = function (dim_id, dim_label, realms) {
            if (!dim_id || !dim_label) return;
            var filterDimensionPanel = new CCR.xdmod.ui.FilterDimensionPanel({
                origin_module: 'Metric Explorer',
                origin_component: 'Data Series Definition',
                dimension_id: dim_id,
                realms: realms,
                dimension_label: dim_label,
                //selectedFilters: [],
                filtersStore: this.filtersStore
            });
            filterDimensionPanel.on('cancel', function () {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Cancelled filters panel');
                addFilterMenu.closable = true;
                addFilterMenu.hide();
            });
            filterDimensionPanel.on('ok', function () {
                addFilterMenu.closable = true;
                addFilterMenu.hide();
            });
            var addFilterMenu = new Ext.menu.Menu({
                showSeparator: false,
                items: [filterDimensionPanel],
                scope: this,
                closable: false,
                listeners: {
                    'beforehide': function (t) {
                        return t.closable;
                    },
                    'hide': function (t) {
                        t.scope.el.unmask();
                    },
                    'show': function (t) {
                        t.scope.el.mask();
                    }
                }
            });
            addFilterMenu.ownerCt = this;
            addFilterMenu = Ext.menu.MenuMgr.get(addFilterMenu);
            addFilterMenu.show(this.addFilterButton.el, 'tl-bl?');
        }
        var realmData = [];
        for (realm in this.realms) {
            realmData.push([realm]);
        }
        var metricData = [];
        for (metric in this.realms[this.record.data.realm]['metrics']) {
            metricData.push([metric, this.realms[this.record.data.realm]['metrics'][metric].text]);
        }
        var dimenionsData = [];
        for (dimension in this.realms[this.record.data.realm]['dimensions']) {
            dimenionsData.push([dimension, this.realms[this.record.data.realm]['dimensions'][dimension].text]);
        }
        var activeFilterCheckColumn = new Ext.grid.CheckColumn({
            id: 'checked',
            sortable: false,
            dataIndex: 'checked',
            header: 'Local',
            tooltip: 'Check this column to apply filter to this dataset',
            scope: this,
            width: 50,
            hidden: false,
            checkchange: function (record, data_index, checked) {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Toggled filter checkbox', Ext.encode({
                    dimension: record.data.dimension_id,
                    value: record.data.value_name,
                    checked: checked
                }));
            } // checkchange
        });
        this.filtersStore = new Ext.data.GroupingStore({
            autoDestroy: true,
            idIndex: 0,
            groupField: 'dimension_id',
            sortInfo: {
                field: 'dimension_id',
                direction: 'ASC' // or 'DESC' (case sensitive for local sorting)
            },
            reader: new Ext.data.JsonReader({
                totalProperty: 'total',
                idProperty: 'id',
                root: 'data'
            }, [
                'id',
                'value_id',
                'value_name',
                'dimension_id',
                'realms',
                'checked'
            ])
        });
        if (this.record.data.filters) {
            var currentFilters = jQuery.extend({}, this.record.data.filters);
            this.filtersStore.loadData(currentFilters, false);
        }
        var selectAllButton = new Ext.Button({
            text: 'Select All',
            scope: this,
            handler: function (b, e) {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on Check All in Local Filters pane');
                this.filtersStore.each(function (r) {
                    r.set('checked', true);
                });
            }
        });
        var clearAllButton = new Ext.Button({
            text: 'Clear All',
            scope: this,
            handler: function (b, e) {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on Uncheck All in Local Filters pane');
                this.filtersStore.each(function (r) {
                    r.set('checked', false);
                });
            }
        });

        var applyFilterSelection = new Ext.Button({
            tooltip: 'Apply selected filter(s)',
            text: 'Apply',
            scope: this,
            handler: function () {
                this.record.set('filters', this.getSelectedFilters());
                XDMoD.TrackEvent('Metic Explorer', 'Clicked on Apply filter in Chart Filters pane');
            } // handler
        }); // applyFilterSelection

        var removeFilterItem = new Ext.Button({
            iconCls: 'delete_filter',
            tooltip: 'Delete highlighted filter(s)',
            text: 'Delete',
            disabled: true,
            scope: this,
            handler: function (i, e) {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on Delete in Local Filters pane');
                var records = filtersGridPanel.getSelectionModel().getSelections();
                for (i = 0; i < records.length; i++) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Confirmed deletion of filter', Ext.encode({
                        dimension: records[i].data.dimension_id,
                        value: records[i].data.value_name
                    }));
                } //for (each record selected)
                filtersGridPanel.store.remove(records);
            }
        });

        var viewer = CCR.xdmod.ui.Viewer.getViewer();
        var datasetsMenuDefaultWidth = 1150;
        var viewerWidth = viewer.getWidth();

        var filtersGridPanel = new Ext.grid.GridPanel({
            header: false,
            height: 250,
            id: 'grid_filters_' + this.id,
            useArrows: true,
            autoScroll: true,
            sortable: false,
            enableHdMenu: false,
            loadMask: true,
            margins: '0 0 0 0',
            buttonAlign: 'left',
            view: new Ext.grid.GroupingView({
                emptyText: 'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.',
                forceFit: true,
                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (sm, row_index, record) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected a chart filter', Ext.encode({
                            dimension: record.data.dimension_id,
                            value: record.data.value_name
                        }));
                    },
                    rowdeselect: function (sm, row_index, record) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> De-selected a chart filter', Ext.encode({
                            dimension: record.data.dimension_id,
                            value: record.data.value_name
                        }));
                    },
                    selectionchange: function (sm) {
                        removeFilterItem.setDisabled(sm.getCount() <= 0);
                    }
                }
            }),
            plugins: [
                activeFilterCheckColumn
                //,new Ext.ux.plugins.ContainerBodyMask ({ msg:'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.', masked:true})
            ],
            autoExpandColumn: 'value_name',
            store: this.filtersStore,
            columns: [
                activeFilterCheckColumn,
                //{id: 'realm', tooltip: 'Realm', width: 80, header: 'Realm',  dataIndex: 'realm'},
                {
                    id: 'dimension',
                    tooltip: 'Dimension',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo),
                    width: 80,
                    header: 'Dimension',
                    dataIndex: 'dimension_id'
                }, {
                    id: 'value_name',
                    tooltip: 'Filter',
                    width: 100,
                    header: 'Filter',
                    dataIndex: 'value_name'
                }
            ],
            tbar: [
                removeFilterItem
            ],
            fbar: [
                clearAllButton,
                '-',
                selectAllButton,
                '->',
                applyFilterSelection
            ]
        });

        function updateFilters() {
            this.record.set('filters', this.getSelectedFilters());
        }
        this.filtersStore.on('add', updateFilters, this);
        this.filtersStore.on('remove', updateFilters, this);
        this.has_std_err = this.realms[this.record.data.realm]['metrics'][this.record.data.metric].std_err;
        this.stdErrorCheckBox = new Ext.form.Checkbox({
            fieldLabel: 'Std Err Bars',
            name: 'std_err',
            xtype: 'checkbox',
            boxLabel: 'Show the std err bars on each data point',
            disabled: !this.has_std_err || this.record.data.log_scale,
            checked: this.record.data.std_err,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('std_err', check);
                }
            }
        });
        this.stdErrorLabelsCheckBox = new Ext.form.Checkbox({
            fieldLabel: 'Std Err Labels',
            name: 'std_err_labels',
            xtype: 'checkbox',
            boxLabel: 'Show the std err labels on each data point',
            disabled: !this.has_std_err || this.record.data.log_scale,
            checked: this.record.data.std_err_labels,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('std_err_labels', check);
                }
            }
        });
        /*this.trendLineCheckBox = new Ext.form.Checkbox({
            fieldLabel: 'Trend Line',
            name: 'trend_line',
            xtype: 'checkbox',
            boxLabel: 'Show trend line',
            checked: this.record.data.trend_line && this.timeseries,
            disabled: !this.timeseries,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('trend_line', check);
                }
            }
        });*/
        this.enabledDatasetCheckBox = new Ext.form.Checkbox({
            fieldLabel: 'Enabled',
            name: 'enabled',
            xtype: 'checkbox',
            boxLabel: 'Show on chart',
            checked: this.record.data.enabled,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('enabled', check);
                }
            }
        });
        this.valueLabelsCheckbox = new Ext.form.Checkbox({
            fieldLabel: 'Value Labels',
            name: 'value_labels',
            xtype: 'checkbox',
            checked: this.record.data.value_labels,
            boxLabel: 'Show a value label on each data point',
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('value_labels', check);
                }
            }
        });
        this.displayTypeCombo = new Ext.form.ComboBox({
            flex: 2.5,
            fieldLabel: 'Display Type',
            name: 'display_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'text'
                ],
                data: CCR.xdmod.ui.AddDataPanel.display_types
            }),
            disabled: false,
            value: this.record.data.display_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            listeners: {
                scope: this,
                'select': function (combo, record, index) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                    var display_type = record.get('id');
                    var isValid = this.validate('display_type', display_type);
                    if (isValid) {

                        var btn = Ext.getCmp('adp_submit_button');
                        if (btn) {
                            btn.enable();
                        }

                        this.record.set('display_type', display_type);
                        if (display_type === 'pie') {
                            this.valueLabelsCheckbox.setValue(true);
                            this.logScaleCheckbox.disable();
                        } else {
                            this.logScaleCheckbox.enable();
                        }

                        this.lineTypeCombo.setDisabled(display_type !== 'line' &&
                            display_type !== 'spline' &&
                            display_type !== 'area' &&
                            display_type !== 'areaspline');
                    } else {
                        var btn = Ext.getCmp('adp_submit_button');
                        if (btn) {
                            btn.disable();
                        }
                    }

                }
            }
        });
        this.lineTypeCombo = new Ext.form.ComboBox({
            fieldLabel: 'Line Type',
            name: 'line_type',
            xtype: 'combo',
            mode: 'local',
            itemSelector: 'div.line-item',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'text',
                    'dasharray'
                ],
                data: CCR.xdmod.ui.AddDataPanel.line_types
            }),
            disabled: this.record.data.display_type !== 'line' &&
                this.record.data.display_type !== 'spline' &&
                this.record.data.display_type !== 'area' &&
                this.record.data.display_type !== 'areaspline',
            value: this.record.data.line_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="line-item">',
                '<span>',
                '<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">',
                '<g fill="none" stroke="black" stroke-width="2">',
                '<path stroke-dasharray="{dasharray}" d="M 0 6 l 180 0" />',
                '</g>', '</svg>', '{text}', '</span>',
                '</div></tpl>'
            ),
            listeners: {
                scope: this,
                'select': function (combo, record, index) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                    this.record.set('line_type', record.get('id'));
                }
            }
        });
        this.lineWidthCombo = new Ext.form.ComboBox({
            fieldLabel: 'Line Width',
            name: 'line_width',
            xtype: 'combo',
            mode: 'local',
            itemSelector: 'div.line-width-item',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'text'
                ],
                data: CCR.xdmod.ui.AddDataPanel.line_widths
            }),
            disabled: this.record.data.display_type !== 'line' &&
                this.record.data.display_type !== 'spline' &&
                this.record.data.display_type !== 'area' &&
                this.record.data.display_type !== 'areaspline',
            value: this.record.data.line_width,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="line-width-item">',
                '<span>',
                '<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">',
                '<g fill="none" stroke="black" stroke-width="{id}">',
                '<path stroke-dasharray="" d="M 0 6 l 180 0" />',
                '</g>', '</svg>', '{text}', '</span>',
                '</div></tpl>'
            ),
            listeners: {
                scope: this,
                'select': function (combo, record, index) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                    this.record.set('line_width', record.get('id'));
                }
            }
        });
        this.colorCombo = new Ext.form.ComboBox({
            fieldLabel: 'Color',
            name: 'color',
            xtype: 'combo',
            mode: 'local',
            itemSelector: 'div.color-item',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id', {
                        name: 'color_inverse',
                        convert: function (v, record) {
                            if (record == 'auto') return '000000';
                            return CCR.xdmod.ui.invertColor(record);
                        }
                    }
                ],
                data: CCR.xdmod.ui.colors[0]
            }),
            disabled: false,
            value: this.record.data.color,
            valueField: 'id',
            displayField: 'id',
            triggerAction: 'all',
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="color-item" style="border: 1px; background-color:#{id}; color:#{color_inverse}; " >',
                '<span >',
                '{id}',
                '</span>',
                '</div></tpl>'
            ),
            listeners: {
                scope: this,
                select: function (combo, record, index) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                    var color = record.get('id');
                    this.record.set('color', color);
                    if (color !== 'auto') {
                        document.getElementById(combo.id).style.backgroundImage = 'none';
                        document.getElementById(combo.id).style.backgroundColor = '#' + color;
                        document.getElementById(combo.id).style.color = '#' + record.get('color_inverse');
                    } else {
                        document.getElementById(combo.id).style.backgroundImage = 'url("../../gui/lib/extjs/resources/images/default/form/text-bg.gif")';
                        document.getElementById(combo.id).style.backgroundColor = '#ffffff';
                        document.getElementById(combo.id).style.color = '#000000';
                    }
                },
                render: function (combo) {
                    var color = this.record.get('color');
                    if (color !== 'auto') {
                        document.getElementById(combo.id).style.backgroundImage = 'none';
                        document.getElementById(combo.id).style.backgroundColor = '#' + color;
                        document.getElementById(combo.id).style.color = '#' + CCR.xdmod.ui.invertColor(color);
                    } else {
                        document.getElementById(combo.id).style.backgroundImage = 'url("../../gui/lib/extjs/resources/images/default/form/text-bg.gif")';
                        document.getElementById(combo.id).style.backgroundColor = '#ffffff';
                        document.getElementById(combo.id).style.color = '#000000';
                    }
                }
            }
        });
        this.shadowCheckBox = new Ext.form.Checkbox({
            fieldLabel: 'Shadow',
            name: 'shadow',
            xtype: 'checkbox',
            boxLabel: 'Cast a shadow',
            checked: this.record.data.shadow,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Advanced -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('shadow', check);
                }
            }
        });
        this.displayTypeConfigButton = new Ext.Button({
            flex: 1.5,
            xtype: 'button',
            text: 'Advanced',
            handler: function () {
                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on the Advanced button');
            },
            menu: [{
                bodyStyle: 'padding:5px 5px 0;',
                xtype: 'form',
                items: [this.lineTypeCombo, this.lineWidthCombo, this.colorCombo, this.shadowCheckBox]
            }]
        });
        var displayType = this.record.get('display_type');
        var isPie = displayType === 'pie';

        this.logScaleCheckbox = new Ext.form.Checkbox(            {
            fieldLabel: 'Log Scale',
            name: 'log_scale',
            xtype: 'checkbox',
            boxLabel: 'Use a log scale y axis for this data',
            checked: this.record.data.log_scale,
            disabled: isPie,
            listeners: {
                scope: this,
                'check': function (checkbox, check) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                        checked: check
                    }));
                    this.record.set('log_scale', check);
                    this.stdErrorCheckBox.setDisabled(!this.record.data.has_std_err || check);
                    this.stdErrorLabelsCheckBox.setDisabled(!this.record.data.has_std_err || check);
                }
            }
        });
        var formItems = [
            {
                fieldLabel: 'Category',
                name: 'category',
                xtype: 'combo',
                mode: 'local',
                editable: false,
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id'
                    ],
                    data: realmData // data is local
                }),
                disabled: true,
                value: XDMoD.Module.MetricExplorer.getCategoryForRealm(
                    this.record.data.realm
                ),
                valueField: 'id',
                displayField: 'id',
                triggerAction: 'all'
            }, {
                fieldLabel: 'Metric',
                name: 'metric',
                xtype: 'combo',
                mode: 'local',
                editable: false,
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'text'
                    ],
                    data: metricData // data is local
                }),
                disabled: false,
                value: this.record.data.metric,
                valueField: 'id',
                displayField: 'text',
                triggerAction: 'all',
                listeners: {
                    scope: this,
                    'select': function (combo, record, index) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                        var metric = record.get('id');
                        this.record.set('metric', metric);
                        var has_std_err = this.realms[this.record.data.realm]['metrics'][metric].std_err;
                        this.record.set('has_std_err', has_std_err);
                        this.stdErrorCheckBox.setDisabled(!has_std_err || this.record.data.log_scale);
                        this.stdErrorLabelsCheckBox.setDisabled(!has_std_err || this.record.data.log_scale);
                    }
                }
            }, {
                fieldLabel: 'Group by',
                name: 'dimension',
                xtype: 'combo',
                mode: 'local',
                editable: false,
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'text'
                    ],
                    data: dimenionsData // data is local
                }),
                disabled: false,
                value: this.record.data.group_by,
                valueField: 'id',
                displayField: 'text',
                triggerAction: 'all',
                listeners: {
                    scope: this,
                    'select': function (combo, record, index) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                        this.record.set('group_by', record.get('id'));
                    }
                }
            }, {
                fieldLabel: 'Sort Type',
                name: 'sort_type',
                xtype: 'combo',
                mode: 'local',
                editable: false,
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: [
                        'id',
                        'text'
                    ],
                    data: CCR.xdmod.ui.AddDataPanel.sort_types
                }),
                disabled: false,
                value: this.record.data.sort_type,
                valueField: 'id',
                displayField: 'text',
                triggerAction: 'all',
                listeners: {
                    scope: this,
                    'select': function (combo, record, index) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
                        this.record.set('sort_type', record.get('id'));
                    }
                }
            }, {
                fieldLabel: 'Display Type',
                xtype: 'compositefield',
                items: [
                    this.displayTypeCombo, {
                        fieldLabel: 'Stacking',
                        name: 'combine_type',
                        xtype: 'combo',
                        mode: 'local',
                        flex: 2,
                        editable: false,
                        store: new Ext.data.ArrayStore({
                            id: 0,
                            fields: [
                                'id',
                                'text'
                            ],
                            data: CCR.xdmod.ui.AddDataPanel.combine_types
                        }),
                        disabled: false,
                        value: this.record.data.combine_type,
                        valueField: 'id',
                        displayField: 'text',
                        triggerAction: 'all',
                        listeners: {
                            scope: this,
                            'select': function (combo, record, index) {
                                XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Selected Stacking type using drop-down menu', record.get('id'));
                                this.record.set('combine_type', record.get('id'));
                            }
                        }
                    },
                    this.displayTypeConfigButton
                ]
            },
            /*,
            {
                fieldLabel: 'X Axis',
                name: 'x_axis',
                xtype: 'checkbox',
                boxLabel: 'Use this data as the x axis values',
                disabled: this.timeseries,
                checked: this.x_axis,
                listeners:
                {
                    scope: this,
                    'check': function(checkbox, check)
                    {
                        this.x_axis = check;
                    }
                }
            }*/
            /*this.trendLineCheckBox,*/
            this.stdErrorCheckBox,
            this.stdErrorLabelsCheckBox,
            this.logScaleCheckbox,
            this.valueLabelsCheckbox, {
                fieldLabel: 'Verbose Legends',
                name: 'long_legend',
                boxLabel: 'Show filters in legend',
                checked: this.record.data.long_legend,
                xtype: 'checkbox',
                listeners: {
                    scope: this,
                    'check': function (checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                            checked: check
                        }));
                        this.record.set('long_legend', check);
                    }
                }
            },
            this.enabledDatasetCheckBox, {
                fieldLabel: 'Local Filters',
                xtype: 'container',
                layout: 'hbox',
                items: [
                    this.addFilterButton,
                    {
                        xtype: 'button',
                        text: 'Filters',
                        iconCls: 'filter',
                        menu: new Ext.menu.Menu({
                            showSeparator: false,
                            items: filtersGridPanel,
                            width: viewerWidth < datasetsMenuDefaultWidth ? viewerWidth : datasetsMenuDefaultWidth,
                            renderTo: document.body
                        })
                    }
                ]
            }, {
                fieldLabel: 'Ignore Chart Filters',
                name: 'ignore_global',
                xtype: 'checkbox',
                boxLabel: "Apply only local filters to this data series",
                checked: this.record.data.ignore_global,
                listeners: {
                    scope: this,
                    'check': function (checkbox, check) {
                        XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({
                            checked: check
                        }));
                        this.record.set('ignore_global', check);
                    }
                }
            }
        ];

        var form = new Ext.FormPanel({
            labelWidth: 125, // label settings here cascade unless overridden
            bodyStyle: 'padding:5px 5px 0',
            defaults: {
                width: 325,
                anchor: 0
            },
            items: formItems
        });

        Ext.apply(this, {
            items: [form],
            layout: 'fit',
            width: 475,
            height: 540,
            border: false,
            title: '<img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> Data Series Definition',
            buttons: [{
                scope: this,
                id: 'adp_submit_button',
                text: this.update_record ? 'Update' : 'Add',
                handler: function (b, e) {
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on ' + b.text);
                    b.scope.add_function.call(this);
                }
            }, {
                scope: this,
                text: 'Cancel',
                handler: function (b, e) {
                    for (attr in this.originalData) {
                        this.record.set(attr, this.originalData[attr]);
                    }
                    XDMoD.TrackEvent('Metric Explorer', 'Data Series Definition -> Clicked on Cancel');
                    b.scope.cancel_function.call(this);
                }
            }]
        });
        CCR.xdmod.ui.AddDataPanel.superclass.initComponent.apply(this, arguments);
        this.hideMenu = function () {
            filtersMenu.hide();
        };
    },

    validate: function(field, value) {
        // Default implementation is a pass through.
        return true;
    }
});
