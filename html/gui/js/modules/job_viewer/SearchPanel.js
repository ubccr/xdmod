/**
 * The component that is responsible for building and submitting searches for
 * individual jobs in the 'Single Job Viewer'.
 *
 * It currently responds to the following events:
 *   - add_condition (realm, name, operator, value, valueId) : add a new search
 *     condition (where clause).
 *   - remove_condition (panel, record) : removes the provided record from the
 *     local data store.
 *   - perform_search (panel)           : currently a no-op.
 *   - cancel_search (panel)            : hides the search window.
 *   - close_search (panel)             : hides the search window, also clears
 *                                        the local data store.
 *   - reset_criteria (panel)           : resets the search criteria.
 *   - realm_selected (realm)           : indicates that a 'realm' was selected
 *                                        in the user interface.
 *   - field_selected (field)           : indicates that a 'field' was selected
 *                                        in the user interface.
 **/
XDMoD.Module.JobViewer.SearchPanel = Ext.extend(Ext.Panel, {

    /**
     * The default number of results to retrieve during paging operations.
     *
     * @var {Number}
     */
    _DEFAULT_PAGE_SIZE: 24,

    _DEFAULT_SEARCH_NAME_SIZE: 2,

    /**
     * Default constructor, just used to setup the events to be listened for,
     * some sane defaults, as well as the child components.
     * properties of note:
     *    searchStore: local Ext.data.ArrayStore() used to store the search
     *                 terms before submitting as a 'search'.
     *     realmField: Combobox containing the realms available for selection.
     *    searchField: Combobox dependent on 'realmField' selection. Provides
     *                 a selection of dimensions available for selection.
     *     valueField: Combobox dependent on 'searchField' selection. Provides
     *                 a selection of dimension values available for selection.
     **/
    initComponent: function () {
        var self = this;

        this.addEvents(
                'add_condition',
                'remove_condition',
                'perform_search',
                'cancel_search',
                'close_search',
                'reset_criteria',
                'realm_selected',
                'field_selected'
        );

        this.resultsStore = this._createResultsStore();

        this.selected = {};
        this.children = [];

        this.editing = false;

        XDMoD.Module.JobViewer.SearchPanel.superclass.initComponent.apply(
            Ext.apply(this, {
                    layout: 'table',
                    width: 1000,
                autoScroll: true,
                    border: false,
                    layoutConfig: {
                        columns: 2
                    },
                    style: 'background-color: #D0D0D0;',
                    defaults: {
                        frame: false,
                        border: false,
                        width: 500,
                        height: 300
                    },
                    items: self._getItems()
                }), arguments
        );

        this.resetResults = function() {
            self.resultsStore.loadData({results:[], totalCount: 0}, false);
            self.selected = {};
        };

    }, // initComponent

    listeners: {

        /**
         * Fired when this component is first rendered to the page. We use it
         * to setup event forwarding of the 'load' event from the search_results
         * component to this components bottom toolbar. We do that here because
         * it's only at this point that both components are available.
         */
        render: function () {
            var searchResults = Ext.getCmp('search_results');
            var bbar = searchResults ? searchResults.getBottomToolbar() : null;
            if (bbar) {
                bbar.relayEvents(this.resultsStore, ['load']);
            }
        }, // render

        /**
         * This is the 'show' event that has been forwarded from the containing
         * window.
         * @param {Ext.Window} window
         **/
        show: function (window) {
            var body = Ext.getBody();
            var bodyBox = body.getBox();
            var bodyHeight = bodyBox.height;
            var bodyWidth = bodyBox.width;

            var box = window.getBox();
            var x = box.x;
            var y = box.y;
            var height = box.height;
            var width = box.width;

            var adjHeight = Math.min(bodyHeight, height);
            var adjWidth = Math.min(bodyWidth, width);
            var adjX = x < 0 ? 0 : x;
            var adjY = y < 0 ? 0 : y;

            window.setHeight(adjHeight);
            window.setWidth(adjWidth);
            window.setPosition(adjX, adjY);

            this.shown = true;
            if (!this.children) {
                this.children = [];
            }

            this.fireEvent('validate');
            this.fireEvent('validate_search_criteria');
            if (!this.editing) {
                this.ownerCt.setTitle("Search");
            }
        }, // show

        /**
         * Forwarded 'move' event from this panel's Ext.Window parent.
         * This will keep the window from being moved outside of the users
         * visible space.
         *
         * @param {Ext.Window} window the window object that was moved.
         * @param {Number}     x      new x coordinate of the window.
         * @param {Number}     y      new y coordinate of the window.
         **/
        move: function(window, x, y) {
            var adjX = x < 0 ? 0 : x;
            var adjY = y < 0 ? 0 : y;

            if ((adjX === 0 && adjX !== x) ||
                (adjY === 0 && adjY !== y)) {
                window.setPosition(adjX, adjY);
            }
        },

        /**
         * Forwarded 'hide' event from this panel's Ext.Window parent.
         * we capture it here so that we can clean up the UI and have
         * it ready for its next use.
         **/
        hide: function() {
            this.fireEvent('close_search', this, false);
        },

        /**
         *
         * @param {String} realm
         * @param {String} field
         * @param {String} fieldDisplay
         * @param {String} operator
         * @param {String} value
         * @param {String} valueId
         */
        add_condition: function (realm, field, fieldDisplay, operator, value, valueId) {
            var record = new this.searchStore.recordType({
                realm: realm,
                field: field,
                fieldDisplay: fieldDisplay,
                operator: operator,
                value: value,
                valueId: valueId
            });
            this.searchStore.add(record);
        }, // add_condition

        /**
         * Indicates that this component should remove the provided record from
         * it's search store.
         *
         * @param {Ext.data.Record} record
         */
        remove_condition: function (record) {
            if (CCR.isType(record, CCR.Types.Object)) {
                this.searchStore.remove(record);
            }
        }, // remove_condition

        /**
         * Indicates that this component should cancel the currently active
         * search.
         *
         * @param panel
         */
        cancel_search: function (/*panel*/) {
            this.ownerCt.hide();
        }, // cancel_search

        /**
         * Indicates that the user has requested a search be performed.
         *
         * @param panel
         */
        search_requested: function (panel, searchType, searchParams) {
            var self = this;
            if(!this.loadMask) {
                this.loadMask = new Ext.LoadMask(
                    panel.getEl(),
                    {
                        id: 'job-viewer-search-mask',
                        store: this.resultsStore
                    });
            }

            panel.resetResults();

            this.resultsStore.load({
                params: searchParams,
                callback: function (records, options, success) {

                    var resultsGrid, saveButton, text, params, prefix;

                    if(!success) {
                        // Store load failure is handled by the exception listener
                        return;
                    }

                    // Cache the search parameters in the store object for use if the
                    // search is saved (ExtJs does not provide a native call to do this).
                    self.resultsStore.searchParams = searchParams;

                    resultsGrid = Ext.getCmp('search_results');

                    if(self.resultsStore.getTotalCount() === 0) {
                        resultsGrid.setDisabled(true);
                        resultsGrid.getEl().mask(searchType + ' returned zero jobs.', 'x-mask');
                    } else {
                        resultsGrid.getEl().unmask();
                        resultsGrid.setDisabled(false);

                        if (searchType == 'Lookup') {
                            // Since the Quick Lookup search is designed to find an exact job,
                            // automatically select the jobs returned 
                            self.resultsStore.each(function (record) {
                                record.set('included', true);
                                text = record.get('text');
                            });
                            var resource = self._getFieldDisplayValue(Ext.getCmp('basic-resource'));
                            var localJobId = self._getFieldDisplayValue(Ext.getCmp('basic-localjobid'));
                            params = [resource, localJobId];
                        } else if (searchType == 'Search') {
                            params = [
                                'search',
                                self._formatDate(new Date()),
                                Math.floor((Math.random() * 1024) + 1)
                            ];
                        }

                        var searchNameField = Ext.getCmp('job-viewer-search-name');
                        var searchName = searchNameField.getValue();

                        if (!searchName) {
                            if (!params) {
                                params = JSON.parse(searchParams.params);
                            }
                            searchNameField.setValue(self._generateDefaultName(params, prefix));
                            searchNameField.focus(true);
                        }

                        self.fireEvent('validate');
                    }
                }
            });
        }, // search_requested

        basic_search_realm_selected: function (realm) {
            var basicSearch = Ext.getCmp('job-viewer-search-lookup');
            basicSearch.disable();
            var jobidField = Ext.getCmp('basic-localjobid');
            jobidField.reset();
            var resource = Ext.getCmp('basic-resource');
            resource.store.load({
                params: {
                    realm: realm
                }
            });
        },

        /**
         * Event fired when a realm has been selected.
         *
         * @param realm
         */
        realm_selected: function (realm) {
            Ext.getCmp('search-add').disable();
            Ext.getCmp('job-viewer-search-search').disable();
            this.searchStore.removeAll();
            this.valueField.store.setBaseParam('realm', realm);
            this.searchField.reset();
            this.searchField.store.load({
                params: {
                    realm: realm
                }
            });
        },

        validate_search_criteria: function() {
            var lookupValid, searchValid;

            lookupValid = Ext.getCmp('basic-resource').getValue().toString().length > 0 &&
                Ext.getCmp('basic-localjobid').getValue().toString().length > 0;

            Ext.getCmp('job-viewer-search-lookup').setDisabled(!lookupValid);

            searchValid = this.searchStore.getCount() > 0 &&
                Ext.getCmp('search_start_date').isValid() &&
                Ext.getCmp('search_end_date').isValid();

            Ext.getCmp('job-viewer-search-search').setDisabled(!searchValid);
        },

        /**
         * Indicates that the UI should be validated. If it is currently not in
         * a valid state the the user should be notified.
         *
         */
        validate: function (options) {
            if (this.shown === true) {
                this._validateResults(options);
            }
        }, // validate

        /**
         * Indicates that the provided field was selected and as such if the
         * field has a child field ( field whose values depend on this fields
         * value ) then update the child fields parameters, remove all current
         * values and let the user pick from the possibly new values.
         *
         * @param {Object} field that has been selected.
         */
        field_selected: function (field) {
            var self = this;
            if (CCR.exists(self.valueField) && CCR.exists(self.valueField.store)) {
                self.valueField.store.proxy.setUrl(self.valueField.store.proxy.url + ('/' + field));

                self.valueField.store.removeAll();
                self.valueField.setValue(null);
                self._selectInitial('search-value');
            }
        }, // field_selected

        /**
         * Indicates that this component should reset search and value field
         * components of the provided panel.
         *
         * @param panel
         */
        reset_criteria: function (panel, all) {
            if (CCR.exists(panel)) {
                var field = CCR.exists(panel.searchField) ? panel.searchField : null;
                var value = CCR.exists(panel.valueField) ? panel.valueField : null;
                var add = Ext.getCmp('search-add');

                if (CCR.exists(value) && CCR.exists(value.setValue)) {
                    value.setValue(null);
                    if (CCR.exists(add)) {
                        add.disable();
                    }
                }

                if (all) {
                    if (CCR.exists(field) && CCR.exists(field.setValue)) {
                        field.setValue(null);
                    }
                }
            }
        }, // reset_criteria

        /**
         * Indicates that the user wishes to close this component. We need to
         * reset everything so that it's ready for use the next time the window
         * is opened.
         *
         * @param panel
         * @param reload
         */
        close_search: function (panel, reload) {
            if (CCR.exists(panel)) {
                reload = reload || false;

                // HIDE: this window.
                panel.ownerCt.hide();

                // CHECK: if we should be reloading
                if (reload) {
                    this.jobViewer.fireEvent('reload_root');
                    this.jobViewer.fireEvent('activate');
                }

                panel.searchStore.removeAll();
                panel.resetResults();

                var basicResource = Ext.getCmp('basic-resource');
                var basicJobNumber = Ext.getCmp('basic-localjobid');

                if(basicResource) {
                    basicResource.setValue('');
                }
                if(basicJobNumber) {
                    basicJobNumber.setValue('');
                }

                var resultsGrid = Ext.getCmp('search_results');
                if(resultsGrid) {
                    resultsGrid.getEl().unmask();
                    resultsGrid.setDisabled(true);
                }

                var searchField = Ext.getCmp('search-field');
                searchField.setValue(null);
                var searchValue = Ext.getCmp('search-value');
                searchValue.setValue(null);
                var searchNameField = Ext.getCmp('job-viewer-search-name');
                searchNameField.setValue('');
                searchNameField.clearInvalid();

                this.shown = false;
                this.editing = false;

                delete this.dtype;
                delete this.dtypeId;
                delete this.children;
            }
        }, // close_search

        /**
         * An event that can be called when a user wishes to 'edit' an already
         * existing search. It accepts the {Ext.tree.AsyncTreeNode} value from
         * the SearchHistoryTree and then sets up the Search Panel for the
         * type of Search that was performed. Making sure that all fo the user
         * modifiable values are set per the node passed in.
         *
         * @param {Ext.tree.AsyncTreeNode} node
         **/
        edit_search: function(node) {
            this.editing = true;
            var params = node.attributes;

            if (!node.attributes.searchterms.params) {
                CCR.error('Error Viewing Search', 'Unable to view search, no data provided.');
                return false;
            }

            this.title = params.text;
            this.dtype = params.dtype;
            this.dtypeId = params[this.dtype];
            this._retrieveSelected(node);

            this.ownerCt.setTitle("Editing Search: " + this.title);
            Ext.getCmp('job-viewer-search-name').setValue(this.title);

            if (!this.ownerCt.isVisible()) {
                this.ownerCt.show();
            }

            if (params.searchterms.params.start_date) {
                this._editAdvancedSearch.call(this, params.searchterms.params);
            } else {
                this._editQuickSearch.call(this, params.searchterms.params);
            }

            return true;
        } // edit_search
    }, // listeners

    /**
     * A private helper function that determines whether or not the state of the
     * results store is considered 'valid' such that the 'Save Results' button
     * should be enabled.
     *
     * @param {Object} options
     **/
    _validateResults: function(options) {
        options = options || {};
        var validate = options.validate !== undefined ? options.validate : false;
        if (this.resultsStore) {
            var field = Ext.getCmp('job-viewer-search-name');

            var searchNameIsValid = options.name && validate
                    ? field.validateValue(options.name)
                    : validate
                    ? field.isValid()
                    : true;

            var valid = this.resultsStore.getCount() > 0 &&
                    this._getSelectedRecords().length > 0 &&
                    searchNameIsValid;

            var saveResults = Ext.getCmp('job-viewer-search-save-results');
            var saveAs = Ext.getCmp('job-viewer-search-save-as');

            this._toggle(valid, saveResults);
            this._toggle(valid && this.editing === true, saveAs);
        }
    },

    /**
     * Selects the initial item for a component to the record identified by the index provided by the user.
     * This method requires that the identified component have a function called 'getStore' that supports a function
     * 'getCount' to work as intended.
     *
     * @param {String} id of the component that has a 'store' ( ie. Ext.form.ComboBox ) whose value you want to set.
     * @param {null|Number} index of the initial record to set. Defaults to 0.
     * @param {null|Function} callback to be executed when this function is complete.
     * @private
     */
    _selectInitial: function (id, index, callback) {
        callback = callback || function () {
                };

        if (CCR.exists(id)) {
            var field = Ext.getCmp(id);
            var store = CCR.exists(field) && CCR.exists(field.getStore) ? field.getStore() : null;
            var hasRecords = CCR.exists(store) && CCR.exists(store.getCount) ? store.getCount() > 0 : false;

            /**
             *
             * @param {Ext.data.ArrayStore} store
             * @param {Ext.form.ComboBox} field
             * @param {Function} callback
             */
            var selectRecord = function (store, field, callback) {
                if(index === undefined) {
                    return;
                }
                var record = store.getAt(index);
                if (field.isExpanded()) {
                    field.select(index, true);
                } else {
                    var getName = function (record) {
                        if (CCR.exists(record)) {
                            var properties = ['name', 'short_name'];
                            for (var property in properties) {
                                if(properties.hasOwnProperty(property)) {
                                    var value = record.get(properties[property]);
                                    if (CCR.exists(value)) {
                                        return value;
                                    }
                                }
                            }
                        }
                        return "";
                    };
                    var text = getName(record);
                    field.setValue(text);
                    field.selectedIndex = index;
                }
                field.fireEvent('select', field, record, index);
                callback(record);
            };

            if (hasRecords) {
                selectRecord(store, field, callback);
            } else {
                store.load({
                    callback: function (/*records, options*/) {
                        selectRecord(store, field, callback);
                    }
                });
            }
        }
    }, // select_initial

    /**
     * Format the provided date value in a such a way that the ExtJS Date
     * components can deal with it.
     *
     * @param {Date} value
     * @returns {string}
     * @private
     */
    _formatDate: function (value) {
        if (!CCR.isType(value, '[object Date]')) {
            return String(value);
        }

        var pad = CCR.pad;
        var left = CCR.STR_PAD_LEFT;

        var year = value.getFullYear();
        var month = value.getMonth();
        var date = value.getDate();

        var dates = [
            year,
            pad(String(month + 1), 2, '0', left),
            pad(String(date), 2, '0', left)
        ];

        return dates.join('-');
    }, // _formatDate

    /**
     * Helper function that returns a new JsonStore that is suitable for use
     * as this components result store.
     *
     * @returns {*}
     * @private
     */
    _createResultsStore: function () {
        var self = this;

        return new Ext.data.JsonStore({
            id: 'results_store',
            url: XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/search/jobs',
            proxy: new Ext.data.HttpProxy({
                api: {
                    read: {
                        method: 'GET',
                        url   : XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/search/jobs'
                    }
                }
            }),
            root: 'results',
            totalProperty: 'totalCount',
            fields: [
                {name: 'dtype', mapping: 'dtype', type: 'string'},
                {name: 'jobid', mapping: 'jobid', type: 'int'},
                { name: 'local_job_id', mapping: 'local_job_id', type: 'string' },
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'realm', mapping: 'realm', type: 'string'},
                {name: 'resource', mapping: 'resource', type: 'string'},
                {name: 'text', mapping: 'text', type: 'string'},
                {name: 'included', mapping: 'included', type: 'bool', defaultValue: false},
                {name: 'username', mapping: 'username', type: 'string'}
            ],
            listeners: {

                /**
                 *
                 * @param {Ext.data.Store} store
                 * @param {[]Ext.data.Record} records
                 * @param {Object} params
                 **/
                load: function(store, records /*, params*/) {
                    for ( var i = 0; i < records.length; i++) {
                        var record = records[i];
                        var id = self._getId(record);
                        var checked = self.children.indexOf(id) >= 0;
                        self._handleIncludeRecord(record, checked);
                    }
                    self.fireEvent('validate');
                },

                exception : function (proxy, type, action, options, response /*, arg*/) {
                    var data = JSON.parse(response.responseText);
                    var status = response.status;
                    var message = data.message || 'An error occurred while attempting to execute the requested operation. Response Code: [' + status + ']';
                    Ext.MessageBox.show({
                        title  : 'Error: Performing Search',
                        msg    : message,
                        icon   : Ext.MessageBox.ERROR,
                        buttons: Ext.MessageBox.OK
                    });

                }
            }
        });
    }, // _getResultsStore

    /**
     * Returns an ArrayStore that is suitable for use as this components
     * search criteria store.
     *
     * @returns {*}
     * @private
     */
    _getSearchStore: function () {
        var self = this;
        if (this.searchStore === null || this.searchStore === undefined) {
            this.searchStore = new Ext.data.ArrayStore({
                id: 'search-grid-store',
                proxy: new Ext.data.MemoryProxy(),
                fields: ['realm', 'field', 'fieldDisplay', 'operator', 'value', 'valueId'],
                listeners: {
                    remove: function(/*store, record, index*/) {
                        self.fireEvent('validate_search_criteria');
                    },
                    add: function(/*store, records, index*/) {
                        self.fireEvent('validate_search_criteria');
                    }
                }
            });
        }
        return this.searchStore;
    }, // _getSearchStore

    /**
     * Retrieve the value of the date field identified by the provided prefix.
     *
     * @param {String} prefix
     * @returns {*}
     * @private
     */
    _getDateValue: function (prefix) {
        if (!CCR.exists(prefix) || typeof prefix !== 'string' || prefix.length < 1) {
            return null;
        }

        var dateField = Ext.getCmp(prefix + '_date');

        var date = dateField.getValue();

        return date;
    }, // _getDateValue

    /**
     * Retrieve all of the currently 'selected' records across all pages.
     *
     * @returns {*}
     * @private
     */
    _getSelectedRecords: function () {
        var included = this.resultsStore.query('included', true);

        var existingselections = {};
        for(var i = 0; i < included.items.length; i++) {
            existingselections[ included.items[i].data[included.items[i].data.dtype] ] = 1;
        }

        for (var key in this.selected) {
            if (this.selected.hasOwnProperty(key) && !existingselections.hasOwnProperty(key)) {
                included.add(key, this.selected[key]);
            }
        }

        return included;
    }, // _getSelectedRecords

    /**
     * Returns an array of objects that only contain a particular set of properties.
     *
     * @param values
     * @returns {Array}
     * @private
     */
    _resultsToJSON: function (values) {
        if (CCR.exists(values) && CCR.exists(values.length) && values.length > 0) {
            var results = [];

            var attributes = ['resource', 'name', 'jobid', 'text', 'realm', 'dtype', 'local_job_id'];
            for (var i = 0; i < values.length; i++) {
                var value = values.get(i);
                var temp = {};
                for (var j = 0; j < attributes.length; j++) {
                    var attribute = attributes[j];
                    if (CCR.exists(attribute)) {
                        temp[attribute] = value.get(attribute);
                    }
                }
                results.push(temp);
            }
            return results;
        }
        return [];
    }, // resultsToJSON

    /**
     * Return an array of ExtJS components to be used as this components
     * items property.
     *
     * @returns {*[]}
     * @private
     */
    _getItems: function () {
        var self = this;

        var checkColumn = new Ext.grid.CheckColumn({
            header: 'Include',
            dataIndex: 'included',
            id: 'included',
            width: 55,
            checkchange: function (record, dataIndex, checked) {
                self._handleIncludeRecord(record, checked);
                self.fireEvent('validate');
            }
        });

        return [
            {
                xtype: 'panel',
                layout: 'border',
                height: 48,
                width: 502,
                colspan: 1,
                border: true,
                style: 'margin-left: -2px; margin-top: -2px; background-color: #D0D0D0;',
                items: [
                    {
                        xtype: 'fieldset',
                        region: 'center',
                        border: false,
                        items: [
                            new XDMoD.RealTimeValidatingTextField({
                                id: 'job-viewer-search-name',
                                region: 'center',
                                emptyText: 'Enter Search Name...',
                                width: 354,
                                allowBlank: false,
                                style: 'margin-right: 20px',
                                fieldLabel: 'Search Name',
                                tooltip: {
                                    text: 'the name value for the current search',
                                    xtype: 'quicktip'
                                }
                            })
                        ]
                    }
                ]
            },
            {
                title: "Results",
                id: 'search_results_container',
                xtype: "panel",
                height: 582,
                rowspan: 3,
                layout: 'border',
                listeners: {
                    activate: function() {
                        if (self.rendered) {
                            self.fireEvent('validate');
                        }
                    }
                },
                items: [
                    {
                        xtype: 'editorgrid',
                        id: 'search_results',
                        region: 'center',
                        autoExpandColumn: 'name',
                        plugins: [checkColumn],
                        loadMask: false,
                        store: this.resultsStore,
                        disabled: true,
                        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                        listeners: {
                            rowclick: function(searchgrid, rowIndex) {
                                var record = searchgrid.store.getAt(rowIndex);
                                var checked = !record.get('included');
                                record.set('included', checked);
                                checkColumn.checkchange(record, rowIndex, checked);
                            }
                        },
                        columns: [
                            checkColumn,
                            {
                                header: 'Job Id',
                                dataIndex: 'local_job_id'
                            },
                            {
                                header: 'Resource',
                                dataIndex: 'resource',
                                width: 140,
                                id: 'resource'
                            },
                            {
                                header: 'Name',
                                dataIndex: 'name',
                                id: 'name',
                                width: 210
                            }
                        ],
                        bbar: new Ext.PagingToolbar({
                            pageSize: self._DEFAULT_PAGE_SIZE,
                            displayInfo: true,
                            displayMsg: 'Displaying jobs {0} - {1} of {2}',
                            emptyMsg: 'No jobs to display',
                            store: self.resultsStore,
                            listeners: {
                                load: function (store, records, options) {
                                    this.onLoad(store, records, options);
                                },
                                beforechange: function(bbar, params) {
                                    var searchParams = bbar.store.searchParams;
                                    for(var p in searchParams) {
                                        if(searchParams.hasOwnProperty(p) && !params.hasOwnProperty(p)) {
                                            params[p] = searchParams[p];
                                        }
                                    }
                                }
                            }
                        })
                    }
                ]
            },
            {
                xtype: 'panel',
                title: 'Quick Job Lookup',
                tools: [{
                    id: 'help',
                    qtip: 'Use the quick lookup form to search for a job based on its ID and the resource on which it ran.'
                }],
                layout: 'border',
                height: 160,
                items: [{
                    xtype: 'fieldset',
                    region: 'center',
                    items: [{
                        xtype: 'realmcombo',
                        id: 'basic-search-realm',
                        panel: self,
                        realmSelectEvent: 'basic_search_realm_selected'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: 'Resource',
                        id: 'basic-resource',
                        emptyText: 'Select a Resource',
                        triggerAction: 'all',
                        selectOnFocus: true,
                        displayField: 'long_name',
                        valueField: 'id',
                        typeAhead: true,
                        mode: 'local',
                        forceSelection: true,
                        enableKeyEvents: true,
                        store: new Ext.data.JsonStore({
                            proxy: new Ext.data.HttpProxy({
                                url: XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/dimensions/resource',
                                method: 'GET'
                            }),
                            baseParams: {
                                realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
                                token: self.token
                            },
                            storeId: 'jobviewer-basicsearch-resource',
                            autoLoad: true,
                            root: 'results',
                            fields: [
                                {name: 'id', type: 'string'},
                                {name: 'short_name', type: 'string'},
                                {name: 'long_name', type: 'string'}
                            ],
                            listeners: {
                                exception: function (proxy, type, action, options, response) {
                                    CCR.xdmod.ui.presentFailureResponse(response, {
                                        title: 'Job Viewer',
                                        wrapperMessage: 'The Quick Job Lookup resource list failed to load.'
                                    });
                                }
                            }
                        }),
                        listeners: {
                            select: function( /* combo, record, index */ ) {
                                self.fireEvent('validate_search_criteria');
                            },
                            blur: function( /*combo, event*/ ) {
                                self.fireEvent('validate_search_criteria');
                             }
                        }
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Job Number',
                        emptyText: 'Enter Job #',
                        id: 'basic-localjobid',
                        stripCharsRe: /(^\s+|\s+$)/g,
                        width: 200,
                        enableKeyEvents: true,
                        listeners: {
                            keyup: function( /*field, event*/ ) {
                                self.fireEvent('validate_search_criteria');
                            },
                            specialkey: function(field, event) {
                                if (event.getKey() === event.ENTER) {
                                    self.fireEvent('validate_search_criteria');
                                    return false;
                                }
                            }
                        }
                    }],
                    buttons: [{
                        xtype: 'button',
                        disabled: true,
                        text: 'Search',
                        id: 'job-viewer-search-lookup',
                        handler: function( /*button, event*/ ) {
                            var resourceField = Ext.getCmp('basic-resource');
                            var localjobidField = Ext.getCmp('basic-localjobid');
                            var realmField = Ext.getCmp('basic-search-realm');
                            var params = {
                                realm: realmField.getValue(),
                                params: JSON.stringify({
                                    resource_id: resourceField.getValue(),
                                    local_job_id: localjobidField.getValue()
                                })
                            };
                            self.fireEvent('search_requested', self, 'Lookup', params);
                        }
                    }]
                }]
            },
            {
                xtype: 'panel',
                id: 'job-viewer-advanced-search',
                title: 'Advanced Search',
                tools: [{
                    id: 'help',
                    qtip: 'Use the advanced search form to search for jobs based on one or more filters and a date range.'
                }],
                height: 375,
                layout: 'border',
                items: [{
                    region: 'center',
                    id: 'criteria_advanced',
                    xtype: 'fieldset',
                    labelWidth: 55,
                    items: [{
                        xtype: 'datefield',
                        id: 'search_start_date',
                        format: 'Y-m-d',
                        name: 'start_date',
                        fieldLabel: 'Start',
                        enableKeyEvents: true,
                        submitValue: false,
                        update: false,
                        validator: function(/*val*/) {
                            return self._dateFieldValidator('startDateField', 'start date');
                        },
                        listeners: {
                            beforerender: function (field) {
                                field.setValue(self._getDefaultStartDate());
                            },
                            keyup: function (/*field, record, index*/) {
                                self.fireEvent('validate_search_criteria');
                            },
                            select: function (/*field, record, index*/) {
                                self.fireEvent('validate_search_criteria');
                            }
                        }
                    },
                    {
                        xtype: 'datefield',
                        id: 'search_end_date',
                        name: 'end_date',
                        format: 'Y-m-d',
                        fieldLabel: 'End',
                        submitValue: false,
                        enableKeyEvents: true,
                        update: false,
                        validator: function(/*val*/) {
                            return self._dateFieldValidator('endDateField', 'end date');
                        },
                        listeners: {
                            beforerender: function (field) {
                                field.setValue(self._getDefaultEndDate());
                            },
                            keyup: function (/*field, record, index*/) {
                                self.fireEvent('validate_search_criteria');
                            },
                            select: function (/*field, record, index*/) {
                                self.fireEvent('validate_search_criteria');
                            }
                        }
                    },
                    {
                        id: 'realm-field',
                        xtype: 'realmcombo',
                        panel: self,
                        realmSelectEvent: 'realm_selected'
                    },
                    {
                        xtype: 'compositefield',
                        fieldLabel: 'Filter',
                        defaults: {
                            margins: '0 8 26 0'
                        },
                        items: [

                            {
                                id: 'search-field',
                                xtype: 'uxgroupcombo',
                                emptyText: 'Select a Field...',
                                triggerAction: 'all',
                                selectOnFocus: true,
                                displayField: 'name',
                                width: 175,
                                valueField: 'id',
                                typeAhead: true,
                                mode: 'local',
                                forceSelection: true,
                                groupTextTpl: '<span style="font-weight: bold;">{text}</span>',
                                tpl: '<tpl for="."><div ext:qtip="{description}" class="x-combo-list-item">{name}</div></tpl>',
                                store: new Ext.data.GroupingStore({
                                    proxy: new Ext.data.HttpProxy({
                                        method: 'GET',
                                        url: XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/dimensions'
                                    }),
                                    baseParams: {
                                        token: self.token,
                                        realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
                                        querygroup: 'tg_usage'

                                    },
                                    sortInfo: {
                                        field: 'name',
                                        direction: 'ASC'
                                    },
                                    groupField: 'Category',
                                    autoLoad: true,
                                    storeId: 'dimensionResults',
                                    reader: new Ext.data.JsonReader({
                                        root: 'results',
                                        idParameter: 'id',
                                        fields: ['id', 'name', 'Category', 'description']
                                    }),
                                    listeners: {
                                        exception: function (proxy, type, action, exception, response) {
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
                                        }
                                    }
                                }),
                                listeners: {
                                    select: function (field, record /*, index*/) {
                                        if (CCR.exists(record)) {
                                            var value = record.get('id');
                                            self.fireEvent('field_selected', value);
                                        }

                                    },
                                    afterrender: function (field) {
                                        if (!CCR.exists(self.searchField)) {
                                            self.searchField = field;
                                        }
                                    }
                                }
                            },
                            {
                                id: 'search-operator',
                                xtype: 'label',
                                text: '=',
                                style: 'margin-top: 4px; font-weight: bold',
                                listeners: {
                                    afterrender: function (field) {
                                        if (!CCR.exists(self.operatorField)) {
                                            self.operatorField = field;
                                        }
                                    }
                                }
                            },
                            {
                                id: 'search-value',
                                xtype: 'combo',
                                emptyText: 'Select a Value...',
                                triggerAction: 'all',
                                selectOnFocus: true,
                                displayField: 'long_name',
                                width: 170,
                                valueField: 'id',
                                typeAhead: true,
                                mode: 'local',
                                forceSelection: true,
                                enableKeyEvents: true,
                                store: new Ext.data.JsonStore({
                                    proxy: new Ext.data.HttpProxy({
                                        method: 'GET',
                                        url: XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/dimensions'
                                    }),
                                    baseParams: {
                                        token: self.token,
                                        querygroup: 'tg_usage',
                                        realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
                                        filter: 'true'
                                    },
                                    storeId: 'valueStore',
                                    idProperty: 'id',
                                    root: 'results',
                                    fields: [
                                        {name: 'id', type: 'string'},
                                        {name: 'name', type: 'string'},
                                        {name: 'short_name', type: 'string'},
                                        {name: 'long_name', type: 'string'}
                                    ],
                                    listeners: {
                                        exception: function (proxy, type, action, exception, response) {
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
                                        }
                                    }
                                }),
                                listeners: {
                                    afterrender: function (field) {
                                        if (!CCR.exists(self.valueField)) {
                                            self.valueField = field;
                                        }
                                    },
                                    select: function (field, record /*, index*/) {
                                        if (CCR.exists(record)) {
                                            var addCmp = Ext.getCmp('search-add');
                                            if (addCmp) {
                                                addCmp.enable();
                                            }
                                        }
                                    },
                                    keyup: function(field /*, event*/) {
                                        var value = field.el.dom.value;
                                        var addCriteriaCmp = Ext.getCmp('search-add');
                                        if (!CCR.exists(value)) {
                                            addCriteriaCmp.disable();
                                        } else {
                                            addCriteriaCmp.enable();
                                        }
                                    }
                                }
                            },
                            {
                                id: 'search-add',
                                xtype: 'button',
                                disabled: true,
                                text: 'Add',
                                handler: function (button /*, event*/ ) {
                                    var realmField = Ext.getCmp('realm-field');
                                    var searchField = Ext.getCmp('search-field');
                                    var searchValue = Ext.getCmp('search-value');

                                    var realm = realmField ? realmField.getValue() : null;
                                    var fieldRecord = searchField ? searchField.store.getAt(searchField.selectedIndex) : null;
                                    var field = fieldRecord ? fieldRecord.get('id') : null;
                                    var fieldDisplay = fieldRecord ? fieldRecord.get('name') : null;
                                    var operator = '=';
                                    if (searchValue.selectedIndex >= 0) {
                                        var searchRecord = searchValue.store.getAt(searchValue.selectedIndex);
                                        var valueName = searchRecord.get('short_name');
                                        var value = searchRecord.get('id');

                                        self.fireEvent('add_condition', realm, field, fieldDisplay, operator, valueName, value);
                                        self.fireEvent('reset_criteria', self);
                                    } else {
                                        searchValue.setValue(null);
                                        button.disable();
                                    }
                                }
                            }
                        ]
                    },
                    {
                        xtype: 'panel',
                        layout: 'fit',
                        height: 200,
                        items: [
                            {
                                xtype: 'grid',
                                id: 'job-viewer-search-criteria-grid',
                                region: 'center',
                                autoExpandColumn: 'value',
                                store: this._getSearchStore(),
                                columns: [
                                    {
                                        id: 'field',
                                        width: 200,
                                        header: 'Field',
                                        dataIndex: 'fieldDisplay'
                                    },
                                    {
                                        id: 'operator',
                                        width: 57,
                                        header: 'Operator',
                                        dataIndex: 'operator'
                                    },
                                    {
                                        id: 'value',
                                        width: 200,
                                        header: 'Value',
                                        dataIndex: 'value'
                                    },
                                    {
                                        xtype: 'actioncolumn',
                                        width: 25,
                                        items: [
                                            {
                                                icon: '../../../gui/images/delete.png',
                                                handler: function (grid, rowIndex /*, colIndex*/) {
                                                    var record = grid.store.getAt(rowIndex);
                                                    self.fireEvent('remove_condition', record);
                                                }
                                            }
                                        ]
                                    }

                                ]
                            }
                        ]

                    }],
                    buttons: [{
                        xtype: 'button',
                        text: 'Search',
                        id: 'job-viewer-search-search',
                        disabled: true,
                        handler: function( /*button, event*/ ) {
                            var startDate = Ext.getCmp('search_start_date').getValue();
                            var endDate = Ext.getCmp('search_end_date').getValue();
                            var realmField = Ext.getCmp('realm-field');

                            var searchParams = {};
                            var total = self.searchStore.getCount();
                            for (var i = 0; i < total; i++) {
                                var searchParam = self.searchStore.getAt(i);

                                var field = searchParam.get('field');
                                var value = searchParam.get('valueId');

                                if (!searchParams[field]) {
                                    searchParams[field] = [];
                                }
                                searchParams[field].push(value);
                            }

                            var params = {
                                'start_date': self._formatDate(startDate),
                                'end_date': self._formatDate(endDate),
                                'realm': realmField.getValue(),
                                'limit': self._DEFAULT_PAGE_SIZE,
                                'start': 0,
                                'params': JSON.stringify(searchParams)
                            };
                            self.fireEvent('search_requested', self, 'Search', params);
                        }
                    }]
                }]

        },
        {
            xtype: 'toolbar',
            colspan: 2,
            width: 1000,
            height: 27,
            minHeight: 27,
            items: [
                '->',
                {
                    xtype: 'button',
                    id: 'job-viewer-search-save-as',
                    text: 'Save As',
                    enabled: false,
                    tooltip: {
                        text: 'Save the selected results under a new name',
                        xtype: 'quicktip'
                    },
                    handler: function(button, event, defaultText) {
                        var formattedDate = self._formatDate(new Date());
                        var extension = Math.floor((Math.random() * 1024) + 1);
                        Ext.MessageBox.prompt('Pick Search Name', 'Please select a name with which to identify this collection of Search Criteria and it\'s associated results.', function(button, text) {
                            if (button === 'ok') {
                                var hasText = CCR.exists(text) && text.length >= 3;

                                if (!hasText) {
                                    Ext.MessageBox.show({
                                        title: 'Invalid Name',
                                        msg: 'You must provide a name with which to identify these results if you wish to save them.',
                                        icon: Ext.MessageBox.ERROR,
                                        buttons: Ext.MessageBox.OK
                                    });
                                    return;
                                }

                                self._upsertSearchRecord(text);
                            }
                        }, this, false, defaultText || 'search-' + formattedDate + '-' + extension);
                    }
                },
                {
                    xtype: 'button',
                    id: 'job-viewer-search-save-results',
                    text: 'Save Results',
                    tooltip: {
                        text: 'Select jobs from the results pane above and click here to save the jobs in the search history.',
                        xtype: "quicktip"
                    },
                    handler: function(button, event, defaultText) {
                        var title = Ext.getCmp('job-viewer-search-name').getValue();
                        if(self.editing === true) {
                            title = title !== "" ? title : self.title;
                            self._upsertSearchRecord(title, self.dtypeId);
                        } else {
                            if (title) {
                                self._upsertSearchRecord(title);
                            } else {
                                var formattedDate = self._formatDate(new Date());
                                var extension = Math.floor((Math.random() * 1024) + 1);
                                var title = defaultText || 'search-' + formattedDate + '-' + extension;
                                self._upsertSearchRecord(title);
                            }
                        }
                    }
                },
                {
                    xtype: 'button',
                    id: 'job-viewer-search-cancel',
                    text: 'Cancel',
                    handler: function( /*button, event*/ ) {
                        self.fireEvent('close_search', self, false);
                    }
                }
            ]
        }];
    }, // getItems

    /**
     * A helper function that takes care of either updating or inserting the current search
     * record w/ the provided title.
     *
     * @param {String}      title
     * @param {String|null} id
     **/
    _upsertSearchRecord: function(title, id) {
        var self = this;
        var selected = self._resultsToJSON(self._getSelectedRecords());

        var params = {
            text: Ext.util.Format.htmlEncode(title),
            searchterms: {
                params: self.resultsStore.searchParams
            },
            results: selected
        };

        var realmField = Ext.getCmp('realm-field');
        var realm = realmField ? realmField.getValue() : null;
        var idFragment = id !== undefined ? '/' + id : '';
        var url = XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse + '/search/history' + idFragment  + '?realm=' + realm + '&token=' + XDMoD.REST.token;

        Ext.Ajax.request({
                url: url,
                method: 'POST',
                params: {
                    'data': JSON.stringify(params)
                },
                success: function (response) {
                    var exists = CCR.exists;

                    var data = JSON.parse(response.responseText);
                    var success = exists(data) && data.success;
                    if (success) {

                        var search = CCR.isType(data.results, CCR.Types.Array) ? data.results[0] : data.results;
                        var dtype = search['dtype'];
                        var value = search[dtype];

                        var newToken = ['realm=' + realm, dtype + '=' + value].join('&');
                        var current = Ext.History.getToken();
                        var token = CCR.tokenize(current);
                        var tab = token && token.tab ? token.tab : self.jobViewer.id;

                        Ext.History.add("#" + tab + '?' + newToken);
                    }
                    self.fireEvent('close_search', self, true);
                },
                error: function (/*response*/) {
                    Ext.MessageBox.show({
                            title: 'Save Error',
                            msg: 'There was an error saving search [' + title + '].',
                            icon: Ext.MessageBox.ERROR,
                            buttons: Ext.MessageBox.OK
                    });
                }
        });
    },

    /**
     *
     * @param {Ext.data.Record} record
     * @param {boolean|null} checked
     **/
    _handleIncludeRecord: function(record, checked) {
        var self = this;
        var dtype = record.get('dtype');
        var id = record.get(dtype);
        var exists = CCR.exists(self.selected[id]);

        if (checked && !exists) {
            self.selected[id] = record;
        }
        if ( !checked && exists) {
            delete self.selected[id];
        }

        exists = CCR.exists(self.selected[id]);
        if (checked && exists) {
            record.set('included', true);
        }
    },
    
    _dateFieldValidator: function (field_id, label) {
            var validDates = {
                startDateField: Date.parseDate(Ext.getCmp('search_start_date').getRawValue(), 'Y-m-d'),
                endDateField: Date.parseDate(Ext.getCmp('search_end_date').getRawValue(), 'Y-m-d')
            };
            if (validDates[field_id] === undefined) {
                return 'Invalid ' + label + ' (must be of the form YYYY-MM-DD)';
            }
            if (validDates.startDateField !== undefined && validDates.endDateField !== undefined) {
                if (validDates.startDateField > validDates.endDateField) {
                    return 'Start date must be less than or equal to the end date';
                }
            }
            return true;
    },

    /**
     * Load the Quick Job Lookup form with the provided searchTerms.
     *
     * @param {Object} searchTerms
     */
    _editQuickSearch: function(searchTerms) {
        var self = this;

        var params = JSON.parse(searchTerms.params);

        Ext.getCmp('basic-search-realm').setValue(searchTerms.realm);
        Ext.getCmp('basic-localjobid').setValue(params.local_job_id);

        var resourceField = Ext.getCmp('basic-resource');
        resourceField.store.load({
            params: {
                realm: searchTerms.realm
            },
            callback: function () {
                resourceField.setValue(params.resource_id);
                self.fireEvent('validate_search_criteria');
            }
        });
    },

    /**
     * Performs the steps necessary to get the SearchPanel ready for
     * editing an 'Advanced Search'.
     *
     * @param {Object} searchTerms
     */
    _editAdvancedSearch: function(searchTerms) {
        var self = this;

        var startDateField = Ext.getCmp('search_start_date');
        var endDateField = Ext.getCmp('search_end_date');

        var startDate = searchTerms.start_date;
        var endDate = searchTerms.end_date;

        startDateField.setValue(startDate);
        endDateField.setValue(endDate);

        var realmField = Ext.getCmp('realm-field');

        var realm = searchTerms.realm;
        realmField.setValue(searchTerms.realm);

        var clearSearchCriteria = function() {
            var store = CCR.exists(self.searchStore) ? self.searchStore : null;
            if (CCR.exists(store) && CCR.exists(store.removeAll)) {
                store.removeAll();
            }
        };

        var addSearchCriteria = function() {
            var value;
            var i;

            var params = JSON.parse(searchTerms.params);
            var advSearch = Ext.getCmp("job-viewer-advanced-search", {removeMask: true});
            var searchTermMask = new Ext.LoadMask(advSearch.el);
            var shown = 0;
            for (var field in params) {
                if (params.hasOwnProperty(field)) {
                    for (i = 0; i < params[field].length; i++) {

                        if (shown <= 0) {
                            searchTermMask.show();
                        }
                        shown++;

                        value = params[field][i];
                        var displayPromises = [
                            self._findFieldDisplay.call(self, field),
                            self._findFieldValueDisplay.call(self, field, value)
                        ];

                        /**
                         * Defines a partial function that will be supplied with
                         * the current values of field and value so that they
                         * will be in scope ( and hold the correct value )
                         * when called by the promise.
                         *
                         * @param {String} field the id value of the field
                         * @param {String} value the id value of the value
                         * @param {Array} displayValues the return values
                         *                              from the promises.
                         * @returns {Function} to be executed after all of
                         *                     the display promises have
                         *                     resolved.
                         **/
                        var partialThen = function(field, value, displayValues) {
                            return function(displayValues) {
                                var fieldDisplay = displayValues[0];
                                var valueDisplay = displayValues[1];
                                self.fireEvent('add_condition', realm, field, fieldDisplay, '=', valueDisplay, value);
                                if (--shown <= 0) {
                                    searchTermMask.hide();
                                }
                            };
                        };
                        RSVP
                          .all(displayPromises)
                          .then(partialThen(field, value))
                          .catch(function(reason) {
                            CCR.error('Error retrieving criteria information', reason);
                            searchTermMask.hide();
                          });
                    }
                }
            }
        };

        clearSearchCriteria();
        addSearchCriteria();
        self.resetResults();
    },

    /**
     * Retrieves the value that should be displayed to the user for the provided
     * 'field' value.
     *
     * @param {String}   field
     * @param {Function} callback
     *
     * @returns {RSVP.Promise}
     */
    _findFieldDisplay: function(field) {
        var self = this;
        var url = XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse +
            '/dimensions/' + field + '/name?token=' + XDMoD.REST.token;

        return this._getPromise(url, ['results', 'name']);
    },

    /**
     * Retrieves the string that should be displayed to the user for the provided
     * 'field' and 'value'.
     *
     * @param {String}   field
     * @param {String}   value
     *
     * @return {RSVP.Promise}
     */
    _findFieldValueDisplay: function(field, value) {
        var self = this;
        var url = XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse +
            '/dimensions/' + field +
            '/values/' + value +
            '/name?token=' + XDMoD.REST.token;

        return this._getPromise(url, ['results', 'name']);
    },

    _getPromise: function(url, resolveProperties) {
        return new RSVP.Promise(
            function(resolve, reject){
                Ext.Ajax.request({
                    url: url,
                    success: function(response) {
                        var data = JSON.parse(response.responseText);
                        var success = data.success;
                        if (success === true) {
                            var resolveValue = data;
                            for (var i = 0; i < resolveProperties.length; i++) {
                                var property = resolveProperties[i];
                                resolveValue = resolveValue[property];
                            }
                            resolve(resolveValue);
                        }
                    },
                    failure: function(response) {
                        var data = JSON.parse(response.responseText);
                        var message = data.message || 'An unknown error has occured.';
                        reject(message);
                    }
                });
            }
        );
    },

    /**
     * Attempt to retrieve the already 'selected' values from the provided
     * node. A 'selected' value is defined as an entry in this nodes childNodes
     * property that contains a 'jobid' attribute. These are then used to
     * mark the returned results such that it represents the current search
     * state.
     *
     * @param {Ext.tree.AsyncTreeNode} node
     **/
    _retrieveSelected: function(node) {
        var self = this;

        /**
         *
         * @param {Array} selected
         * @param {Array} idProperties
         * @returns {Array} suitable to set 'this.children' to.
         **/
        var processSelected = function(selected, idProperties) {
            var results = [];
            for ( var i = 0; i < selected.length; i++) {
                var item = selected[i];
                var id = item[idProperties[0]];
                for ( var j = 1; j < idProperties.length; j++) {
                    id = id[idProperties[j]];
                }
                results.push(id);
            }
            return results;
        };

        if (node.childNodes && node.childNodes.length && node.childNodes.length > 0) {
            this.children = processSelected(node.childNodes, ['attributes', 'jobid']);
        } else {
            var realm = this._getNodeValue(this._getParentNode(node, 'realm'), 'realm');
            realm = realm !== null ? realm : '';

            var url = XDMoD.REST.url + '/' + self.jobViewer.rest.warehouse +
                    '/search/history/' + this.dtypeId +
                    '?realm=' + realm +
                    '&token=' + XDMoD.REST.token;

            this._getPromise(url, ['results'])
                .then(function(results){
                    self.children = processSelected(results, ['jobid']);
                });
        }
    },

    /**
     * Attempt to retrieve an 'id' property from the provided 'item'. An 'id'
     * property is defined as one that can be found via first querying for
     * a 'dtype' property. Then using that value to retrieve the 'id'.
     *
     * @param {Object} item the item to be used in retrieving the id property
     *
     * @return {null|*} returns null if no 'dtype' property can be found or
     *                  if there is no 'get' method for the provided item.
     *                  else it attempts to provide the value of
     *                  item[item.dtype] or item.get(item.dtype)
     **/
    _getId: function(item) {
        if (item.dtype !== undefined) {
            return item[item.dtype];
        } else if (item.get !== undefined) {
            var dtype = item.get('dtype');
            return item.get(dtype);
        }
        return null;
    },

    _getTitle: function() {
        return this.title;
    },

    /**
     * If the condition evaluates to true then 'fn' is called with all of the
     * arguments following 'elseFn'. If it evaluates to false then 'elseFn'
     * is called with all of the arguments following 'elseFn';
     *
     * @param {Boolean}  condition
     * @param {Function} fn
     * @param {Function} elseFn
     *
     * @return {*} the return value of 'fn' if 'condition' is true else the
     *             return value of 'elseFn'
     **/
    _onOrCondition: function(condition, fn, elseFn) {
        var args = Array.prototype.slice.call(arguments, 3);
        if (condition) {
            return fn.apply(null, args);
        } else {
            return elseFn.apply(null, args);
        }
    },

    /**
     * Toggle the 'enabled' state of an {Ext.Component} based on the provided
     * boolean 'condition'.
     *
     * @param {Boolean}       condition a Boolean expression that will control
     *                                  whether the provided component is
     *                                  enabled or disabled.
     * @param {Ext.Component} cmp       the component to be enabled / disabled.
     **/
    _toggle: function(condition, cmp) {
        var enable = function(cmp) {
            if (cmp && cmp.enable) {
                cmp.enable();
            }
        };

        var disable = function(cmp) {
            if (cmp && cmp.disable) {
                cmp.disable();
            }
        };

        this._onOrCondition(condition, enable, disable, cmp);
    },

    /**
     * Attempts to follow the 'parentNode' links of the provided 'child'
     * node until a parent is found that contains an attribute that matches
     * the provided 'dtype'.
     *
     * @param {Ext.tree.AsyncTreeNode} child the node with which to start the
     *                                       search.
     * @param {String}                 dtype the attribute to use in qualifying
     *                                       the parentNode.
     *
     * @return {null|Ext.tree.AsyncTreeNode} returns null if no parent is found
     *                                       or it returns the node identified
     *                                       by the provided 'dtype' by
     *                                       following the parentNode links of
     *                                       the provided 'child'.
     **/
    _getParentNode: function(child, dtype) {
        var parent = child.parentNode;
        if (parent) {
            var attributes = parent.attributes || {};
            if (attributes[dtype] !== undefined) {
                return parent;
            }
            return this._getParentNode(parent, dtype);
        }
        return null;
    },

    /**
     * Attempts to retrieve the value for the provided key 'attribute' from the
     * specified 'node'.
     *
     * @param {Ext.tree.AsyncTreeNode} node
     * @param {attribute}              attribute
     *
     * @return {null|*} returns null if the attribute is not found. Else it
     *                  returns the attribute value.
     **/
    _getNodeValue: function(node, attribute) {
        if (node && node.attributes && node.attributes[attribute] !== undefined)  {
            return node.attributes[attribute];
        }
        return null;
    },

    _getDefaultStartDate: function() {
        var start = new Date();
        start.setDate(start.getDate() - 7);
        return start;
    },

    _getDefaultEndDate: function() {
        var now = new Date();
        now.setDate(now.getDate() - 1);
        return now;
    },

    _getFieldDisplayValue: function(field) {
        return field && field.store
            ? field.store.getById(field.getValue()).get(field.displayField)
            : field && field.getValue
            ? field.getValue()
            : null;
    },

    /**
     * Attempt to generate a default search name with the given parameters.
     *
     * @param {Object|Array} params      the parameters to use in generating a
     *                                   default name
     * @param {String}       [prefix=''] an optional parameter that will be
     *                                   pre-pended to the output.
     * @param {String}       [delim='-'] an optional parameter that will be
     *                                   used to delimit the param values
     *
     * @return {String}
     **/
    _generateDefaultName: function(params, prefix, delim) {
        var args = [];
        prefix = prefix !== undefined ? prefix : '';
        delim = delim !== undefined ? delim : '-';

        args.push(prefix);
        if (typeof params === 'object') {
            for ( var key in params) {
                if (params.hasOwnProperty(key)) {
                    args.push(params[key]);
                }
            }
        } else if (Array.isArray(params)) {
            args = params;
        }
        return args
            .filter(function(element, index, array) {
                return element !== undefined &&
                    element !== null &&
                    element !== '';
            })
            .join(delim);
    }

});


XDMoD.Module.JobViewer.RealmCombo = Ext.extend(Ext.form.ComboBox, {
    fieldLabel: 'Realm',
    mode: 'local',
    typeAhead: true,
    triggerAction: 'all',
    selectOnFocus: true,
    forceSelection: true,
    store: CCR.xdmod.ui.rawDataAllowedRealms,
    value: CCR.xdmod.ui.rawDataAllowedRealms[0],
    listeners: {
        select: function (field) {
            if (field.startValue !== field.getValue()) {
                this.panel.fireEvent(this.realmSelectEvent, field.getValue());
            }
        },
        blur: function (field) {
            if (field.startValue !== field.getValue()) {
                this.panel.fireEvent(this.realmSelectEvent, field.getValue());
            }
        }
    }
});

Ext.reg('realmcombo', XDMoD.Module.JobViewer.RealmCombo);
