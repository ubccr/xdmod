/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Jan-05
*
* A fully server-side configurable grid panel
*/

Ext.ux.DynamicGridPanel = Ext.extend(Ext.grid.GridPanel, {

    initComponent: function () {
        /**
         * Default configuration options.
         *
         * You are free to change the values or add/remove options.
         * The important point is to define a data store with JsonReader
         * without configuration and columns with empty array. We are going
         * to setup our reader with the metaData information returned by the server.
         * See http://extjs.com/deploy/dev/docs/?class=Ext.data.JsonReader for more
         * information how to configure your JsonReader with metaData.
         *
         * A data store with remoteSort = true displays strange behaviours such as
         * not to display arrows when you sort the data and inconsistent ASC, DESC option.
         * Any suggestions are welcome
         */
        var config = {
            viewConfig: {
                forceFit: false
            },
            enableColLock: true,
            enableHdMenu: this.showHdMenu || false,
            // loadMask: true, 
            border: false,
            stripeRows: true,
            autoScroll: true,
            ds: new CCR.xdmod.CustomJsonStore({
                url: this.storeUrl,
                remoteSort: this.remoteSort || false,
                baseParams: this.baseParams || {}
            }),

            colModel: this.lockingView ? new Ext.ux.grid.LockingColumnModel({}) : new Ext.grid.ColumnModel({}),
            view: this.lockingView ? new Ext.ux.grid.LockingGridView({}) : new Ext.ux.grid.BufferView({

                // render rows as they come into viewable area.
                scrollDelay: false,
                cacheSize: 36
            }),

            selModel: new Ext.grid.CellSelectionModel({})


        };
        if (this.searchField) {
            config.tbar = ['Search ', new CCR.xdmod.ui.CustomTwinTriggerField({
                store: config.ds
            })];
        }
        if (this.usePaging) {
            if (!this.defaultPageSize) this.defaultPageSize = 100;
            this.pageSizeField = new Ext.form.NumberField({
                id: 'page_size_field_' + this.id,
                fieldLabel: 'Grid Size',
                name: 'page_size',
                minValue: 1,
                maxValue: 200,
                allowDecimals: false,
                decimalPrecision: 0,
                incrementValue: 1,
                alternateIncrementValue: 2,
                accelerate: true,
                width: 30,
                //emptyText: this.defaultPageSize,
                value: this.defaultPageSize,
                listeners: {
                    'change': function (t, newValue, oldValue) {
                        if (t.isValid(false) && newValue != t.ownerCt.pageSize) {
                            t.ownerCt.pageSize = newValue;
                            t.ownerCt.doRefresh();
                        }
                    },
                    'specialkey': function (t, e) {
                        // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                        // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                        if (t.isValid(false) && e.getKey() == e.ENTER && t.getValue() != t.ownerCt.pageSize) {
                            //	this.parent.onHandle();
                            t.ownerCt.cursor = 0;
                            t.ownerCt.pageSize = t.getValue();
                            t.ownerCt.doRefresh();

                        }
                    }
                }

            });


            // paging bar on the bottom
            config.bbar =
                new Ext.PagingToolbar({
                    pageSize: this.defaultPageSize,
                    store: config.ds,
                    displayInfo: true,
                    beforePageText: 'Datasheet Page',
                    displayMsg: 'Datasheet Rows {0} - {1} of {2}',
                    emptyMsg: "No data",
                    items: [
                        '-',
                        'Sheet Size',
                        this.pageSizeField,
                        'Rows'
                    ]
                    //,
                    //	plugins: new Ext.ux.ProgressBarPager()

                });
        }
        Ext.apply(this, config);
        Ext.apply(this.initialConfig, config);
        Ext.ux.DynamicGridPanel.superclass.initComponent.apply(this, arguments);

        this.addEvents("load");
    },
    onRender: function (ct, position) {
        this.colModel.defaultSortable = true;
        Ext.ux.DynamicGridPanel.superclass.onRender.call(this, ct, position);
        /**
         * Grid is not masked for the first data load.
         * We are masking it while store is loading data
         */
        //this.el.mask('Loading...');
        this.store.on('beforeload', function () {
            if (this.pageSizeField) this.store.baseParams.limit = this.pageSizeField.getValue();
        }, this);
        this.store.on('load', function () {

            /**
             * Thats the magic! <img src="http://erhanabay.com/wp-includes/images/smilies/icon_smile.gif" alt=":)" class="wp-smiley">
             *
             * JSON data returned from server has the column definitions
             */
            if (typeof (this.store.reader.jsonData.columns) === 'object') {
                var columns = [];
                /**
                 * Adding RowNumberer or setting selection model as CheckboxSelectionModel
                 * We need to add them before other columns to display first
                 */
                if (this.rowNumberer) {
                    var rowNumbererWidth = 25;
                    if (this.store.reader.jsonData.total > 99 && this.store.reader.jsonData.total < 1000) {
                        rowNumbererWidth = 30;
                    } else
                    if (this.store.reader.jsonData.total > 999) {
                        rowNumbererWidth = 38;
                    } else
                    if (this.store.reader.jsonData.total > 9999) {
                        rowNumbererWidth = 46;
                    }
                    columns.push(new CCR.xdmod.ui.CustomRowNumberer({
                        width: rowNumbererWidth,
                        offset: this.bottomToolbar ? this.bottomToolbar.cursor : 0,
                        locked: false
                    }));
                }
                if (this.checkboxSelModel) {
                    columns.push(new Ext.grid.CheckboxSelectionModel());
                }
                Ext.each(this.store.reader.jsonData.columns, function (column) {
                    columns.push(column);
                });
                /**
                 * Setting column model configuration
                 */
                this.getColumnModel().setConfig(columns);
            }
            /**
             * Unmasking grid
             */
            this.el.unmask();
            this.fireEvent("load", this);
        }, this);
        /**
         * And finally load the data from server!
         */

        //this.store.load();
    }
});
