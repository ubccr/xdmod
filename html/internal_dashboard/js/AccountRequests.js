Ext.ns('XDMoD');

XDMoD.AccountRequests = Ext.extend(Ext.Panel, {
    initComponent: function () {
        var self = this;
        var cachedMD5 = '';

        var adminPanel = new XDMoD.AdminPanel();

        self.storeProvider = new DashboardStore({
            url: 'controllers/controller.php',
            root: 'response',
            baseParams: {
                operation: 'enum_account_requests'
            },
            fields: [
                'id',
                'first_name',
                'last_name',
                'organization',
                'title',
                'email_address',
                'field_of_science',
                'additional_information',
                'time_submitted',
                'status',
                'comments'
            ]
        });

        self.storeProvider.on('load', function (s, r) {
            var suffix = (r.length != 1) ? 's' : '';
            tbNumRequests.setText(
                '<b style="color: #00f">' + r.length + ' account request' +
                suffix + '</b>'
            );
        });

        var presentUser = function (data, parent) {
            var w = new XDMoD.CommentEditor();
            w.setParent(parent);
            w.initWithData(data);
            w.show();
        };

        var rowRenderer = function (
            val,
            metaData,
            record,
            rowIndex,
            colIndex,
            store
        ) {
            var entryData   = store.getAt(rowIndex).data;
            var activeColor = (entryData.status == 'new') ? '#000' : '#080';
            return '<span style="color: ' + activeColor + '">' + 
                Ext.util.Format.htmlEncode(val) +
                '</span>';
        };

        // Check to see if the account request list is stale (e.g., has been changed in the
        // database) and hilight the refresh button so the user knows the view needs to be
        // refreshed.

        var staleCheck = function () {
            Ext.Ajax.request({
                method: 'POST',
                url: 'controllers/controller.php',
                params: {
                    operation: 'enum_account_requests',
                    md5only: true
                },
                callback: function (options, success, response) {
                    var json;
                    if (success) {
                        json = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(json);
                    }

                    if (!success) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: "XDMoD Dashboard",
                            wrapperMessage: "The background monitor for new account requests has failed."
                        });
                        return;
                    }

                    var updateNeeded = (cachedMD5 != json.md5);

                    var el = document.getElementById(
                        'btn_refresh_toolicon');
                    el.className = updateNeeded ?
                        'x-btn x-btn-text-icon update_highlight' :
                        'x-btn x-btn-text-icon';

                    (function () { staleCheck(); }).defer(10000);
                }
            });
        };

        var generateLegend = function (c) {
            var markup = '<div><table border=0><tr>';

            for (var i = 0; i < c.length; i++) {
                markup +=
                    '<td style="background-color: ' + c[i].color +
                    '" width=30>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' +
                    '<td style="padding: 0 10px 0 0">&nbsp;' + c[i].label +
                    '</td>';
            }

            markup += '</tr></table></div>';

            return markup;
        };

        self.storeProvider.on('load', function (store, records, options) {
            cachedMD5 = store.reader.jsonData.md5;
        });

        self.on('afterrender', function () {
            reloadAccountRequests();
            // We haven't needed this in 3 years, don't check every couple of seconds to see if the
            // view needs to be manually refreshed. It was only generating unnecessary traffic.
            // (function () { staleCheck(); }).defer(1000);
        });

        self.userGrid = new Ext.grid.GridPanel({
            store: self.storeProvider,

            viewConfig: {
                emptyText: 'No account requests are present',
                forceFit: true
            },

            autoScroll: true,
            enableHdMenu: false,
            loadMask: true,

            sm: new Ext.grid.RowSelectionModel({
                singleSelect: false,

                listeners: {
                    selectionchange: function (smObj) {
                        var numRowsSelected = smObj.getCount();

                        if (numRowsSelected > 1) {
                            Ext.getCmp('btn_toolbar_edit').setDisabled(true);
                            Ext.getCmp('btn_toolbar_delete').setDisabled(false);
                            Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);
                        } else if (numRowsSelected === 1) {
                            Ext.getCmp('btn_toolbar_edit').setDisabled(false);
                            Ext.getCmp('btn_toolbar_delete').setDisabled(false);
                            Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(false);
                        } else {
                            Ext.getCmp('btn_toolbar_edit').setDisabled(true);
                            Ext.getCmp('btn_toolbar_delete').setDisabled(true);
                            Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);
                        }
                    }
                }
            }),

            columns: [
                {
                    header: 'ID',
                    width: 10,
                    dataIndex: 'id',
                    sortable: false,
                    hidden: true,
                    renderer: rowRenderer
                },
                {
                    header: 'First Name',
                    width: 80,
                    dataIndex: 'first_name',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Last Name',
                    width: 80,
                    dataIndex: 'last_name',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Organization',
                    width: 80,
                    dataIndex: 'organization',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Title',
                    width: 80,
                    dataIndex: 'title',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'E-Mail Address',
                    width: 60,
                    dataIndex: 'email_address',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Additional Information',
                    width: 50,
                    dataIndex: 'additional_information',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Time Submitted',
                    width: 60,
                    dataIndex: 'time_submitted',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Status',
                    width: 80,
                    dataIndex: 'status',
                    sortable: true,
                    renderer: rowRenderer
                },
                {
                    header: 'Comments',
                    width: 50,
                    dataIndex: 'comments',
                    sortable: false,
                    renderer: rowRenderer
                }
            ]
        });

        self.userGrid.on('rowdblclick', function (grid, ri, e) {
            presentUser(grid.getSelectionModel().getSelected().data, self);
        });

        var tbNumRequests = new Ext.Toolbar.TextItem({
            html: '...'
        });

        var reloadAccountRequests = function () {
            document.getElementById('btn_refresh_toolicon').className =
                'x-btn x-btn-text-icon';
            self.userGrid.getSelectionModel().clearSelections(true);

            Ext.getCmp('btn_toolbar_edit').setDisabled(true);
            Ext.getCmp('btn_toolbar_delete').setDisabled(true);
            Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);

            self.storeProvider.reload();
        };

        Ext.apply(this, {
            title: 'XDMoD Account Requests',
            region: 'center',
            layout: 'fit',

            tbar: {
                items: [
                    {
                        xtype: 'button',
                        id: 'btn_refresh_toolicon',
                        iconCls: 'btn_refresh',
                        text: 'Refresh',
                        handler: function () {
                            reloadAccountRequests();
                        }
                    },
                    {
                        xtype: 'button',
                        id: 'btn_toolbar_new_user_dialog',
                        iconCls: 'btn_init_dialog',
                        text: 'Initialize New User Dialog',
                        disabled: true,
                        handler: function () {
                            adminPanel.initNewUser({
                                user_data: self.userGrid.getSelectionModel().getSelected().data,
                                callback: reloadAccountRequests
                            });
                        }
                    },
                    {
                        xtype: 'button',
                        id: 'btn_toolbar_edit',
                        iconCls: 'btn_edit',
                        text: 'Edit Comment',
                        disabled: true,
                        handler: function () {
                            presentUser(self.userGrid.getSelectionModel().getSelected().data, self);
                        }
                    },
                    {
                        xtype: 'button',
                        id: 'btn_toolbar_delete',
                        iconCls: 'btn_delete',
                        text: 'Delete Request(s)',
                        disabled: true,
                        handler: function () {
                            var selectedRecords = self.userGrid.getSelectionModel().getSelections();
                            var numSelectedRecords = selectedRecords.length;

                            var selectedRecordIds = [];
                            for (var i = 0; i < numSelectedRecords; i++) {
                                selectedRecordIds.push(selectedRecords[i].data.id);
                            }

                            var deletionSubject = (numSelectedRecords > 1) ? 'multiple requests' : 'this request';

                            Ext.Msg.show({
                                maxWidth: 800,
                                minWidth: 400,
                                title: 'Delete Selected Request(s)',
                                msg: 'Are you sure you want to delete ' + deletionSubject + '?<br><b>This action cannot be undone.</b>',
                                icon: Ext.MessageBox.QUESTION,
                                buttons: Ext.Msg.YESNO,

                                fn: function (resp) {
                                    if (resp == 'yes') {
                                        Ext.Ajax.request({
                                            method: 'POST',
                                            url: 'controllers/controller.php',
                                            params: {
                                                operation: 'delete_request',
                                                id: selectedRecordIds.join(',')
                                            },
                                            callback: function (options, success, response) {
                                                if (success) {
                                                    success = CCR.checkJSONResponseSuccess(response);
                                                }

                                                if (!success) {
                                                    CCR.xdmod.ui.presentFailureResponse(response, {
                                                        title: "XDMoD Dashboard",
                                                        wrapperMessage: "Account request deletion failed."
                                                    });
                                                    return;
                                                }

                                                self.userGrid.getSelectionModel().clearSelections(true);
                                                Ext.getCmp('btn_toolbar_edit').setDisabled(true);
                                                Ext.getCmp('btn_toolbar_delete').setDisabled(true);
                                                Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);
                                                self.storeProvider.reload();
                                            }
                                        });
                                    }
                                }
                            });
                        }
                    },
                    {
                        xtype: 'buttongroup',
                        items: [
                            {
                                text: 'Create & Manage Users',
                                scale: 'small',
                                iconCls: 'btn_group',
                                id: 'about_button',
                                handler: function () {
                                    adminPanel.showPanel({
                                        doListReload: false,
                                        callback: function () {
                                            current_users.reloadUserList();
                                        }
                                    });
                                },
                                scope: this
                            }
                        ]
                    },

                    '->',

                    new Ext.Toolbar.TextItem({
                        html: generateLegend(
                            [
                                {
                                    color: '#000',
                                    label: 'Pending'
                                },
                                {
                                    color: '#080',
                                    label: 'Already Created'
                                }
                            ]
                        )
                    })
                ]
            },

            bbar: {
                items: [
                    tbNumRequests
                ]
            },

            items: [
                self.userGrid
            ]
        });

        XDMoD.AccountRequests.superclass.initComponent.call(this);
    }
});
