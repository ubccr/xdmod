Ext.namespace('XDMoD');

XDMoD.CreateUser = Ext.extend(Ext.form.FormPanel, {

    // This flag is set to true when a new user has been created via
    // this form
    usersRecentlyAdded: false,

    // This holds the value of the user_type of the last user created
    userTypeRecentlyAdded: 0,

    initComponent: function () {

        var me = this;
        var base_controller = '../controllers/user_admin.php';

        // conditionally overridden in the call to initialize()
        var account_request_id = '';

        //conditionally overridden in the call to initialize()
        var account_creation_callback;

        var leftColumnFieldWidth = 205;

        var cmbUserMapping = new CCR.xdmod.ui.TGUserDropDown({
            dashboardMode: true,
            user_management_mode: true,
            controllerBase: '../controllers/sab_user.php',
            fieldLabel: 'Map To',
            emptyText: 'User not mapped',
            hiddenName: 'nm_new_user_mapping',
            width: 150
        });

        cmbUserMapping.on('disable', function () { cmbUserMapping.reset(); });

        var cmbInstitution = new CCR.xdmod.ui.InstitutionDropDown({
            controllerBase: base_controller,
            disabled: true,
            fieldLabel: 'Institution',
            emptyText: 'No Institution Selected',
            width: leftColumnFieldWidth
        });

        cmbInstitution.on('disable', function () { cmbInstitution.reset(); });
        cmbInstitution.on('change', function (combo) {
            combo.removeClass('admin_panel_invalid_text_entry');
        });

        var storeUserType = new DashboardStore({
            url: base_controller,
            root: 'user_types',
            baseParams: { 'operation' : 'enum_user_types' },
            fields: ['id', 'type'],
            autoLoad: true
        });

        var cmbUserType = new Ext.form.ComboBox({
            editable: false,
            fieldLabel: 'User Type',
            store: storeUserType,
            displayField: 'type',
            triggerAction: 'all',
            valueField: 'id',

            // "External" user is the default.
            value: 1,

            emptyText: 'No User Type Selected',
            width: 160
        });

        cmbUserType.on('disable', function () { cmbUserType.reset(); });

        var btnFindUser = new Ext.Button({
            text: 'Find',
            width: 50,

            handler: function () {
                var fieldsToValidate = [txtFirstName, txtLastName];

                // Sanitization

                for (var i = 0; i < fieldsToValidate.length; i++) {
                    if (!fieldsToValidate[i].isValid()) {
                        CCR.xdmod.ui.userManagementMessage('Please fix any errors in the name fields.', false);
                        return;
                    }
                }

                var searchCrit = {
                    first_name: txtFirstName.getValue(),
                    last_name: txtLastName.getValue()
                };

                if (txtEmailAddress.getValue() !== '') {
                    searchCrit.email_address = txtEmailAddress.getValue();
                }

                if (cmbInstitution.getValue() !== '') {
                    searchCrit.organization = cmbInstitution.getValue();
                }

                me.setUserMapping(searchCrit, true);
            }
        });

        var fieldRequiredText = 'This field is required.';

        var minUsernameLength = XDMoD.constants.minUsernameLength;
        var maxUsernameLength = XDMoD.constants.maxUsernameLength;
        var txtUsername = new Ext.form.TextField({
            fieldLabel: 'Username',
            emptyText: minUsernameLength + ' min, ' + maxUsernameLength + ' max',
            msgTarget: 'under',
            width: leftColumnFieldWidth,

            allowBlank: false,
            blankText: fieldRequiredText,
            minLength: minUsernameLength,
            minLengthText: 'Minimum length (' + minUsernameLength + ' characters) not met.',
            maxLength: maxUsernameLength,
            maxLengthText: 'Maximum length (' + maxUsernameLength +  ' characters) exceeded.',
            regex: XDMoD.regex.usernameCharacters,
            regexText: 'The username must consist of alphanumeric characters only, or it can be an e-mail address.',

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });

        var fsUserDetails = new Ext.form.FieldSet({
            title: 'User Details',
            cls: 'admin_panel_user_details',

            items: [
                txtUsername,
                {
                    xtype: 'compositefield',
                    items: [
                        cmbUserMapping,
                        btnFindUser
                    ]
                },
                cmbInstitution
            ]
        });

        var noReservedCharactersText = 'This field may not contain reserved characters. ($, ^, #, <, >, ", :, \\, /, !)';

        var maxFirstNameLength = XDMoD.constants.maxFirstNameLength;
        var txtFirstName = new Ext.form.TextField({
            fieldLabel: 'First Name',
            emptyText: '1 min, ' + maxFirstNameLength + ' max',
            msgTarget: 'under',
            width: leftColumnFieldWidth,

            allowBlank: false,
            blankText: fieldRequiredText,
            maxLength: maxFirstNameLength,
            maxLengthText: 'Maximum length (' + maxFirstNameLength + ' characters) exceeded.',
            regex: XDMoD.regex.noReservedCharacters,
            regexText: noReservedCharactersText,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });

        var maxLastNameLength = XDMoD.constants.maxLastNameLength;
        var txtLastName = new Ext.form.TextField({
            fieldLabel: 'Last Name',
            emptyText: '1 min, ' + maxLastNameLength + ' max',
            msgTarget: 'under',
            width: leftColumnFieldWidth,

            allowBlank: false,
            blankText: fieldRequiredText,
            maxLength: maxLastNameLength,
            maxLengthText: 'Maximum length (' + maxLastNameLength + ' characters) exceeded.',
            regex: XDMoD.regex.noReservedCharacters,
            regexText: noReservedCharactersText,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });

        var minEmailLength = XDMoD.constants.minEmailLength;
        var maxEmailLength = XDMoD.constants.maxEmailLength;
        var txtEmailAddress = new Ext.form.TextField({
            fieldLabel: 'E-Mail Address',
            emptyText: minEmailLength + ' min, ' + maxEmailLength + ' max',
            msgTarget: 'under',
            width: leftColumnFieldWidth,

            allowBlank: false,
            blankText: fieldRequiredText,
            minLength: minEmailLength,
            minLengthText: 'Minimum length (' + minEmailLength + ' characters) not met.',
            maxLength: maxEmailLength,
            maxLengthText: 'Maximum length (' + maxEmailLength + ' characters) exceeded.',
            validator: XDMoD.validator.email,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });

        var fsUserInformation = new Ext.form.FieldSet({
            title: 'User Information',
            cls: 'admin_panel_section_user_information',
            items: [
                txtFirstName,
                txtLastName,
                txtEmailAddress
            ]
        });

        this.setFirstName = function (v) {
            txtFirstName.setValue(v);
        };

        this.setLastName = function (v) {
            txtLastName.setValue(v);
        };

        this.setEmailAddress = function (v) {
            txtEmailAddress.setValue(v);
        };

        this.setUsername = function (v) {
            txtUsername.setValue(v);
        };

        this.setOrganization = function (v) {
            cmbInstitution.getStore().load({
                params: {
                    query: v,
                    start: 0,
                    limit: 1000
                },
                callback: function (records) {
                    Ext.each(records, function (item, index) {
                        if (item.get('name') === v) {
                            cmbInstitution.setValue(item.get('id'));
                        }
                    }, this);
                }
            });
        };

        this.setUserMapping = function (searchCrit, prompt) {
            prompt = prompt || false;

            var me = this;

            Ext.Ajax.request({
                url: '../controllers/user_admin.php',

                params: {
                    operation: 'search_users',
                    search_crit: Ext.encode(searchCrit)
                },

                method: 'POST',

                scope: me,

                callback: function (options, success, response) {
                    var json;
                    if (success) {
                        json = CCR.safelyDecodeJSONResponse(response);
                        success = CCR.checkDecodedJSONResponseSuccess(json);
                    }

                    if (!success) {
                        CCR.xdmod.ui.presentFailureResponse(response, {
                            title: 'User Management',
                            wrapperMessage: 'Setting user mapping failed.'
                        });
                        return;
                    }

                    if (json.total === 1) {
                        if (json.data[0].exact_match) {
                            cmbUserMapping.initializeWithValue(
                                json.data[0].person_id,
                                json.data[0].person_name
                            );
                        } else {
                            me.displayUserGrid(json.data);
                        }
                    } else if (prompt) {
                        if (json.total === 0) {
                            var msg = 'No match could be found for "' +
                                searchCrit.last_name + ', ' +
                                searchCrit.first_name + '".';
                            CCR.xdmod.ui.userManagementMessage(msg, false);
                        } else {
                            me.displayUserGrid(json.data);
                        }
                    }
                }
            });
        };

        this.displayUserGrid = function (users) {
            var userFields, usersArray, usersStore, grid, win;

            userFields = [
                'person_id',
                'person_name',
                'first_name',
                'last_name',
                'email_address',
                'organization'
            ];

            usersStore = new Ext.data.ArrayStore({
                autoDestroy: true,
                fields: userFields,
                idIndex: 0
            });

            usersArray = [];
            Ext.each(users, function (user) {
                var userArr = [];

                Ext.each(userFields, function (field) {
                    userArr.push(Ext.isEmpty(user[field]) ? '' : user[field]);
                }, this);

                usersArray.push(userArr);
            }, this);

            usersStore.loadData(usersArray);

            grid = new Ext.grid.GridPanel({
                store: usersStore,

                viewConfig: {
                    forceFit: true
                },

                colModel: new Ext.grid.ColumnModel({
                    singleSelect: true
                }),

                columns: [
                    {
                        header: 'First Name',
                        width: 80,
                        dataIndex: 'first_name',
                        sortable: true
                    },
                    {
                        header: 'Last Name',
                        width: 80,
                        dataIndex: 'last_name',
                        sortable: true
                    },
                    {
                        header: 'Organization',
                        width: 80,
                        dataIndex: 'organization',
                        sortable: true
                    },
                    {
                        header: 'E-Mail Address',
                        width: 60,
                        dataIndex: 'email_address',
                        sortable: true
                    }
                ],

                listeners: {
                    rowdblclick: function (grid, rowIndex) {
                        var user = usersStore.getAt(rowIndex);
                        cmbUserMapping.initializeWithValue(
                            user.get('person_id'),
                            user.get('person_name')
                        );
                        win.close();
                    }
                }
            });

            win = new Ext.Window({
                title: 'No exact match found, select one',
                autoShow: true,
                layout: 'fit',
                border: false,
                frame: true,
                modal: true,
                width: 800,
                height: 400,
                resizable: false,
                items: [
                    grid
                ]
            });

            win.show();
        };

        /* eslint-disable no-use-before-define */
        var roleGridClickHandler = function () {
            var selRoles = newUserRoleGrid.getSelectedAcls();
            cmbInstitution.setDisabled(selRoles.itemExists('cc') === -1);
        };

        var newUserRoleGrid = new XDMoD.Admin.AclGrid({
            cls: 'admin_panel_section_role_assignment_n',
            role_description_column_width: 140,
            layout: 'fit',
            height: 200,
            selectionChangeHandler: roleGridClickHandler
        });
        /* eslint-enable no-use-before-define */

        this.setCallback = function (callback) {
            account_creation_callback = callback;
        };

        this.reset = function () {
            account_request_id = '';
            account_creation_callback = undefined;
        };

        this.initialize = function (config) {
            if (config.accountRequestID) {
                account_request_id = config.accountRequestID;
            }

            if (config.accountCreationCallback) {
                account_creation_callback = config.accountCreationCallback;
            }

            this.setFirstName();
            this.setLastName();
            this.setEmailAddress();
            this.setUsername();

            cmbUserMapping.reset();

            cmbUserType.reset();

            cmbInstitution.reset();
            cmbInstitution.setDisabled(true);

            newUserRoleGrid.reset();
        };

        var fsRoleAssignment = new Ext.form.FieldSet({
            title: 'Acl Assignment',

            items: [
                newUserRoleGrid
            ]
        });

        var fsUserType = new Ext.form.FieldSet({
            title: 'Additional Settings',
            labelAlign: 'left',

            items: [
                cmbUserType
            ]
        });

        var btnCreateUser = new Ext.Button({
            text: 'Create User',

            iconCls: 'admin_panel_btn_create_user',

            handler: function () {
                var institution = (cmbInstitution.getValue().length > 0) ? cmbInstitution.getValue() : -1;
                var mapped_user_id = (cmbUserMapping.getValue().length > 0) ? cmbUserMapping.getValue() : -1;

                // Sanitization

                cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
                cmbInstitution.removeClass('admin_panel_invalid_text_entry');
                cmbUserType.removeClass('admin_panel_invalid_text_entry');

                if (!me.getForm().isValid()) {
                    CCR.xdmod.ui.userManagementMessage('Please fix any fields marked as invalid.', false);
                    return;
                }
                var acls = newUserRoleGrid.getSelectedAcls();

                if (acls.length <= 0) {
                    CCR.xdmod.ui.userManagementMessage('This user must have at least one role.', false);
                    return;
                }

                if ((acls.indexOf('usr') !== -1 || acls.indexOf('pi') !== -1) && cmbUserMapping.getValue().length === 0) {
                    cmbUserMapping.addClass('admin_panel_invalid_text_entry');

                    CCR.xdmod.ui.userManagementMessage('This user must be mapped to a XSEDE Account<br>(Using the drop-down list)', false);
                    return;
                }

                if (!cmbUserMapping.disabled && cmbUserMapping.getValue() == cmbUserMapping.getRawValue()) {
                    cmbUserMapping.addClass('admin_panel_invalid_text_entry');

                    CCR.xdmod.ui.userManagementMessage('Cannot find <b>' + cmbUserMapping.getValue() + '</b> in the directory.<br>Please select a name from the drop-down list.', false);
                    return;
                }

                if (acls.indexOf('cc') >= 0 && cmbInstitution.getValue().length === 0) {
                    cmbInstitution.addClass('admin_panel_invalid_text_entry');
                    CCR.xdmod.ui.userManagementMessage('An institution must be specified for a user having a role of Campus Champion.', false);
                    return;
                }

                if (cmbUserType.getValue().length === 0) {
                    cmbUserType.addClass('admin_panel_invalid_text_entry');
                    CCR.xdmod.ui.userManagementMessage('This user must have a type associated with it.', false);
                    return;
                }
                // these have their own variable so that they can be easily
                // swapped out for the results of a call to the backend to retrieve
                // the current list of feature acls. And to make it clear what this
                // array of strings represents.
                var featureAcls = ['dev', 'mgr'];

                var nonFeatureAcls = CCR.difference(featureAcls, acls);

                if (nonFeatureAcls.length === 0) {
                    CCR.xdmod.ui.userManagementMessage('You must select a non-flag acl for the user. ( i.e. anything not Manager or Developer ');
                    return;
                }

                var populatedAcls = {};
                // populate centers for any of the selected acls that have them
                for (var i = 0; i < acls.length; i++) {
                    var acl = acls[i];
                    populatedAcls[acl] = newUserRoleGrid.getCenters(acl);
                }

                // Submit request

                var objParams = {
                    operation: 'create_user',

                    account_request_id: account_request_id,

                    first_name: txtFirstName.getValue(),
                    last_name: txtLastName.getValue(),
                    email_address: txtEmailAddress.getValue(),
                    username: txtUsername.getValue(),

                    acls: Ext.util.JSON.encode(populatedAcls),
                    assignment: mapped_user_id,
                    institution: institution,
                    user_type: cmbUserType.getValue()
                };

                Ext.Ajax.request({
                    url: base_controller,
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
                                wrapperMessage: 'Failed to create new user.'
                            });
                            return;
                        }

                        me.usersRecentlyAdded = true;
                        me.userTypeRecentlyAdded = json.user_type;

                        CCR.xdmod.ui.userManagementMessage(json.message, json.success);

                        if (account_creation_callback) {
                            account_creation_callback();
                        }
                    }
                });
            }
        });

        Ext.apply(this, {
            title: 'New User',
            border: false,
            cls: 'no-underline-invalid-fields-form',

            bbar: {
                items: [
                    btnCreateUser,
                    '->',
                    new Ext.Button({
                        text: 'Close',
                        iconCls: 'general_btn_close',
                        handler: function () { me.parentWindow.hide(); }
                    })
                ]
            },
            items: {
                border: false,

                // Force panel to have background color that it did before
                // implementing automatic height layout. It's not clear why
                // the panel's background defaults to white, unlike the
                // backgrounds of panels in the profile editor window.
                bodyStyle: 'background-color: #f1f1f1',

                layout: {
                    type: 'column',
                    columns: 2
                },

                items: [
                    {
                        border: false,
                        baseCls:'x-plain',
                        bodyStyle:'padding:5px 0px 5px 5px',
                        items: [
                            fsUserInformation,
                            fsUserDetails
                        ]
                    },
                    {
                        border: false,
                        width: 300,
                        baseCls:'x-plain',
                        bodyStyle:'padding:5px 5px 5px 5px',
                        items: [
                            fsRoleAssignment,
                            fsUserType
                        ]
                    }
                ]
            },
            listeners: {
                activate: function () {
                    newUserRoleGrid.reset();
                }
            }
        });

        XDMoD.CreateUser.superclass.initComponent.call(this);
    },

    onRender : function (ct, position) {
        XDMoD.CreateUser.superclass.onRender.call(this, ct, position);
    }
});

