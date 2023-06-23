XDMoD.Profile = {
    logoutOnClose: false
};

XDMoD.ProfileEditorConstants = {

    PASSWORD: 0,
    WELCOME_EMAIL_CHANGE: 1, // designates if we're displaying first time login prompt to validate email
    SSO_USER: 5 // designates whether or not this is a Single Sign On user

}; // XDMoD.ProfileEditorConstants

// --------------------------------------------

XDMoD.ProfileEditor = Ext.extend(Ext.Window, {
    id: 'xdmod-profile-editor',
    width: 400,

    border: false,
    frame: true,

    iconCls: 'user_profile_16',

    modal: true,
    closable: true,

    closeAction: 'close',
    resizable: false,
    stateful: false,

    title: 'My Profile',

    tooltip: 'Profile Editor',

    init: function () {
        var tabs = [this.general_settings, this.api_token];
        this.addListener('afterrender', function () {
            tabs.forEach(function (tab) {
                tab.handleOpenEvent();
            });
        });
        this.addListener('close', function () {
            tabs.forEach(function (tab) {
                tab.handleCloseEvent();
            });
        });
        tabs.forEach(function (tab) {
            tab.init();
        });
        this.show();
    },

    handleProfileClose: function () {
        if (XDMoD.Profile.logoutOnClose) {
            Ext.Msg.show({
                maxWidth: 800,
                minWidth: 400,
                title: 'Close profile and logout?',
                msg: 'If you do not supply an e-mail address, you will be logged out of XDMoD.<br/><br/>Are you sure you want to do this?',
                buttons: Ext.Msg.YESNO,

                fn: function (resp) {
                    if (resp === 'yes') {
                        CCR.xdmod.ui.actionLogout();
                    }
                }, // fn

                icon: Ext.MessageBox.QUESTION
            }); // Ext.Msg.show

            return false;
        } // if (XDMoD.Profile.logoutOnClose)

        return true;
    },

    getCloseButton: function () {
        var self = this;

        return new Ext.Button({
            text: 'Close',
            iconCls: 'general_btn_close',
            handler: function () {
                self.close();
            }
        });
    },

    initComponent: function () {
        var self = this;

        this.general_settings = new XDMoD.ProfileGeneralSettings({
            parentWindow: self
        });

        this.api_token = new XDMoD.ProfileApiToken({
            parentWindow: self
        });

        // ------------------------------------------------

        this.on('beforeclose', self.handleProfileClose);

        // ------------------------------------------------

        var tabItems = [
            this.general_settings
        ];

        if (CCR.xdmod.ui.isCenterDirector) {
            tabItems.push(new XDMoD.ProfileRoleDelegation({
                parentWindow: self
            }));
        }

        if (CCR.xdmod.ui.tgSummaryViewer.title === 'Dashboard') {
            tabItems.push({
                title: 'Settings',
                height: 316,
                layout: 'fit',
                border: false,
                frame: true,
                items: [{
                    items: [{
                        title: 'User Interface',
                        xtype: 'form',
                        bodyStyle: 'padding: 5px',
                        labelWidth: 230,
                        frame: true,
                        items: [{
                            xtype: 'button',
                            fieldLabel: 'Dashboard Layout',
                            text: 'Reset to Default',
                            handler: function (button) {
                                Ext.Ajax.request({
                                    url: XDMoD.REST.url + '/dashboard/layout',
                                    method: 'DELETE',
                                    success: function () {
                                        button.setDisabled(true);
                                        CCR.xdmod.ui.tgSummaryViewer.fireEvent('request_refresh');
                                    },
                                    failure: function (response, opts) {
                                        Ext.MessageBox.alert('Error', response.message);
                                    }
                                });
                            }
                        }]
                    }]
                }],
                bbar: {
                    items: [
                        '->',
                        self.getCloseButton()
                    ]
                }
            });
        }

        tabItems.push(this.api_token);

        var tabPanel = new Ext.TabPanel({
            frame: false,
            border: false,
            activeTab: 0,

            defaults: {
                tabCls: 'tab-strip'
            },

            items: tabItems,

            listeners: {
                tabchange: function (thisTabPanel, tab) {
                    XDMoD.utils.syncWindowShadow(thisTabPanel);

                    // Fix a bug where invalid field errors are displayed poorly
                    // after a tab switch by forcing form validation.
                    if (tab !== self.general_settings) {
                        return;
                    }

                    tab.cascade(function (currentComponent) {
                        if (currentComponent instanceof Ext.form.FormPanel) {
                            currentComponent.getForm().isValid();
                            return false;
                        }

                        return true;
                    });
                }
            }
        }); // tabPanel

        // ------------------------------------------------

        Ext.apply(this, {
            items: [
                tabPanel
            ]
        });

        XDMoD.ProfileEditor.superclass.initComponent.call(this);
    } // initComponent
}); // XDMoD.ProfileEditor
