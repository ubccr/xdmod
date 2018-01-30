XDMoD.AbstractContactDialog = Ext.extend(Ext.Window, {
    width: 470,
    modal: true,
    resizable: false,
    title: 'AbstractContactDialog',
    iconCls: 'contact_16',
    bodyStyle: 'padding:15px 13px 0',

    // Abstract properties
    trackEventCategory: null,
    successMessageHtml: null,
    contactReason: null,

    initComponent: function () {
        var self = this;

        var fieldRequiredText = 'This field is required.';

        var maxNameLength = XDMoD.constants.maxNameLength;
        var txtName = new Ext.form.TextField({
            name: 'name',
            fieldLabel: 'Name',
            width: 200,
            emptyText: '1 min, ' + maxNameLength + ' max',
            msgTarget: 'under',

            allowBlank: false,
            blankText: fieldRequiredText,
            maxLength: maxNameLength + 1,
            maxLengthText: 'Maximum length (' + maxNameLength + ' characters) exceeded.',
            regex: XDMoD.regex.noReservedCharacters,
            regexText: 'This field may not contain reserved characters. ($, ^, #, <, >, ", :, \\, /, !)',

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });//txtName

        var minEmailLength = XDMoD.constants.minEmailLength;
        var maxEmailLength = XDMoD.constants.maxEmailLength;
        var txtEmail = new Ext.form.TextField({
            name: 'email',
            fieldLabel: 'E-Mail',
            width: 200,
            emptyText: minEmailLength + ' min, ' + maxEmailLength + ' max',
            msgTarget: 'under',

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
        });//txtEmail

        var txtMessage = new Ext.form.TextArea({
            name: 'message',
            anchor: '100%',
            msgTarget: 'under',

            allowBlank: false,
            blankText: fieldRequiredText,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });//txtMessage

        var captchaField = null;

        if (CCR.xdmod.use_captcha && !CCR.xdmod.logged_in) {
            captchaField = new XDMoD.CaptchaField({
                style: 'margin-left: 60px; margin-bottom: 10px;'
            });//captchaField
        }

        var btnSubmit = new Ext.Button({
            text: 'Send Message',
            iconCls: 'contact_btn_send',
            handler: function () {
                XDMoD.TrackEvent(
                    self.trackEventCategory,
                    'Clicked Send Message button'
                );
                processContactForm();
            }
        });//btnSubmit

        var processContactForm = function () {
            var timestamp_secs = XDMoD.Tracking.timestamp / 1000;

            var params = {
                operation: 'contact',
                username: CCR.xdmod.ui.username,
                token: XDMoD.REST.token,
                timestamp: timestamp_secs,
                reason: self.contactReason
            };

            if (captchaField) {
                var captchaResponse = Ext.util.Format.trim(captchaField.getResponseField());

                if (captchaResponse.length === 0) {
                    CCR.xdmod.ui.userManagementMessage(
                        'Please answer the reCAPTCHA challenge.',
                        false
                    );
                    return;
                }

                params.recaptcha_response_field = captchaResponse;
            }

            signUpSection.getForm().submit({
                url: 'controllers/mailer.php',
                method: 'POST',
                params: params,

                success: function (form, action) {
                    successSection.setHeight(signUpSection.getHeight());
                    self.getLayout().setActiveItem(1);
                    btnSubmit.setDisabled(true);
                },

                failure: function (form, action) {
                    if (action.failureType === Ext.form.Action.CLIENT_INVALID) {
                        CCR.xdmod.ui.userManagementMessage(
                            "Please resolve any problems in the form and try sending your message again.",
                            false
                        );
                        return;
                    }

                    CCR.xdmod.ui.presentFailureResponse(action.response, {
                        title: self.title,
                        wrapperMessage: "There was a problem sending your message."
                    });
                }
            });
        };//processContactForm

        var signUpSectionItems = [];
        if (( typeof self.intro === 'object')) {
            signUpSectionItems.push(self.intro);
        }
        signUpSectionItems.push(
            new Ext.Panel({
                labelWidth: 45,
                frame: true,
                title: 'Contact Information',
                bodyStyle: 'padding:5px 5px 0',
                width: 430,
                layout: 'form',
                items: [
                    txtName,
                    txtEmail
                ]
            })
        );
        signUpSectionItems.push(
            new Ext.Panel({
                hideLabels: true,
                frame: true,
                title: 'Message',
                style: 'margin: 15px 0px',
                width: 430,
                layout: 'form',
                items: [
                    txtMessage
                ]
            })
        );
        if (captchaField) {
            signUpSectionItems.push(captchaField);
        }

        var signUpSection = new Ext.form.FormPanel({
            width: 300,
            autoHeight: true,
            baseCls: 'x-plain',
            cls: 'no-underline-invalid-fields-form',
            items: signUpSectionItems
        });//signUpSection

        var successSection = new Ext.Panel({
            baseCls: 'x-plain',
            html: self.successMessageHtml
        });

        self.on('afterrender', function () {
            if (CCR.xdmod.logged_in) {
                XDMoD.REST.connection.request({
                    url: '/users/current',
                    method: 'GET',

                    callback: function (options, success, response) {
                        // If success reported, attempt to extract user data.
                        var data;
                        if (success) {
                            data = CCR.safelyDecodeJSONResponse(response);
                            success = CCR.checkDecodedJSONResponseSuccess(data);
                        }

                        // If not successful, return quietly. This is done as a
                        // convenience, so there's no need to interrupt the
                        // user if it fails.
                        if (!success) {
                            return;
                        }

                        // Set the name and email fields to the user's info.
                        txtName.setValue(
                            data.results.first_name + ' ' +
                            data.results.last_name
                        );
                        txtEmail.setValue(data.results.email_address);
                    }
                });//XDMoD.REST.Call
            }
        });

        self.on('close', function () {
            XDMoD.TrackEvent(self.trackEventCategory, 'Closed Window');
        });

        // --------------------------------

        Ext.apply(this, {
            layout: 'card',
            activeItem: 0,
            bbar: {
                items: [
                    btnSubmit,
                    '->',
                    new Ext.Button({
                        text: 'Close',
                        iconCls: 'general_btn_close',
                        handler: function () { self.close(); }
                    })
                ]
            },
            items: [
                signUpSection,
                successSection
            ]
        });

        XDMoD.AbstractContactDialog.superclass.initComponent.call(this);

    }//initComponent

});//XDMoD.AbstractContactDialog

XDMoD.ContactDialog = Ext.extend(XDMoD.AbstractContactDialog, {
    title: 'Send Message',
    trackEventCategory: 'Contact Window',
    successMessageHtml: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' +
                  'Thank you for your message.<br /><br />A team member will be in touch with you shortly.</center>',
    contactReason: 'contact'
});//XDMoD.ContactDialog

XDMoD.SupportDialog = Ext.extend(XDMoD.AbstractContactDialog, {
    title: 'Submit Support Request',
    trackEventCategory: 'Support Window',
    successMessageHtml: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' +
                  'Thank you for your support request.<br /><br />A team member will be in touch with you shortly.</center>',
    contactReason: 'contact'
});//XDMoD.ContactDialog

XDMoD.WishlistDialog = Ext.extend(XDMoD.AbstractContactDialog, {
    title: 'Request Feature',
    iconCls: 'bulb_16',
    trackEventCategory: 'Wishlist Window',
    successMessageHtml: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' +
                  'Thank you for your feature request.<br /><br />A team member will be in touch with you shortly.</center>',
    contactReason: 'wishlist',
    intro: {
        xtype: 'panel',
        title: 'Roadmap',
        hideLabels: true,
        width: 430,
        style: 'margin-bottom: 15px;',
        layout: 'form',
        items: [
            {
                xtype: 'panel',
                layout: 'hbox',
                style: 'padding: 5px; background-color: #F1F1F1',
                border: false,
                items: [
                    {
                        xtype: 'panel',
                        border: false,
                        bodyStyle: 'background-color: #F1F1F1',
                        html: 'Before submitting a feature request please take a moment to see if it has already been added to our new Roadmap.',
                        flex: 1
                    },
                    {
                        xtype: 'button',
                        text: 'Roadmap',
                        iconCls: 'roadmap',
                        id: 'request-feature-roadmap',
                        handler: function() {
                            this.ownerCt.ownerCt.ownerCt.ownerCt.close();
                            Ext.History.add('#main_tab_panel:about_xdmod?Roadmap');
                        }
                    }
                ]
            }
        ]
    }
});//XDMoD.WishlistDialog
