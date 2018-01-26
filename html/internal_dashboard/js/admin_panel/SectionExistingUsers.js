Ext.namespace('XDMoD');

var displayExceptionEmails = function () {
    Ext.Ajax.request({
        url: '../controllers/user_admin.php',
        params:  { operation: 'enum_exception_email_addresses' },
        method: 'POST',
        callback: function (options, success, response) {
            var json;
            if (success) {
                json = CCR.safelyDecodeJSONResponse(response);
                success = CCR.checkDecodedJSONResponseSuccess(json);
            }

            if (!success) {
                CCR.xdmod.ui.presentFailureResponse(response, {
                    title: "User Management",
                    wrapperMessage: "Could not retrieve exception email addresses."
                });
                return;
            }

            var addresses = [];
            for (var i = 0; i < json.email_addresses.length; i++) {
                addresses.push(json.email_addresses[i]);
            }
            var message = 'The following addresses can be mapped to multiple XDMoD accounts:<br><br>' + addresses.join("<br />");
            CCR.xdmod.ui.generalMessage(
                'Exception E-Mail Addresses',
                message,
                true,
                5000
            );
      }//callback
   });//Ext.Ajax.request
};//displayExceptionEmails

// =====================================================================================

XDMoD.ExistingUsers = Ext.extend(Ext.Panel, {
    initFlag: 0,

    // Assigned to Ext.data.JsonStore in initComponent(...)
    userStore: null,

    // If the userStore is reloaded, userStoreLoadReset determines
    // whether the UserDetails section is to be reset
    userStoreLoadReset: true,

    // Assigned to Ext.SplitButton in initComponent(...)
    groupToggle: null,

    cachedUserTypeID: 0,
    cachedAutoSelectUserID: undefined,

    reloadUserList: function (user_type, select_user_with_id) {

        this.cachedAutoSelectUserID = select_user_with_id;

        if (this.userTypes.length === 0) {

            // "Current Users" tab has not yet been visited.  Simply
            // cache the user type ID so that when the user types store
            // does load, the intended category can be fetched.
            this.cachedUserTypeID = user_type;
        }

        for (var i = 0; i < this.userTypes.length; i++) {

            if (this.userTypes[i].id == user_type) {
                this.cachedUserTypeID = user_type;

                this.groupToggle.setText(this.userTypes[i].text);

                Dashboard.ControllerProxy(this.userStore, {
                    operation: 'list_users',
                    group: this.userTypes[i].id
                });

                return;
            }
        }
    },

    initComponent: function () {

        function formatDate(value) {
            return value ? value.dateFormat('M d, Y') : '';
        }

        this.userTypes = [];

        var self = this;
        var selected_user_id = -1;
        var selected_username = '';

        var mapping_cached_person_name = '';
        var mapping_cached_person_id = '';
        var cached_user_type = null;

        var user_update_callback;

        var settingsAreDirty = false;

        // variable used to set a state/phase regarding the means in
        // which the combo box data store gets reloaded
        var tg_user_list_phase = '';

        var tg_user_list_page_size = 300;

        // shorthand alias
        var fm = Ext.form;

        // ------------------------------------------

        self.setCallback = function (callback) {
            user_update_callback = callback;
        };

        // ------------------------------------------

        function userRenderer(
            val,
            metaData,
            record,
            rowIndex,
            colIndex,
            store
        ) {
            var entryData = store.getAt(rowIndex).data;
            var color;

            if (entryData.account_is_active == '1') { color = '000'; }
            if (entryData.account_is_active == '0') { color = 'f00'; }

            return '<div style="color: #' + color + '">' + Ext.util.Format.htmlEncode(val) + '</div>';
        }

        // ------------------------------------------

        function loggedInRenderer(
            val,
            metaData,
            record,
            rowIndex,
            colIndex,
            store
        ) {
            if (val !== 0) {
                color = '#00f';
                d = new Date(val);
                d = DateUtilities.convertDateToProperString(d);
            }
            else {
                color = '#888';
                d = 'Never logged in';
            }

            return '<div style="color: ' + color + '">' + Ext.util.Format.htmlEncode(d) + '</div>';
        }

        // ------------------------------------------

        var cm = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hideable: false,
                resizable: true
            },
            columns: [
                {
                    id: 'common',
                    header: 'Username',
                    dataIndex: 'username',
                    width: 90,
                    renderer: userRenderer
                },
                {
                    header: 'First Name',
                    dataIndex: 'first_name',
                    width: 120,
                    renderer: userRenderer
                },
                {
                    header: 'Last Name',
                    dataIndex: 'last_name',
                    width: 120,
                    renderer: userRenderer
                },
                {
                    header: 'Last Logged In',
                    dataIndex: 'last_logged_in',
                    width: 150,
                    renderer: loggedInRenderer
                }
            ]
        });

        // ------------------------------------------------------------------

        var comboChangeHandler = function (f, newValue, oldValue) {
            settingsAreDirty = true;
            saveIndicator.show();
        };

        // ------------------------------------------------------------------

        var storeUserType = new DashboardStore({
            url: '../controllers/user_admin.php',
            root: 'user_types',
            autoLoad: true,
            baseParams: { operation: 'enum_user_types' },
            fields: ['id', 'type']
        });

        var cmbUserType = new Ext.form.ComboBox({
            editable: false,
            width: 165,
            listWidth: 165,
            fieldLabel: 'User Type',
            store: storeUserType,
            displayField: 'type',
            triggerAction: 'all',
            valueField: 'id',
            emptyText: 'No User Type Selected',
            listeners: { change: comboChangeHandler }
        });

        cmbUserType.on('disable', function() { cmbUserType.reset(); });

        // ------------------------------------------------------------------

        var mnuUserTypeFilter = new Ext.menu.Menu({
            plain: true,
            showSeparator: false,
            cls: 'no-icon-menu',
            items: []
        });

        mnuUserTypeFilter.on('click', function (menu, menuItem, e) {
            if (self.inDirtyState()) {
                Ext.Msg.show({
                    maxWidth: 800,
                    minWidth: 400,
                    title: 'Unsaved Changes',
                    msg: "There are unsaved changes to this account.<br />" +
                         "Do you wish to save these changes before continuing?<br /><br />" +
                         "If you choose <b>No</b>, you will lose all your changes.",

                    icon: Ext.MessageBox.QUESTION,

                    buttons: {
                        yes: "Yes (go back and save)",
                        no: "No (discard changes)"
                    },

                    fn: function (resp) {
                        if (resp == 'yes') { return; }

                        if (resp == 'no') {
                            self.resetDirtyState();
                            saveIndicator.hide();
                            self.reloadUserList(menuItem.type_id);
                        }
                    }
                });

                return;

            }

            self.reloadUserList(menuItem.type_id);

        });

        // ------------------------------------------------------------------

        this.on('activate', function () {
            Ext.Ajax.request({
                    url: '../controllers/user_admin.php',
                    params: { operation: 'enum_user_types' },
                    method: 'POST',

                    callback: function (options, success, response) {
                        var json;
                        if (success) {
                            json = CCR.safelyDecodeJSONResponse(response);
                            success = CCR.checkDecodedJSONResponseSuccess(json);
                        }

                        if (!success) {
                            CCR.xdmod.ui.presentFailureResponse(response, {
                                title: 'User Management',
                                wrapperMessage: 'Could not load user types.'
                            });
                            return;
                        }

                        for (var i = 0; i < json.user_types.length; i++) {
                            self.userTypes.push({
                                text: json.user_types[i].type + ' Users',
                                id: json.user_types[i].id
                            });

                            mnuUserTypeFilter.addItem({
                                text: json.user_types[i].type + ' Users',
                                type_id: json.user_types[i].id
                            });
                        }

                        var user_type_to_load =
                            (self.cachedUserTypeID > 0) ?
                            self.cachedUserTypeID :
                            json.user_types[0].id;

                        self.reloadUserList(
                            user_type_to_load,
                            self.cachedAutoSelectUserID
                        );
                    }//callback
                });//Ext.Ajax.request
            }, this, { single: true });

            // ------------------------------------------

            var cmbInstitution = new CCR.xdmod.ui.InstitutionDropDown({
                fieldLabel: 'Institution',
                emptyText: 'No Institution Selected',
                width: 165,
                listWidth: 310,
                listeners: { change: comboChangeHandler }
            });

            cmbInstitution.on('disable', function() {
                cmbInstitution.reset();
            });

            // ------------------------------------------

        var storeUserListing = new DashboardStore(
            {
                autoload: true,
                url: '../controllers/user_admin.php',
                baseParams: {
                    operation: 'list_users',
                    group: ''
                },
                storeId: 'storeUserListing',
                root: 'users',
                fields: [
                    'id',
                    'username',
                    'email_address',
                    'first_name',
                    'last_name',
                    'account_is_active',
                    'last_logged_in'
                ]
            }
        );

            this.userStore = storeUserListing;

            storeUserListing.on('load', function (store, records, options) {
                if (!self.userStoreLoadReset) {
                    self.userStoreLoadReset = true;
                    return;
                }

                userEditor.setTitle('User Details');

                userEditor.showMask();
                userSettings.setDisabled(true);
                cmbUserType.setDisabled(true);
                btnSaveChanges.setDisabled(true);
                existingUserEmailField.setValue('');

                if (self.initFlag == 1) {
                    document.getElementById('txtAccountTimestamps').innerText = '';
                    document.getElementById('txtAccountStatus').innerText = '';
                }

                self.initFlag = 1;

                //cmbUserMapping.setValue('');

                cmbInstitution.setDisabled(true);

                roleGrid.reset();

                lblXSEDEUser.hide();
                cmbUserType.show();

                if (self.cachedAutoSelectUserID !== undefined) {

                    var targetIndex = store.findExact(
                        'id',
                        self.cachedAutoSelectUserID
                    );

                    grid.getSelectionModel().selectRow(targetIndex);

                    grid.fireEvent('cellclick', grid, targetIndex);

                    self.cachedAutoSelectUserID = undefined;
                }
            });//storeUserListing.on('load')

            // ------------------------------------------

            var userManagementAction = function (objParams) {

            Ext.Ajax.request({
                url: '../controllers/user_admin.php',
                params: objParams,
                method: 'POST',

                callback: function (options, success, response) {
                    var json;
                    if (success) {
                        json = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(json);
                    }

                    if (!success) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'User Management',
                            wrapperMessage: 'User operation failed.'
                        });
                        return;
                    }

                    if (
                        objParams.operation == 'delete_user' ||
                        objParams.operation == 'update_user'
                    ) {
                        if (objParams.operation == 'delete_user') {
                            selected_user_id = -1;
                            selected_username = '';
                        }

                        if (objParams.operation == 'update_user') {
                            self.userStoreLoadReset = false;
                            fetchUserDetails(objParams.uid, false);
                        }

                        // Refresh the user list based on the
                        // currently selected user list view
                        self.reloadUserList(self.cachedUserTypeID);
                    }

                    if (
                        objParams.operation !==
                        'empty_report_image_cache'
                    ) {
                        if (user_update_callback) {
                            user_update_callback();
                        }
                    }

                    var displayedMessage = json.message ? json.message : json.status;
                    CCR.xdmod.ui.userManagementMessage(
                        displayedMessage,
                        json.success
                    );
                }
            });//Ext.Ajax.request
        };//userManagementAction

        // ------------------------------------------

        var usersTypeSplitButton = new Ext.Button({
            scope: this,
            width: 50,
            text: '',
            cls: 'no-icon-menu',
            menu: mnuUserTypeFilter
        });

        this.groupToggle = usersTypeSplitButton;

        // ------------------------------------------

        var actionEmptyReportImageCache = function () {
            Ext.Msg.show({
                maxWidth: 800,
                title: 'Empty Report Image Cache',
                msg: "Are you sure you want to empty the report image cache for user <b>" + selected_username + "</b> ?",
                buttons: Ext.Msg.YESNO,
                fn: function(resp) {
                    if (resp == 'yes'){
                        userManagementAction({
                            operation: 'empty_report_image_cache',
                            uid: selected_user_id
                        });
                    }
                },

                icon: Ext.MessageBox.QUESTION
            });
        };//actionEmptyReportImageCache

        // ------------------------------------------

        var actionDeleteAccount = function () {
            if (selected_user_id == -1) {
                CCR.xdmod.ui.userManagementMessage(
                    'You must first select a user to delete.',
                    false
                );
                return;
            }

            Ext.Msg.show({
                maxWidth: 800,
                title: 'Delete User',
                msg: "Are you sure you want to delete user <b>" + selected_username + "</b> from the portal ?",
                icon: Ext.MessageBox.QUESTION,
                buttons: Ext.Msg.YESNO,
                fn: function (resp) {
                    if (resp == 'yes'){
                        userManagementAction({
                            operation: 'delete_user',
                            uid: selected_user_id
                        });
                    }
                }
            });
        };

        // ------------------------------------------

        var actionToggleAccountStatus = function (action) {
            if (selected_user_id == -1) {
                CCR.xdmod.ui.userManagementMessage(
                    'You must first select a user to ' +
                        action.toLowerCase() + '.',
                    false
                );
                return;
            }

            Ext.Msg.show({
                maxWidth: 800,
                title: action + ' User',
                msg: "Are you sure you want to " + action.toLowerCase() + " access for user <b>" + selected_username + "</b> ?",
                icon: Ext.MessageBox.QUESTION,
                buttons: Ext.Msg.YESNO,
                fn: function(resp) {
                    if (resp == 'yes'){
                        /* eslint-disable no-use-before-define */
                        userManagementAction({
                            operation: 'update_user',
                            uid: selected_user_id,
                            email_address: existingUserEmailField.getValue(),
                            is_active: (action == 'Enable') ? 'y' : 'n'
                        });
                        /* eslint-enable no-use-before-define */
                    }
                }
            });
        };

        // ------------------------------------------

        var actionPasswordReset = function () {
            if (selected_user_id == -1) {
                CCR.xdmod.ui.userManagementMessage(
                    'You must first select a user you wish to send a password reset e-mail to.',
                    false
                );
                return;
            }

            Ext.Msg.show({
                maxWidth: 800,
                title: 'Password Reset',
                msg: "Are you sure you want to send a password reset e-mail to <b>" + selected_username + "</b> ?",
                icon: Ext.MessageBox.QUESTION,
                buttons: Ext.Msg.YESNO,

                fn: function (resp) {
                    if (resp == 'yes'){
                        userManagementAction({
                            operation: 'pass_reset',
                            uid: selected_user_id
                        });
                    }
                }
            });
        };

        // ------------------------------------------

        var fieldRequiredText = 'This field is required.';
        var minEmailLength = XDMoD.constants.minEmailLength;
        var maxEmailLength = XDMoD.constants.maxEmailLength;
        var existingUserEmailField = new Ext.form.TextField({
            fieldLabel: 'E-Mail Address',
            emptyText: minEmailLength + ' min, ' + maxEmailLength + ' max',
            msgTarget: 'under',
            width: 165,
            flex: 1,

            minLength: minEmailLength,
            minLengthText: 'Minimum length (' + minEmailLength + ' characters) not met.',
            maxLength: maxEmailLength,
            maxLengthText: 'Maximum length (' + maxEmailLength + ' characters) exceeded.',

            validator: function (value) {
                // If the user is an XSEDE user, an email address is not required.
                if (cached_user_type === CCR.xdmod.FEDERATED_USER_TYPE) {
                    return true;
                }

                // Return validity of value in the field.
                // (Other validators will check the remaining criteria.)
                return XDMoD.validator.email(value);
            },

            listeners: {
                change: function (f, newValue, oldValue) {
                    XDMoD.utils.trimOnBlur(f);

                    var trimmedValue = f.getValue();

                    if (trimmedValue === oldValue) {
                        return;
                    }

                    settingsAreDirty = true;
                    saveIndicator.show();
                }
            }
        });

        // ------------------------------------------

        var cmbUserMapping = new CCR.xdmod.ui.TGUserDropDown({
            controllerBase: '../controllers/sab_user.php',
            dashboardMode: true,
            user_management_mode: true,
            fieldLabel: 'Map To',
            emptyText: 'User not mapped',
            hiddenName: 'nm_existing_user_mapping',
            width: 165,
            listeners: { change: comboChangeHandler }
        });

        cmbUserMapping.on('disable', function() {
            cmbUserMapping.reset();
        });

        // ------------------------------------------
        /* eslint-disable no-use-before-define */
        var roleGridClickHandler = function () {
            var selRoles = roleGrid.getSelectedAcls();
            cmbInstitution.setDisabled(selRoles.itemExists('cc') === -1);
            if (roleGrid.isInDirtyState()) {
                saveIndicator.show();
            } else {
                saveIndicator.hide();
            }
        };
        var roleGrid = new XDMoD.Admin.AclGrid({
            cls: 'admin_panel_section_role_assignment',
            selectionChangeHandler: roleGridClickHandler,
            border: false
        });
        /* eslint-enable no-use-before-define */

        // ------------------------------------------

        self.inDirtyState = function () {
            return (roleGrid.isInDirtyState() || settingsAreDirty);
        };

        self.resetDirtyState = function () {
            roleGrid.setDirtyState(false);
        };

        // ------------------------------------------

        var roleSettings = new Ext.Panel({
            title: 'Acl Assignment',
            columns: 1,
            layout: 'fit',
            flex: 0.55,
            items: [
                roleGrid
            ]
        });

        // ------------------------------------------

        var lblXSEDEUser = new Ext.form.Label({
            fieldLabel: 'User Type',
            html: '<b style="color: #00f">Federated User</b>'
        });

        lblXSEDEUser.hide();

        var userSettings = new Ext.FormPanel({
            flex: 0.45,
            labelWidth: 95,
            frame: true,
            title: 'Settings',
            bodyStyle: 'padding:5px 5px 0',
            defaults: { width: 170 },
            cls: 'admin_panel_existing_user_settings',
            labelAlign: 'top',
            defaultType: 'textfield',
            region: 'west',
            disabled: true,
            maskDisabled: false,

            items: [
                existingUserEmailField,
                cmbUserType,
                lblXSEDEUser,
                cmbUserMapping,
                cmbInstitution
            ]
        });

        // ------------------------------------------

        var btnSaveChanges = new Ext.Button({
            text: 'Save Changes',
            cls: 'admin_panel_btn_save',
            iconCls: 'admin_panel_btn_save_icon',

            handler: function () {
                cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
                cmbInstitution.removeClass('admin_panel_invalid_text_entry');

                // ===========================================

                if (!userSettings.getForm().isValid()) {
                    CCR.xdmod.ui.userManagementMessage('Please fix any fields marked as invalid.', false);
                    return;
                }

				// ===========================================

                if (roleGrid.getSelectedAcls().length === 0) {
                    CCR.xdmod.ui.userManagementMessage(
                        'This user must have at least one role.',
                        false
                    );
                    return;
                }

                var acls = roleGrid.getSelectedAcls();
                // ===========================================
											              
                if (
                    (acls.indexOf('pi') >= 0 || acls.indexOf('usr') >= 0) &&
                    (cmbUserMapping.getValue().length === 0)
                ) {
                    cmbUserMapping.addClass('admin_panel_invalid_text_entry');
                    CCR.xdmod.ui.userManagementMessage(
                        'Cannot find <b>' + cmbUserMapping.getValue() +
                        '</b> in the directory.<br>Please select a name from the drop-down list.',
                        false
                    );
                    return;
                }
				
				
                if (
                    (acls.indexOf('cc') >= 0) &&
                    (cmbInstitution.getValue().length === 0)
                ) {
                    cmbInstitution.addClass('admin_panel_invalid_text_entry');
                    CCR.xdmod.ui.userManagementMessage(
                        'An institution must be specified for a user having a role of Campus Champion.',
                        false
                    );
                    return;
                }

                var dataAcls = Object.values(CCR.xdmod.UserTypes);

                var intersection = CCR.intersect(dataAcls, acls);

                if (intersection.length === 0) {
                    CCR.xdmod.ui.userManagementMessage('You must select a non-flag acl for the user. ( i.e. anything not Manager or Developer ');
                    return;
                }

                var populatedAcls = {};
                for (var i = 0; i < acls.length; i++) {
                    var acl = acls[i];
                    if (!populatedAcls.hasOwnProperty(acl)) {
                        populatedAcls[acl] = roleGrid.getCenters(acl);
                    }
                }

                var objParams = {
                    operation: 'update_user',
                    uid: selected_user_id,
                    email_address: existingUserEmailField.getValue(),
                    acls: Ext.util.JSON.encode(populatedAcls),
                    assigned_user: (cmbUserMapping.getValue().length === 0) ?
                                 '-1' :
                                 cmbUserMapping.getValue(),
                    institution: (cmbInstitution.getValue().length === 0) ?
                                 '-1' :
                                 cmbInstitution.getValue(),
                    user_type: cmbUserType.getValue()
                };

                Ext.Ajax.request({
                    url: '../controllers/user_admin.php',
                    params: objParams,
                    method: 'POST',

                    callback: function (options, success, response) {
                        var json;
                        if (success) {
                            json = CCR.safelyDecodeJSONResponse(response);
                            success = CCR.checkDecodedJSONResponseSuccess(json);
                        }

                        if (!success) {
                            CCR.xdmod.ui.presentFailureResponse(response, {
                                title: 'User Management',
                                wrapperMessage: 'User update failed.'
                            });
                            return;
                        }

                        self.resetDirtyState();
                        saveIndicator.hide();

                        CCR.xdmod.ui.userManagementMessage(
                            json.status,
                            json.success
                        );

                        // Reload user list only if the previously
                        // updated user was relocated into another
                        // "user type" group
                        if (
                            json.user_type != self.cachedUserTypeID
                        ) {
                            self.reloadUserList(self.cachedUserTypeID);
                        }

                        if (user_update_callback) {
                            user_update_callback();
                        }
                    }//callback
                });//Ext.Ajax.request
            }//handler
        });//btnSaveChanges

        // ------------------------------------------

        var mnuItemPasswordReset = new Ext.menu.Item({
            text: 'Send Password Reset',
            //hidden: true,
            handler: actionPasswordReset
        });

        var mnuActions = new Ext.menu.Menu({
            plain: true,
            showSeparator: false,
            cls: 'no-icon-menu',

            items: [
                {
                    id: 'disableAccountMenuItem',
                    text: 'Disable This Account',
                    handler: function (b, e) {
                        actionToggleAccountStatus("Disable");
                    }
                },
                {
                    id: 'enableAccountMenuItem',
                    text: 'Enable This Account',
                    handler: function (b, e) {
                        actionToggleAccountStatus("Enable");
                    }
                },
                mnuItemPasswordReset,
                {
                    text: 'Delete This Account',
                    handler: actionDeleteAccount
                },
                '-',
                {
                    text: 'Empty Report Image Cache',
                    handler: actionEmptyReportImageCache
                }
            ]
        });

        // ------------------------------------------

        var accessSettings = new Ext.Panel({
            flex: 0.25,
            labelWidth: 95,
            frame: true,
            title: 'Access Details',
            bodyStyle: 'padding:5px 5px 0',
            defaults: { width: 170 },
            cls: 'admin_panel_existing_user_settings',
            labelAlign: 'top',
            defaultType: 'textfield',

            layout: 'column',

            items: [
                {
                    xtype: 'tbtext',
                    text:'<p>Time Created:</p><p>Last Logged In:</p><p>Last Updated:</p>',
                    columnWidth: 0.35
                },
                {
                    xtype: 'tbtext',
                    id: 'txtAccountTimestamps',
                    text: '',
                    width: 168,
                    cls: 'admin_panel_timestamp',
                    style: 'font-size: 11px'
                }
            ]
        });//accessSettings

        // ------------------------------------------

        var innerPanel = new Ext.Panel({
            layout: {
                type: 'hbox',
                padding: '0 0 0 0',
                align: 'stretch'
            },

            flex: 0.75,
            border: false,

            items: [
                roleSettings,
                userSettings
            ],

            baseCls: 'x-plain'
        });

        // ------------------------------------------

        var outerPanel = new Ext.Panel({
            layout: {
                type: 'vbox',
                padding: '0 0 0 0',
                align: 'stretch'
            },

            border: false,

            items: [
                innerPanel,
                accessSettings
            ],

            baseCls: 'x-plain'
        });

        // ------------------------------------------

        var userEditor = new Ext.Panel({
            id: 'admin_panel_user_editor',
            title: 'User Information',
            region: 'center',
            //flex: .55,
            margins: '2 2 2 0',

            border: true,
            layout: 'fit',
            //width: 450,

            tbar: {
                items: [
                    {
                        xtype: 'tbtext',
                        text: 'Status: '
                    },
                    {
                        xtype: 'tbtext',
                        id: 'txtAccountStatus',
                        text: ''
                    },
                    '->',
                    new Ext.Button({
                        text: 'Actions',
                        menu: mnuActions
                    })
                ]
            },

            plugins: [
                new Ext.ux.plugins.ContainerMask({
                    msg: 'Select A User From The List To The Left',
                    masked: true,
                    maskClass: 'admin_panel_editor_mask'
                })
            ],

            items: [
                outerPanel
            ]
        });//userEditor

        // ------------------------------------------

        var fetchUserDetails = function (user_id, reset_controls) {
            Ext.Ajax.request({
                url: '../controllers/user_admin.php',
                params:  {
                    operation: 'get_user_details',
                    uid: user_id
                },
                method: 'POST',

                callback: function (options, success, response) {
                    var json;
                    if (success) {
                        json = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(json);
                    }

                    if (!success) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: "User Management",
                            wrapperMessage: "Failed to load user."
                        });
                        return;
                    }

                    roleGrid.setDirtyState(false);
                    roleGrid.reset();
                    settingsAreDirty = false;

                    saveIndicator.hide();

                    cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
                    cmbInstitution.removeClass('admin_panel_invalid_text_entry');

                    // Account status details ---------------
                   
                   /**
                    * Retrieving a reference to the txtAccountTimestamps
                    * first. Then overriding some of the broken methods
                    * provided by Ext.menu.BaseItem. The reason 
                    * is that they do not have an up to date 'el' 
                    * property, or really, an 'el'  property at all.  
                    * This is required for the 'update' method calls,
                    * made later in the process, to work successfully.  
                    */ 
                    var txtAccountTimestamps = Ext.getCmp('txtAccountTimestamps');
                    var refreshEl = function() {
                         if ( this.el === undefined 
                                || this.el === null ) {

                            this.el = Ext.get(this.id);
                        }
                        return this.el;
                    }; // refreshEl

                    txtAccountTimestamps.getContentTarget = refreshEl;
                    txtAccountTimestamps.getEl = refreshEl;


                    txtAccountTimestamps.update(
                        '<p>' + json.user_information.time_created + '</p>' +
                        '<p>' + json.user_information.time_last_logged_in + '</p>' +
                        '<p>' + json.user_information.time_updated + '</p>'
                    );

                    var txtAccountStatus = document.getElementById('txtAccountStatus');
                    txtAccountStatus.innerText = json.user_information.is_active;

                    if (json.user_information.is_active == 'active') {
                        Ext.getCmp('disableAccountMenuItem').show();
                        Ext.getCmp('enableAccountMenuItem').hide();
                        txtAccountStatus.classList.remove('admin_panel_user_user_status_disabled');
                        txtAccountStatus.classList.add('admin_panel_user_user_status_active');
                    }
                    else {
                        Ext.getCmp('enableAccountMenuItem').show();
                        Ext.getCmp('disableAccountMenuItem').hide();
                        txtAccountStatus.classList.remove('admin_panel_user_user_status_active');
                        txtAccountStatus.classList.add('admin_panel_user_user_status_disabled');
                    }

                    if (reset_controls) {
                        userEditor.setTitle('User Details: ' + Ext.util.Format.htmlEncode(json.user_information.formal_name));

                        existingUserEmailField.setValue(json.user_information.email_address);

                        // Remaining inputs ----------------------

                        mapping_cached_person_name = json.user_information.assigned_user_name;
                        mapping_cached_person_id = json.user_information.assigned_user_id;

                        cached_user_type = parseInt(json.user_information.user_type);


                        if (json.user_information.assigned_user_id != '-1') {
                            cmbUserMapping.initializeWithValue(json.user_information.assigned_user_id, json.user_information.assigned_user_name);
                        } else {
                            cmbUserMapping.reset();
                        }

                        if (cached_user_type === CCR.xdmod.FEDERATED_USER_TYPE) {
                            // XSEDE-derived User: Can't change user type
                            cmbUserType.hide();
                            lblXSEDEUser.show();

                            mnuItemPasswordReset.hide();

                            //cmbUserMapping.reset();

                        }
                        else {

                            // All other (non-XSEDE-derived) users
                            lblXSEDEUser.hide();
                            cmbUserType.show();

                            mnuItemPasswordReset.show();

                            cmbUserType.setDisabled(false);
                            cmbUserType.setValue(cached_user_type);
                        }

                        // -----------------------------

                        if (json.user_information.institution != '-1') {
                            cmbInstitution.setDisabled(false);
                            cmbInstitution.initializeWithValue(json.user_information.institution, json.user_information.institution_name);
                        }
                        else {
                            cmbInstitution.setDisabled(true);
                        }

                        // -----------------------------

                        tg_user_list_phase = 'load_user';

                        /**
                         * acls are in the form:
                         * {
                         *   // If the user's acl has one or more relations to centers
                         *   "<acl_name>": ["<center_1>", "<center_2>"],
                         *
                         *   // If the acl has no relation to centers
                         *   "<acl_name>": []
                         * }
                         */
                        for (var acl in json.user_information.acls) {
                            if (json.user_information.acls.hasOwnProperty(acl)) {
                                var centers = json.user_information.acls[acl];
                                roleGrid.setCenterConfig(acl, centers);
                            }
                        }
                        roleGrid.setSelectedAcls(Object.keys(json.user_information.acls));
                        roleGrid.updateCenterCounts();

                        userSettings.setDisabled(false);
                        userEditor.hideMask();
                        btnSaveChanges.setDisabled(false);

                    }//if (reset_controls == true)

                }//callback
            });//Ext.Ajax.request
        };//fetchUserDetails

        // ------------------------------------------

        var grid = new Ext.grid.GridPanel({
            store: storeUserListing,
            cm: cm,
            title: 'Existing Users',
            region: 'west',
            layout: 'fit',
            width: 510,
            enableHdMenu: false,
            clicksToEdit: 1,
            border: true,
            margins: '2 0 2 2',

            viewConfig: {
                emptyText: 'No users in this category currently exist'
            },

            tbar: {
                items: [
                    {
                        xtype: 'tbtext',
                        cls: 'admin_panel_tbtext',
                        text:'Displaying',
                        flex: 1
                    },
                    usersTypeSplitButton,
                    '->',
                    {
                        xtype: 'tbtext',
                        cls: 'admin_panel_tbtext',
                        text:'<a href="javascript:void(0)" onClick="displayExceptionEmails()">List Exception E-Mails</a>',
                        flex: 1
                    }
                ]
            },

            listeners: {
                'render' : function () {
                    Ext.getBody().on(
                        "contextmenu",
                        Ext.emptyFn,
                        null,
                        { preventDefault: true }
                    );
                },

                'cellclick' : function (grid, rowindex, colindex, e) {
                    if (selected_user_id != -1 && self.inDirtyState()) {
                        Ext.Msg.show({
                            maxWidth: 800,
                            minWidth: 400,

                            title: 'Unsaved Changes',

                            msg: "There are unsaved changes to this account.<br />" +
                                 "Do you wish to save these changes before continuing?<br /><br />" +
                                 "If you choose <b>No</b>, you will lose all your changes.",

                            buttons: {
                                yes: "Yes (go back and save)",
                                no: "No (discard changes)"
                            },

                            fn: function (resp) {
                                if (resp == 'yes') {
                                    var targetIndex = grid.store.find('id', selected_user_id);
                                    grid.getSelectionModel().selectRow(targetIndex);
                                    return;
                                }

                                if (resp == 'no') {
                                    self.resetDirtyState();
                                    saveIndicator.hide();

                                    selected_user_id = grid.store.getAt(rowindex).data.id;
                                    selected_username = grid.store.getAt(rowindex).data.username;

                                    fetchUserDetails(grid.store.getAt(rowindex).data.id, true);
                                }
                            },

                            icon: Ext.MessageBox.QUESTION

                        });//Ext.Msg.show

                        return;
                    }

                    selected_user_id = this.store.getAt(rowindex).data.id;
                    selected_username = this.store.getAt(rowindex).data.username;

                    fetchUserDetails(this.store.getAt(rowindex).data.id, true);
                }//'cellclick'
            }//listeners
        });//grid

        // Disable key navigation of the user grid
        grid.getSelectionModel().onKeyPress = Ext.emptyFn;

        // ------------------------------------------

        var saveIndicator = new Ext.Toolbar.TextItem({
            html: '<span style="color: #f00">(There are unsaved changes to this account)</span>',
            hidden: true
        });

        Ext.apply(this, {
            title: 'Current Users',
            border: false,
            height: 430,
            cls: 'no-underline-invalid-fields-form',

            bbar: {
                items: [
                    btnSaveChanges,
                    saveIndicator,
                    '->',
                    new Ext.Button({
                        text: 'Close',
                        iconCls: 'general_btn_close',
                        handler: function() { self.parentWindow.hide(); }
                    })
                ]
            },
            layout: 'border',
            items: [
                grid,
                userEditor
            ]
        });//Ext.apply

        XDMoD.ExistingUsers.superclass.initComponent.call(this);

    }//initComponent

});//XDMoD.ExistingUsers

