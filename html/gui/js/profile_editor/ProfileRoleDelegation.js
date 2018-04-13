XDMoD.ProfileRoleDelegation = Ext.extend(Ext.Panel, {

    autoHeight: true,
    border: false,
    frame: true,
    resizable: false,
    title: 'Role Delegation',
    cls: 'role_manager',

    reloadEligibleUsers: function (userId) {
        // if userId is specified then attempt to select it after the store has
        // reloaded.
        this.storeCenterStaff.on('load', function () {
            if (userId !== undefined && this.storeCenterStaff.getById(userId) !== undefined) {
                // we just need to select the user as the UI is already setup
                // to handle this case.
                this.cmbCenterStaff.select(userId);
            } else {
                // The user that was modified was not found in the current list
                // of users. We need to update the UI to reflect this new state.
                this.btnElevateUser.setVisible(false);
                this.btnDowngradeUser.setVisible(false);
                this.lblMemberStatus.update('Select a staff member using the list above.');
            }
        }, this, { single: true });
    },

    initComponent: function () {
        var self = this;

        var storeCenterStaff = new Ext.data.JsonStore({
            url: 'controllers/role_manager.php',
            baseParams: { operation: 'enum_center_staff_members' },
            root: 'members',
            autoLoad: true,
            idProperty: 'id',
            fields: ['id', 'name']
        });// storeCenterStaff

        // ---------------------------------------------------------

        storeCenterStaff.on('load', function (store, records, options) {
            var recordsFound = records.length > 0;

            /* eslint-disable no-use-before-define */
            lblStatus.setVisible(!recordsFound);

            sectionAssign.setVisible(recordsFound);
            lblMemberStatus.setVisible(recordsFound);
            /* eslint-enable no-use-before-define */

            XDMoD.utils.syncWindowShadow(self);
        });
        this.storeCenterStaff = storeCenterStaff;

        // ---------------------------------------------------------

        var cmbCenterStaff = new Ext.form.ComboBox({

            editable: false,
            fieldLabel: 'Staff Member',
            store: storeCenterStaff,
            triggerAction: 'all',
            displayField: 'name',
            valueField: 'id',
            emptyText: 'No Member Selected',
            listeners: {
                select: function (combo, value, index) {
                    var conn = new Ext.data.Connection();
                    conn.request({
                        url: 'controllers/role_manager.php',
                        params: {
                            operation: 'get_member_status',
                            member_id: combo.getValue()
                        },
                        method: 'POST',
                        callback: function (options, success, response) {
                            if (success) {
                                var json = Ext.util.JSON.decode(response.responseText);
                                /* eslint-disable no-use-before-define */
                                if (json.success) {
                                    if (json.eligible === true) {
                                        lblMemberStatus.update('');
                                    } else {
                                        lblMemberStatus.update('<p><b>' + combo.getRawValue() + '</b></p><p>' + json.message + '</p>');
                                    }

                                    btnElevateUser.setVisible(json.eligible);
                                    btnDowngradeUser.setVisible(!json.eligible);
                                } else {
                                    lblMemberStatus.update('<p><b>' + combo.getRawValue() + '</b></p><span style="color: #00f">' + json.message + '</span>');
                                    lblMemberStatus.setVisible(true);

                                    btnElevateUser.setVisible(false);
                                    btnDowngradeUser.setVisible(false);
                                }
                                /* eslint-enable no-use-before-define */
                                XDMoD.utils.syncWindowShadow(self);
                            } else {
                                Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
                            }
                        }// callback
                    });// conn.request
                }// select
            }// listeners
        });// cmbCenterStaff
        this.cmbCenterStaff = cmbCenterStaff;

        var sectionAssign = new Ext.Panel({
            hidden: true,

            items: [
                new Ext.Panel({
                    labelWidth: 95,
                    padding: '5px',
                    frame: true,
                    title: 'Delegate Center Staff Privileges',
                    defaults: {
                        width: 200
                    },
                    layout: 'form',

                    items: [
                        cmbCenterStaff
                    ]
                })
            ]
        });// sectionAssign

        // ---------------------------------------------------------

        var btnElevateUser = new Ext.Button({

            text: 'Upgrade Staff Member To Center Staff',
            hidden: true,

            handler: function () {
                var conn = new Ext.data.Connection();
                conn.request({
                    url: 'controllers/role_manager.php',
                    params: {
                        operation: 'upgrade_member',
                        member_id: cmbCenterStaff.getValue()
                    },
                    method: 'POST',
                    callback: function (options, success, response) {
                        if (success) {
                            var json = Ext.util.JSON.decode(response.responseText);
                            /* eslint-disable no-use-before-define */
                            if (json.success) {
                                lblMemberStatus.update('<p><b>' + cmbCenterStaff.getRawValue() + '</b></p><p>' + json.message + '</p>');

                                btnElevateUser.setVisible(false);
                                btnDowngradeUser.setVisible(true);
                                lblMemberStatus.setVisible(true);

                                XDMoD.utils.syncWindowShadow(self);
                            } else {
                                Ext.MessageBox.alert('Role Manager', json.message);
                            }
                            /* eslint-disable no-use-before-define */
                        } else {
                            Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
                        }
                    }// callback
                });// conn.request
            }// handler
        });// btnElevateUser
        this.btnElevateUser = btnElevateUser;

        // ---------------------------------------------------------

        var btnDowngradeUser = new Ext.Button({

            text: 'Revoke Center Staff Privileges',
            hidden: true,
            handler: function () {
                var conn = new Ext.data.Connection();
                conn.request({

                    url: 'controllers/role_manager.php',
                    params: {
                        operation: 'downgrade_member',
                        member_id: cmbCenterStaff.getValue()
                    },
                    method: 'POST',
                    callback: function (options, success, response) {
                        if (success) {
                            var json = Ext.util.JSON.decode(response.responseText);

                            if (json.success) {
                                btnDowngradeUser.setVisible(false);
                                btnElevateUser.setVisible(true);

                                lblMemberStatus.update('');

                                XDMoD.utils.syncWindowShadow(self);
                                self.reloadEligibleUsers(cmbCenterStaff.getValue());
                            } else {
                                Ext.MessageBox.alert('Role Manager', json.message);
                            }
                        } else {
                            Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
                        }
                    }// callback
                });// conn.request
            }// handler
        });// btnDowngradeUser
        this.btnDowngradeUser = btnDowngradeUser;

        var lblMemberStatus = new Ext.BoxComponent({
            hidden: true,
            cls: 'lbl_member_status',
            autoHeight: true,
            html: 'Select a staff member using the list above.'
        });
        this.lblMemberStatus = lblMemberStatus;

        var lblStatus = new Ext.BoxComponent({
            hidden: true,
            html: '<center><div style="padding-top: 6px; color: #000">No staff members for your center could be found.<br /><br />' +
            'Please contact the XDMoD portal team at<br /><a href="mailto:' + CCR.xdmod.tech_support_recipient + '?subject=Center Staff Accounts">' + CCR.xdmod.tech_support_recipient + '</a>' +
            '<br />to request center staff accounts.<br /><br />' +
            'You will be able to use this feature once<br />center staff accounts have been established.</div></center>'
        });

        // ---------------------------------------------------------

        this.on('render', function () {
            storeCenterStaff.load();
        });

        Ext.apply(this, {

            items: [
                sectionAssign,
                lblMemberStatus,
                new Ext.Panel({
                    buttonAlign: 'center',

                    buttons: [
                        btnElevateUser,
                        btnDowngradeUser
                    ]
                }),
                lblStatus
            ],
            bbar: {

                items: [
                    '->',
                    self.parentWindow.getCloseButton()
                ]
            }
        });

        XDMoD.ProfileRoleDelegation.superclass.initComponent.call(this);
    }// initComponent
});// XDMoD.ProfileRoleDelegation
