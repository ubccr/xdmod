/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Dec-22
 *
 */
CCR.xdmod.ui.FilterDimensionPanel = function (config) {
    CCR.xdmod.ui.FilterDimensionPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.FilterDimensionPanel


Ext.extend(CCR.xdmod.ui.FilterDimensionPanel, Ext.Panel, {
    dimension_id: '',
    defaultPageSize: 10,
    filtersStore: null,
    getSelectedFilterIds: function () {
        var ret = [];
        this.filtersStore.each(function (record) {
            if(record.data.dimension_id === this.dimension_id && record.data.checked) ret.push(record.data.id);
        }, this);
        Ext.each(this.filterQueue, function (queuedFilterChange) {
            var filterId = queuedFilterChange.id;
            if (queuedFilterChange.checked) {
                ret.push(filterId);
            } else {
                var removedFilterIndex = ret.indexOf(filterId);
                if (removedFilterIndex === -1) {
                    return;
                }

                ret.splice(removedFilterIndex, 1);
            }
        });
        return ret;
    },
    filterEquals: function(f1, other)
    {
        return f1.id == other.id && f1.value_id == other.value_id && f1.value_name == other.value_name && f1.dimension_id == other.dimension_id && f1.realms.join('') == other.realms.join('');
    },
    initComponent: function () {
        var self = this;

        /**
         * A set of filter changes that have not yet been applied to a chart.
         *
         * This queue is applied when the user selects "OK" or "Preview".
         *
         * @type {Array}
         */
        this.filterQueue = [];

        this.originalFiltersStoreData = { data: [], total: 0};
        if(this.filtersStore)
        {
            var ret = [];

            this.filtersStore.each(function (record) {
                ret.push(record.data);
            });

            this.originalFiltersStoreData  = {
                data: ret,
                total: ret.length
            };
        }

        var origin_module = this.origin_module ? this.origin_module : '';
        var origin = this.origin_component ? this.origin_component + ' -> ' : '';

        var store = new CCR.xdmod.CustomJsonStore({
            url: 'controllers/metric_explorer.php',
            fields: ['checked', 'name', 'id'],
            root: 'data',
            totalProperty: 'totalCount',
            idProperty: 'name',
            messageProperty: 'message',
            scope: this,
            baseParams: {
                operation: 'get_dimension',
                dimension_id: this.dimension_id,
                realm: this.realms.join(','),
                public_user: CCR.xdmod.publicUser
            }
        });
        store.on('beforeload', function (t, op) {
            t.baseParams.selectedFilterIds = this.getSelectedFilterIds().join(',');
            dimensionGrid.showMask();
        }, this);
        store.on('load', function (t, op) {
            dimensionGrid.hideMask();
        }, this);
        store.on('exception', function (proxy, type, action, options, response) {
            dimensionGrid.hideMask();
            CCR.xdmod.ui.presentFailureResponse(response, {
                title: 'Filter List'
            });
        }, this);

        var onCheckChange = function (record) {

            self.filterQueue.push({
                id: self.dimension_id + '=' + record.data.id,
                value_id: record.data.id,
                value_name: record.data.name,
                dimension_id: self.dimension_id,
                realms: self.realms,
                checked: record.data.checked
            });

        };

        var applyQueuedFilters = function () {
            Ext.each(self.filterQueue, applyFilter);
            self.filterQueue = [];
        };

        var applyFilter = function (filter) {

            var index = self.filtersStore.findBy(function (r) {

                if (r.data.id == filter.id) {
                    return true;
                }

            }); //index


            if (index < 0) {
                var newFilterRecord =  new self.filtersStore.recordType(filter);
                self.filtersStore.addSorted(newFilterRecord);
            }else
            {
                var existingFilterRecord = self.filtersStore.getAt(index);
                if(!filter.checked && existingFilterRecord.data.checked)
                {
                    self.filtersStore.removeAt(index);
                }else
                {
                    existingFilterRecord.set('checked', filter.checked);
                }
            }


        };

        var checkColumn = new Ext.grid.CheckColumn({
            id: 'checked',
            width: 35,
            dataIndex: 'checked',
            scope: this,

            onMouseDown: function (e, t) {

                if (Ext.fly(t).hasClass(this.createId())) {

                    e.stopEvent();
                    var index = this.grid.getView().findRowIndex(t);
                    var record = this.grid.store.getAt(index);
                    record.set(this.dataIndex, !record.data[this.dataIndex]);

                    XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Toggled item in list', Ext.encode(record.data));

                    onCheckChange.call(this.scope, record);
                }

            }
        });

        var dimensionGrid = new Ext.grid.GridPanel({
            id: 'filter_dimensions_' + this.id,
            store: store,

            autoScroll: true,
            rowNumberer: true,
            border: true,
            stripeRows: true,
            enableHdMenu: false,
            hideHeaders: true,
            disableSelection: true,
            autoExpandColumn: 'name',
            scope: this,

            viewConfig: {
                forceFit: true,
                scrollOffset: 2 // the grid will never have scrollbars
            },
            plugins: [checkColumn, new Ext.ux.plugins.ContainerBodyMask({
                msg: 'Loading...',
                masked: true
            })],
            columns: [
                checkColumn, {
                    header: '',
                    width: 300,
                    sortable: false,
                    dataIndex: 'name',
                    id: 'name'
                }
            ],
            listeners: {
                'rowmousedown': function (t, rowIndex, e) {
                    var record = t.store.getAt(rowIndex);
                    record.set('checked', !record.data.checked);
                    XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Toggled item in list', Ext.encode(record.data));
                    onCheckChange.call(t.scope, record);
                }
            }
        });

        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: this.defaultPageSize,
            store: store,
            displayInfo: true,
            displayMsg: 'Items {0} - {1} of {2}',
            emptyMsg: "No data"
        });

        pagingToolbar.on('change', function (total, pageObj) {

           XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Loaded page of data', pageObj.activePage + ' of ' + pageObj.pages);

        });

        store.baseParams.start = pagingToolbar.start;
        store.baseParams.limit = pagingToolbar.pageSize;
        store.load();

        var searchField = new Ext.form.TwinTriggerField({
            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            hasSearch: false,
            enableKeyEvents: true,
            onTrigger1Click: function () {

                XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Cleared search field');

                if (this.hasSearch) {
                    this.el.dom.value = '';
                    store.baseParams.start = 0;
                    store.baseParams.limit = pagingToolbar.pageSize;
                    store.baseParams.search_text = '';
                    store.load();
                    this.triggers[0].hide();
                    this.hasSearch = false;
                }
            },

            onTrigger2Click: function () {

                XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Used search field', Ext.encode({search_text: this.getRawValue()}));

                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }
                store.baseParams.start = 0;
                store.baseParams.limit = pagingToolbar.pageSize;
                store.baseParams.search_text = v;
                store.load();
                this.hasSearch = true;
                this.triggers[0].show();
            },
            listeners: {
                'specialkey': function (field, e) {
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() == e.ENTER) {
                        searchField.onTrigger2Click();
                    }
                }
            }
        });

        Ext.apply(this, {
            title: '<img class="x-panel-inline-icon filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> Filter by ' + this.dimension_label,
            width: 450,
            height: 328,
            border: false,
            layout: 'border',
            bodyStyle: 'padding:5px 5px 0',
            buttonAlign: 'left',
            tbar: [
                '->',
                'Search:',
                searchField
            ],
            items: [{
                xtype: 'panel',
                border: false,
                region: 'center',
                layout: 'fit',
                items: dimensionGrid,
                bbar: pagingToolbar
            }],
            fbar: [{
                text: 'Clear All',
                tooltip: 'Disable all filters of this type.',
                handler: function (button, event) {
                    /**
                     * Get a view record corresponding to the given record data.
                     *
                     * If there is no corresponding record in the view's store,
                     * one will be created for the purposes of disabling a
                     * the filter.
                     *
                     * @param  {object} record An object containing filter data.
                     * @return {Ext.data.Record} A record for the view store.
                     */
                    var getViewRecord = function (record) {
                        // Find the filter's corresponding record in the view's
                        // store. If it does not have one, create one for
                        // purposes of disabling this filter.
                        var filterValue = record.value_id;
                        var viewRecordIndex = store.findExact('id', filterValue);
                        var viewRecord;
                        if (viewRecordIndex >= 0) {
                            viewRecord = store.getAt(viewRecordIndex);
                        } else {
                            viewRecord = new store.recordType({
                                id: filterValue,
                                name: record.value_name,
                                checked: true
                            });
                        }

                        return viewRecord;
                    };

                    // For every filter of the type handled by this
                    // panel, get a corresponding view store record,
                    // creating one if necessary.
                    var viewRecords = [];
                    self.filtersStore.each(function (record) {
                        // If the filter does not match the type of filter
                        // being handled by this panel, skip it.
                        if (record.get('dimension_id') !== self.dimension_id) {
                            return;
                        }

                        // Add the view store record to the list.
                        viewRecords.push(getViewRecord(record.data));
                    });

                    // For every enabled filter in the change queue,
                    // get a corresponding view store record.
                    var queuedFiltersToDisable = {};
                    Ext.each(self.filterQueue, function (queuedFilterChange) {
                        // If the filter has already been disabled by a queue
                        // entry, there is no need to disable it again.
                        var filterId = queuedFilterChange.id;
                        if (!queuedFilterChange.checked) {
                            delete queuedFiltersToDisable[filterId];
                            return;
                        }

                        // Add the filter change to the set to disable.
                        queuedFiltersToDisable[filterId] = queuedFilterChange;
                    });
                    Ext.iterate(queuedFiltersToDisable, function (filterId, queuedFilterChange) {
                        viewRecords.push(getViewRecord(queuedFilterChange));
                    });

                    // Set each view store record to unchecked and call the
                    // on check handler, which will handle disabling the filter
                    // in the same way clicking on it in the panel would.
                    Ext.each(viewRecords, function (viewRecord) {
                        viewRecord.set('checked', false);
                        onCheckChange.call(self, viewRecord);
                    });
                }
            },
            '->',
            {
                text: 'Preview',
                handler: function (b, e) {
                    XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Clicked on Preview');

                    applyQueuedFilters();
                }
            },
            {
                scope: this,
                text: 'Ok',
                handler: function (b, e) {
                    XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Clicked on Ok');

                    applyQueuedFilters();
                    b.scope.fireEvent('ok');
                }
            },{
                scope: this,
                text: 'Cancel',
                handler: function (b, e) {
                    var filtersChanged = (this.filtersStore.getCount() != this.originalFiltersStoreData.total);

                    if(false == filtersChanged) {
                        // The number of filters has not changed. Need to check the values of each.
                        var i = 0;
                        var orig = this.originalFiltersStoreData.data;
                        this.filtersStore.each(function (record) {
                            if( false == self.filterEquals(record, orig[i++]) ) {
                                filtersChanged = true;
                            }
                        });
                    }

                    if(true == filtersChanged) {
                        this.filtersStore.loadData(this.originalFiltersStoreData, false);
                    }
                    XDMoD.TrackEvent(origin_module, origin + 'Filter Pane -> Clicked on Cancel');
                    b.scope.fireEvent('cancel');
                }
            }]
        });

        CCR.xdmod.ui.FilterDimensionPanel.superclass.initComponent.apply(this, arguments);

        this.addEvents("ok");
        this.addEvents("cancel");
    }
});
