Ext.ns('XDMoD.Admin');

XDMoD.Admin.AclGrid = Ext.extend(Ext.grid.EditorGridPanel, {
    enableHdMenu: false,

    selectionChangeHandler: null,
    aclCenters: {},

    listeners: {
        beforerender: function (grid) {
            Dashboard.ControllerProxy(grid.getStore(), { operation: 'enum_roles' });
        }
    },

    initComponent: function () {
        var self = this;
        this.isDirty = false;

        this.isInDirtyState = function () {
            return self.isDirty;
        };

        this.setDirtyState = function (dirty) {
            self.isDirty = dirty;
            if (self.selectionChangeHandler) {
                self.selectionChangeHandler();
            }
        };

        this.updateDirtyState = function () {
            var dirty = false;

            var records = self.store.data.items;
            for (var i = 0; i < records.length; i++) {
                var record = records[i];
                dirty = record.modified !== null && record.get('include') !== record.modified['include'];
                if (dirty) {
                    break;
                }
            }

            self.setDirtyState(dirty);
        };

        this.ccInclude = new Ext.grid.CheckColumn({
            header: 'Include',
            dataIndex: 'include',
            width: 55,

            /*
             * Called when Ext renders this control to the screen.
             */
            renderer: function (val, metaData, record, rowIndex, colIndex, store) {
                var data = store.getAt(rowIndex).data;

                if (data.requires_center === true) {
                    return '<div style="text-align:center;">' +
                      '<a title="Specify Centers" href="javascript:void(0)">' +
                      '<img src="images/center_edit.png">' +
                      '</a>' +
                      '</div>'
                    ;
                }
                var checked = val ? '-on' : '';
                return '<div class="x-grid3-check-col' + checked + ' x-grid3-cc-' + this.id + '"></div>';
            }, // renderer

            /**
             * Function that is called when this control receives a mouse down event.
             *
             * @param {object} event
             * @param {object} node
             */
            onMouseDown: function (event, node) {
                // If the node clicked did not require centers then go ahead and process it.
                // NOTE: this is from the 'renderer' function above.

                event.stopEvent();

                var record = this.grid.store.getAt(
                    this.grid.getView().findRowIndex(node)
                );

                if (record.data.requires_center) {
                    // let the center selector handle things.
                    XDMoD.Admin.AclGrid.prepCenterMenu(self.id, record.data.acl, record.data.acl_id, self.id);
                } else {
                    // provide the default behavior of clicking on a checkbox
                    // flipping the state.
                    record.set(this.dataIndex, !record.data[this.dataIndex]);
                    self.updateDirtyState();
                }
            }, // onMouseDown

            /**
             * Reset all of the grid store's record's include properties to false.
             */
            reset: function () {
                this.grid.store.each(function (record) {
                    record.set(this.dataIndex, false);
                }, this);
            },

            getSelected: function () {
                var results = [];
                this.grid.store.each(function (record) {
                    if (record.data[this.dataIndex] === true) {
                        results.push(record.data.acl_id);
                    }
                }, this);
                return results;
            }
        }); // ccInclude

        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                hideable: false
            },
            columns: [
                {
                    header: 'Name',
                    dataIndex: 'acl',
                    width: 109,
                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                        var data = store.getAt(rowIndex).data;
                        if (data.requires_center === true) {
                            if (!record.ref) {
                                record.ref = Ext.id();
                            }
                            var centers = store.parent.getCenters(data.acl_id);
                            return value + '<span id="' + record.ref + '"><b> (' + centers.length + ')</b></span>';
                        }
                        return value;
                    }
                },
                this.ccInclude
            ]
        }); // columnModel

        var store = new DashboardStore({
            parent: self,
            autoLoad: false,
            autoDestroy: true,
            url: '../controllers/user_admin.php',
            root: 'acls',
            fields: ['acl', 'acl_id', 'include', 'requires_center'],
            listeners: {
                load: function (dashboardStore, records) {
                    for (var i = 0; i < records.length; i++) {
                        var record = records[i];
                        if (record.data.requires_center === true &&
                          !self.aclCenters.hasOwnProperty(record.data.acl_id)) {
                            self.aclCenters[record.data.acl_id] = [];
                        }
                    }
                }
            }
        }); // store

        Ext.apply(this, {
            store: store,
            cm: columnModel,
            enableColumnResize: false,
            plugins: [this.ccInclude],
            viewConfig: {
                forceFit: true
            }
        });

        //  Make sure to call the superclass initComponent.
        XDMoD.Admin.AclGrid.superclass.initComponent.call(this);
    },

    setSelectedAcls: function (acls) {
        this.store.each(function (record) {
            var hasAcl = acls.indexOf(record.data.acl_id) !== -1;
            record.set('include', hasAcl);
            /* eslint-disable no-param-reassign */
            if (!record.hasOwnProperty('modified')) {
                record.modified = {};
            }
            record.modified['include'] = hasAcl;
            /* eslint-enable no-param-reassign */
        }, this);
    },

    selectAcl: function (acl) {
        this.store.each(function (record) {
            if (record.data.acl_id === acl) {
                record.set('include', true);
            }
        }, this);
    },

    deselectAcl: function (acl) {
        this.store.each(function (record) {
            if (record.data.acl_id === acl) {
                record.set('include', false);
            }
        }, this);
    },

    getSelectedAcls: function () {
        return this.ccInclude.getSelected();
    },

    reset: function () {
        this.ccInclude.reset();
        for (var key in this.aclCenters) {
            if (this.aclCenters.hasOwnProperty(key)) {
                this.aclCenters[key] = [];
            }
        }
    },
    getCenters: function (acl) {
        if (this.aclCenters.hasOwnProperty(acl)) {
            return this.aclCenters[acl];
        }
        return [];
    },
    setCenterConfig: function (acl, centers) {
        if (this.aclCenters.hasOwnProperty(acl) && centers && centers.length > 0) {
            this.aclCenters[acl] = centers;
        }
    },
    updateCenterCounts: function () {
        this.store.each(function (record) {
            if (record.ref) {
                var centers = this.getCenters(record.data.acl_id);
                var elem = document.getElementById(record.ref);
                elem.innerHTML = '<b> (' + centers.length + ')</b>';
            }
        }, this);
    }
}); // XDMoD.Admin.AclGrid

XDMoD.Admin.AclGrid.CenterSelector = Ext.extend(Ext.menu.Menu, {

    initComponent: function () {
        var self = this;
        this.store = new Ext.data.JsonStore({
            url: '../controllers/user_admin.php',
            fields: ['id', 'organization'],
            root: 'providers',
            idProperty: 'id',
            baseParams: {
                operation: 'enum_resource_providers'
            },
            autoLoad: true
        });
        this.ccInclude = new Ext.grid.CheckColumn({
            header: 'Include',
            dataIndex: 'include',
            width: 50,
            scope: this,
            onMouseDown: function (event, node) {
                if (Ext.fly(node).hasClass(this.createId())) {
                    event.stopEvent();

                    var record = this.grid.store.getAt(
                        this.grid.getView().findRowIndex(node)
                    );

                    var value = !record.data[this.dataIndex];
                    record.set(this.dataIndex, value);

                    var parent = Ext.getCmp(self.parentId);
                    var hasAcl = parent.aclCenters.hasOwnProperty(self.acl_id);
                    var aclHasCenter = parent.aclCenters[self.acl_id].indexOf(record.data.id) >= 0;
                    var isDirty = record.get('include') !== record.modified['include'];

                    if (hasAcl && aclHasCenter && !value) {
                        // If the acl already has a record for this center and
                        // it's being removed then remove it.
                        parent.aclCenters[self.acl_id].splice(parent.aclCenters[self.acl_id].indexOf(record.data.id), 1);
                        if (parent.aclCenters[self.acl_id].length === 0) {
                            parent.deselectAcl(self.acl_id);
                        }

                        parent.setDirtyState(isDirty);
                    } else if (hasAcl && !aclHasCenter && value) {
                        // If the acl does not have a record for this center
                        // then add it.
                        parent.aclCenters[self.acl_id].push(record.data.id);
                        parent.selectAcl(self.acl_id);
                        parent.setDirtyState(isDirty);
                    }
                    parent.updateCenterCounts();
                }
            }

        });

        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                hideable: false,
                resizeable: false
            },
            columns: [
                {
                    header: 'Center',
                    dataIndex: 'organization',
                    width: 280
                },
                this.ccInclude
            ]
        });

        var grid = new Ext.grid.GridPanel({
            title: 'Select the centers associated with the <span style="color: #00F;">' + this.acl + '</span> acl.',
            store: this.store,
            autoScroll: true,
            rowNumberer: true,
            border: true,
            stripeRows: true,
            enableHdMenu: false,
            plugins: [this.ccInclude],
            cm: columnModel,
            region: 'center',
            height: 335,
            layout: 'fit',
            viewConfig: {
                forceFit: true,
                scrollOffset: 2
            },
            bbar: {
                items: [
                    '->',
                    {
                        text: 'Close',
                        iconCls: 'general_btn_close',
                        handler: function () {
                            self.hide();
                        }
                    }
                ]
            }
        });

        grid.store.on('load', function (store, records) {
            for (var i = 0; i < records.length; i++) {
                var record = records[i];
                var found = self.selected.indexOf(record.data.id) >= 0;
                record.set('include', found);
            }
        });

        Ext.apply(this, {
            width: 420,
            height: 345,
            border: false,
            header: false,
            showSeparator: false,
            items: [
                grid
            ]
        });

        XDMoD.Admin.AclGrid.CenterSelector.superclass.initComponent.call(this);
    }

}); // XDMoD.Admin.AclGrid.CenterSelector

XDMoD.Admin.AclGrid.prepCenterMenu = function (objSrcEl, acl, aclId, parentId) {
    var parent = Ext.getCmp(parentId);

    var menu = new XDMoD.Admin.AclGrid.CenterSelector({
        acl: acl,
        acl_id: aclId,
        parentId: parentId,
        selected: parent.getCenters(aclId)
    });

    menu.show(objSrcEl, 'tl-bl?');
}; // XDMoD.Admin.AclGrid.prepCenterMenu
