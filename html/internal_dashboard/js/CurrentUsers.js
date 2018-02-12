/* global document, window, DateUtilities, processLDIFExport */
Ext.ns('XDMoD');

XDMoD.CurrentUsers = Ext.extend(Ext.Panel, {

    initComponent: function () {
        var self = this;

        var colorMapping = [];

        var current_state = {
            group: 'all',
            role: 'any',
            context: ''
        };

        var emptyTextContainer = Ext.id();

        // --------------------------------

        self.storeProvider = new DashboardStore({

            url: 'controllers/controller.php',
            root: 'response',
            successProperty: 'success',
            baseParams: { operation: 'enum_existing_users' },

            fields: [
                'id',
                'username',
                'first_name',
                'last_name',
                'email_address',
                'user_type',
                'role_type',
                'last_logged_in'
            ]

        });

        self.storeProvider.on('load', function (s, r) {
            var suffix = (r.length !== 1) ? 's' : '';

            // eslint-disable-next-line no-use-before-define
            tbNumUsers.setText('<b style="color: #00f">Currently displaying ' + r.length + ' user' + suffix + '</b>');

            if (r.length === 0) {
                if (current_state.context.length > 0) {
                    document.getElementById(emptyTextContainer).innerHTML = 'No matches for <b>' + current_state.context + '</b>';
                } else {
                    document.getElementById(emptyTextContainer).innerHTML = '';
                }
            }
        });

        // --------------------------------

        var adjustUserListView = function (type, value) {
            current_state[type] = value;

            self.storeProvider.reload({
                params: {
                    group_filter: current_state.group,
                    role_filter: current_state.role,
                    context_filter: current_state.context
                }
            });
        };// adjustUserListView

        // ---------------------------------

        var generateLegend = function (c) {
            var markup = '<div><table border=0><tr>';

            for (var i = 0; i < c.length; i++) {
                markup += '<td style="background-color: ' + c[i].color + '" width=30>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="padding: 0 10px 0 0">&nbsp;' + c[i].label + '</td>';
            }
            markup += '</tr></table></div>';

            return markup;
        };// generateLegend

        // ---------------------------------

        var mnuUserRoleFilter = new Ext.menu.Menu({

            plain: true,
            showSeparator: false,
            cls: 'no-icon-menu',

            items: [{ text: 'Any Role', role_id: 'any' }]

        });// mnuUserRoleFilter

        mnuUserRoleFilter.on('click', function (menu, menuItem /* , e */) {
            adjustUserListView('role', menuItem.role_id);
            // eslint-disable-next-line no-use-before-define
            btnUserRoleFilter.setText('<b class="selected_menu_item">' + menuItem.text + '</b>');
        });// mnuUserRoleFilter

        // ---------------------------------

        var btnUserRoleFilter = new Ext.Button({

            xtype: 'button',
            iconCls: 'btn_role',
            text: '<b class="selected_menu_item">Any Role</b>',

            menu: mnuUserRoleFilter

        });// btnUserRoleFilter

        // ---------------------------------

        var mnuUserTypeFilter = new Ext.menu.Menu({

            plain: true,
            showSeparator: false,
            cls: 'no-icon-menu',

            items: [{ text: 'All Users', type_id: 'all' }]

        });// mnuUserTypeFilter

        mnuUserTypeFilter.on('click', function (menu, menuItem /* , e */) {
            adjustUserListView('group', menuItem.type_id);
            // eslint-disable-next-line no-use-before-define
            btnUserTypeFilter.setText('<b class="selected_menu_item">' + menuItem.text + '</b>');
        });// mnuUserTypeFilter

        // ---------------------------------

        var btnUserTypeFilter = new Ext.Button({

            xtype: 'button',
            iconCls: 'btn_group',
            text: '<b class="selected_menu_item">All Users</b>',

            menu: mnuUserTypeFilter

        });// btnUserTypeFilter

        // ---------------------------------

        var legendRegion = new Ext.Toolbar.TextItem({

            html: '<span id="legend-region">Preparing legend...</span>',

            prepare: function (c) {
                document.getElementById('legend-region').innerHTML = generateLegend(c);
            }

        });

        // ---------------------------------

        this.on('activate', function () {
            Ext.Ajax.request({

                url: 'controllers/controller.php',
                params: { operation: 'enum_user_types_and_roles' },
                method: 'POST',
                callback: function (options, success, response) {
                    var json;
                    if (success) {
                        json = CCR.safelyDecodeJSONResponse(response);
                        // eslint-disable-next-line no-param-reassign
                        success = CCR.checkDecodedJSONResponseSuccess(json);
                    }

                    if (!success) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'Existing Users',
                            wrapperMessage: 'There was a problem retrieving user types and roles.'
                        });
                        return;
                    }
                    /* eslint-disable block-scoped-var */
                    for (var i = 0; i < json.user_types.length; i++) {
                        mnuUserTypeFilter.addItem({
                            text: json.user_types[i].type + ' Users',
                            type_id: json.user_types[i].id
                        });

                        colorMapping.push({
                            label: json.user_types[i].type + ' Users',
                            type_id: json.user_types[i].id,
                            color: json.user_types[i].color
                        });
                    }
                    // eslint-disable-next-line no-redeclare
                    for (var i = 0; i < json.user_roles.length; i++) {
                        mnuUserRoleFilter.addItem({
                            text: json.user_roles[i].description,
                            role_id: json.user_roles[i].role_id
                        });
                    }
                    /* eslint-enable block-scoped-var */

                    (function () {
                        legendRegion.prepare(colorMapping);
                        adjustUserListView('group', 'all');
                    }).defer(500);
                }// callback

            });// Ext.Ajax.request
        }, this, { single: true });

        // ---------------------------------

        function loggedInRenderer(val /* , metaData, record, rowIndex, colIndex, store */) {
            var color;
            var d;

            if (val !== '0') {
                var millis = val.split('.')[0] * 1000;

                color = '#000';

                d = new Date(millis);
                d = DateUtilities.convertDateToProperString(d);
            } else {
                color = '#888';
                d = 'Never logged in';
            }

            return '<div style="color: ' + color + '">' + Ext.util.Format.htmlEncode(d) + '</div>';
        }// loggedInRenderer

        // ---------------------------------

        var customRenderer = function (val, metaData, record, rowIndex, colIndex, store) {
            var entryData = store.getAt(rowIndex).data;

            var activeColor = '#000';

            for (var i = 0; i < colorMapping.length; i++) {
                if (colorMapping[i].type_id === entryData.user_type) {
                    activeColor = colorMapping[i].color;
                    break;
                }
            }

            var emailAddress = val.split(';')[0];

            if (emailAddress === 'no_email_address_set') {
                return '<span style="color:#f00; font-weight: bold">No email address set</span>';
            }
            return '<span style="color: ' + activeColor + '">' + Ext.util.Format.htmlEncode(emailAddress) + '</span>';
        };// customRenderer

        // ---------------------------------

        var existingUserGrid = new Ext.grid.GridPanel({

            store: self.storeProvider,
            region: 'center',

            viewConfig: {
                emptyText: 'No accounts exist under this category and role set<br><span id="' + emptyTextContainer + '"></span>',
                forceFit: true
            },

            autoScroll: true,
            enableHdMenu: false,
            loadMask: true,

            sm: new Ext.grid.RowSelectionModel({

                singleSelect: true,

                listeners: {

                    selectionchange: function (smObj) {
                        Ext.getCmp('btn_pseudo_login').setDisabled(smObj.getCount() === 0);
                    }

                }// listeners

            }),

            columns: [
                // checkBoxSelMod,
                {
                    header: 'ID',
                    width: 10,
                    dataIndex: 'id',
                    sortable: false,
                    hidden: true,
                    renderer: customRenderer
                },
                {
                    header: 'Username',
                    width: 30,
                    dataIndex: 'username',
                    sortable: true,
                    renderer: customRenderer
                },
                {
                    header: 'First Name',
                    width: 50,
                    dataIndex: 'first_name',
                    sortable: true,
                    renderer: customRenderer
                },
                {
                    header: 'Last Name',
                    width: 50,
                    dataIndex: 'last_name',
                    sortable: true,
                    renderer: customRenderer
                },
                {
                    header: 'E-Mail Address',
                    width: 50,
                    dataIndex: 'email_address',
                    sortable: true,
                    renderer: customRenderer
                },
                {
                    header: 'Last Logged In',
                    width: 50,
                    dataIndex: 'last_logged_in',
                    sortable: true,
                    renderer: loggedInRenderer
                },
                {
                    header: 'Role(s)',
                    width: 70,
                    dataIndex: 'role_type',
                    sortable: true,
                    renderer: customRenderer
                }
            ]

        });// existingUserGrid

        var reloadUserList = function () {
            self.storeProvider.reload();
        };

        self.reloadUserList = reloadUserList;

        existingUserGrid.on('rowdblclick', function (grid /* , ri, e */) {
            self.adminPanel.loadExistingUser({

                user_data: grid.getSelectionModel().getSelected().data,
                callback: reloadUserList

            });
        });// self.userGrid.on('rowdblclick', ...

        var tbNumUsers = new Ext.Toolbar.TextItem({
            html: '...'
        });

        Ext.apply(this, {

            title: 'Existing Users',
            region: 'center',
            layout: 'border',

            tbar: {

                items: [

                    {
                        xtype: 'tbtext',
                        html: 'Displaying'
                    },

                    btnUserTypeFilter,

                    {
                        xtype: 'tbtext',
                        html: 'having a role of'
                    },

                    btnUserRoleFilter,

                    '|',

                    {
                        xtype: 'button',
                        iconCls: 'btn_refresh',
                        text: 'Refresh',
                        handler: function () {
                            self.storeProvider.reload();
                            var existingUsers = Ext.getCmp('admin_tab_existing_user');
                            existingUsers.reloadUserList();
                        }
                    },

                    '|',

                    {
                        xtype: 'button',
                        disabled: true,
                        iconCls: 'btn_login_as',
                        id: 'btn_pseudo_login',
                        text: 'Log In As Selected User',
                        handler: function () {
                            var uid = existingUserGrid.getSelectionModel().getSelected().data.id;
                            window.open('controllers/pseudo_login.php?uid=' + uid);
                        }
                    },

                    '|',

                    {
                        xtype: 'button',
                        iconCls: 'btn_ldif',
                        text: 'Generate LDIF',
                        tooltip: 'LDAP Data Interchange Format',
                        handler: function () {
                            processLDIFExport({
                                group_filter: current_state.group,
                                role_filter: current_state.role,
                                context_filter: current_state.context
                            });
                        }
                    },

                    {
                        xtype: 'button',
                        iconCls: 'btn_email',
                        text: 'Prepare E-Mail',
                        handler: function () {
                            var w = new XDMoD.BatchMailClient();
                            w.show();
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
                                    self.adminPanel.showPanel({
                                        doListReload: true,
                                        callback: function () {
                                            self.reloadUserList();
                                        }
                                    });
                                },
                                scope: this
                            }
                        ]
                    },

                    '->',

                    legendRegion

                ]

            },

            bbar: {

                items: [

                    tbNumUsers

                ]

            },

            items: [

                new Ext.Panel({
                    height: 30,
                    region: 'north',
                    baseCls: 'x-plain',
                    border: false,
                    frame: false,
                    padding: '5',

                    layout: {
                        type: 'hbox',
                        pack: 'start',
                        align: 'stretch'
                    },

                    items: [

                        {
                            xtype: 'label',
                            width: 35,
                            style: {
                                font: '11px arial,tahoma,helvetica,sans-serif',
                                paddingTop: '4px'
                            },
                            text: 'Filter:'
                        },

                        new Ext.form.TextField({
                            label: 'Search',
                            emptyText: 'Search by username, first, last name',
                            width: 210,
                            enableKeyEvents: true,
                            style: {
                                backgroundColor: '#fdf0ca',
                                backgroundImage: 'none'
                            },
                            listeners: {
                                keyup: function (t) {
                                    adjustUserListView('context', t.getValue().trim());
                                }
                            }
                        })

                    ]

                }),

                existingUserGrid

            ]

        });// Ext.apply

        XDMoD.CurrentUsers.superclass.initComponent.call(this);
    }// initComponent

});// XDMoD.CurrentUsers
