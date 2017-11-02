XDMoD.SignUpDialog = Ext.extend(Ext.Window, {
    width: 591,
    modal: true,
    resizable: false,
    title: "Sign Up Today",
    iconCls: 'signup_16',
    bodyStyle: 'padding:15px 13px 0',

    initComponent: function () {
        var fieldRequiredText = 'This field is required.';
        var noReservedCharactersText = 'This field may not contain reserved characters. ($, ^, #, <, >, ", :, \\, /, !)';

        var maxFirstNameLength = XDMoD.constants.maxFirstNameLength;
        var txtFirstName = new Ext.form.TextField({
            name: 'first_name',
            fieldLabel: 'First Name',
            emptyText: '1 min, ' + maxFirstNameLength + ' max',
            msgTarget: 'under',

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
        });//txtFirstName

        var maxLastNameLength = XDMoD.constants.maxLastNameLength;
        var txtLastName = new Ext.form.TextField({
            name: 'last_name',
            fieldLabel: 'Last Name',
            emptyText: '1 min, ' + maxLastNameLength + ' max',
            msgTarget: 'under',

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
        });//txtLastName

        var maxUserPositionLength = XDMoD.constants.maxUserPositionLength;
        var txtPosition = new Ext.form.TextField({
            name: 'title',
            fieldLabel: 'Position',
            emptyText: '1 min, ' + maxUserPositionLength + ' max',
            msgTarget: 'under',

            allowBlank: false,
            blankText: fieldRequiredText,
            maxLength: maxUserPositionLength,
            maxLengthText: 'Maximum length (' + maxUserPositionLength + ' characters) exceeded.',
            regex: XDMoD.regex.noReservedCharacters,
            regexText: noReservedCharactersText,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });//txtPosition

        var maxUserOrganizationLength = XDMoD.constants.maxUserOrganizationLength;
        var txtOrganization = new Ext.form.TextField({
            name: 'organization',
            fieldLabel: 'Organization',
            emptyText: '1 min, ' + maxUserOrganizationLength + ' max',
            msgTarget: 'under',

            allowBlank: false,
            blankText: fieldRequiredText,
            maxLength: maxUserOrganizationLength,
            maxLengthText: 'Maximum length (' + maxUserOrganizationLength + ' characters) exceeded.',
            regex: XDMoD.regex.noReservedCharacters,
            regexText: noReservedCharactersText,

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });//txtOrganization

        var minEmailLength = XDMoD.constants.minEmailLength;
        var maxEmailLength = XDMoD.constants.maxEmailLength;
        var txtEmail = new Ext.form.TextField({
            name: 'email',
            fieldLabel: 'E-Mail',
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

        var txtAdditionalInformation = new Ext.form.TextArea({
            name: 'additional_information',
            anchor: '100%',
            emptyText: 'Please enter your reason(s) for requesting an account.',
            msgTarget: 'under',

            allowBlank: false,
            blankText: 'You must specify why you are requesting an account.',

            listeners: {
                blur: XDMoD.utils.trimOnBlur,
                invalid: XDMoD.utils.syncWindowShadow,
                valid: XDMoD.utils.syncWindowShadow
            }
        });//txtAdditionalInformation

        var captchaField = null;

        if (CCR.xdmod.use_captcha) {
            captchaField = new XDMoD.CaptchaField({
                style: 'margin-left: 110px'
            });//captchaField
        }

        var btnSubmit = new Ext.Button({
            text: 'Submit My Information',
            iconCls: 'contact_btn_send',
            handler: function () {
                XDMoD.TrackEvent('Sign Up Window', 'Clicked on Submit My Information button');
                processSignUp();
            }
        });//btnSubmit

        var processSignUp = function () {
            var params = {
                operation: 'sign_up',
                field_of_science: 'not available'
            };

            if (captchaField) {
                var captchaResponse = Ext.util.Format.trim(captchaField.getResponseField());
                if (captchaResponse.length === 0) {
                    CCR.xdmod.ui.userManagementMessage("Please answer the reCAPTCHA challenge.", false);
                    return;
                }

                params.recaptcha_challenge_field = captchaField.getChallengeField();
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
                            "Please resolve any problems in the form and try sending your request again.", 
                            false
                        );
                        return;
                    }

                    CCR.xdmod.ui.presentFailureResponse(action.response, {
                        title: self.title,
                        wrapperMessage: "There was a problem sending your request."
                    });
                }
            });
        };//processSignUp

        var signUpItems = [
            new Ext.Panel({
                labelWidth: 78,
                frame: true,
                title: 'Basic Information',
                style: 'margin-top:15px',
                bodyStyle: 'padding:5px 5px 0',
                width: 550,
                layout: 'form',
                items: [
                    {
                        layout: 'column',
                        items: [
                            //column 1
                            {
                                columnWidth: 0.5,
                                layout: 'form',
                                items: [
                                    txtFirstName,
                                    txtLastName,
                                    txtEmail
                                ]
                            },
                            //column 2
                            {
                                columnWidth: 0.5,
                                layout: 'form',
                                items: [
                                    txtOrganization,
                                    txtPosition
                                ]
                            }
                        ]//items
                    }//column_layout
                ]//items
            }),

            new Ext.Panel({
                hideLabels: true,
                frame: true,
                title: 'Affiliation with ' + CCR.xdmod.org_name,
                style: 'margin-top:15px',
                width: 550,
                layout: 'form',
                items: [
                    txtAdditionalInformation
                ]
            })
        ];
        if (captchaField) {
            signUpItems.push(captchaField);
        }

        // Don't display XSEDE text in Open XDMoD.
        if (CCR.xdmod.features.xsede) {
            signUpItems.unshift(
                new Ext.Panel({
                    frame: true,
                    title: 'Account Requirements',
                    width: 550,
                    html: '<p>Users affiliated with XSEDE do not need to request an XDMoD account; they should use their Globus credentials with a linked XSEDE account*. <br><br>Local accounts are created for users affiliated with XSEDE who do not have an XSEDE User Portal account <b>only with the express permission of XSEDE or NSF management</b>.</p><br><p>* Requires an active XSEDE User Portal account. See <a href="https://portal.xsede.org/software/globus" target="_ ">Add XSEDE to your Globus Profile</a> for more information.</p>'
                })
            );
        }

        var signUpSection = new Ext.form.FormPanel({
            width: 310,
            autoHeight: true,
            baseCls: 'x-plain',
            cls: 'no-underline-invalid-fields-form',

            items: signUpItems
        });//signUpSection

        var successSection = new Ext.Panel({
            baseCls: 'x-plain',
            html: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' +
                  'Thank you for signing up.<br /><br />A team member will be in touch with you shortly.</center>'
        });//successSection

        var self = this;

        self.on('close', function () {
            XDMoD.TrackEvent('Sign Up Window', 'Closed Window');
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

        XDMoD.SignUpDialog.superclass.initComponent.call(this);

    }//initComponent

});//XDMoD.SignUpDialog

