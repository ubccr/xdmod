XDMoD.ProfileGeneralSettings = Ext.extend(Ext.form.FormPanel, {

	autoHeight: true,
	border: false,
	frame: true,
	resizable: false,
	title: 'General',
	cls: 'no-underline-invalid-fields-form',

	// Dictates whether closing the profile editor logs out the user automatically
	perform_logout_on_close: false,

	init: function() {

		XDMoD.REST.connection.request({
			url: '/users/current',
			method: 'GET',
			callback: this.cbProfile
		});

	},

	initComponent: function() {

			var self = this;

			// ------------------------------------------------

			var fieldRequiredText = 'This field is required.';
			var reservedCharactersNotAllowedText = 'This field may not contain reserved characters. ($, ^, #, <, >, ", :, \\, /, !)';

			var maxFirstNameLength = XDMoD.constants.maxFirstNameLength;
			var user_profile_firstname = new Ext.form.TextField({
				name: 'first_name',
				fieldLabel: 'First Name',
				emptyText: '1 min, ' + maxFirstNameLength + ' max',
				msgTarget: 'under',

				allowBlank: false,
				blankText: fieldRequiredText,
				maxLength: maxFirstNameLength,
				maxLengthText: 'Maximum length (' + maxFirstNameLength + ' characters) exceeded.',
				regex: XDMoD.regex.noReservedCharacters,
				regexText: reservedCharactersNotAllowedText,

				listeners: {
					blur: XDMoD.utils.trimOnBlur,
					invalid: XDMoD.utils.syncWindowShadow,
					valid: XDMoD.utils.syncWindowShadow
				}
			});

			var maxLastNameLength = XDMoD.constants.maxLastNameLength;
			var user_profile_lastname = new Ext.form.TextField({
				name: 'last_name',
				fieldLabel: 'Last Name',
				emptyText: '1 min, ' + maxLastNameLength + ' max',
				msgTarget: 'under',

				allowBlank: false,
				blankText: fieldRequiredText,
				maxLength: maxLastNameLength,
				maxLengthText: 'Maximum length (' + maxLastNameLength + ' characters) exceeded.',
				regex: XDMoD.regex.noReservedCharacters,
				regexText: reservedCharactersNotAllowedText,

				listeners: {
					blur: XDMoD.utils.trimOnBlur,
					invalid: XDMoD.utils.syncWindowShadow,
					valid: XDMoD.utils.syncWindowShadow
				}
			});

			var minEmailLength = XDMoD.constants.minEmailLength;
			var maxEmailLength = XDMoD.constants.maxEmailLength;
			var removeFieldHighlight = function(thisField) {
				thisField.removeClass('user_profile_highlight_entry');
			};
			var user_profile_email_addr = new Ext.form.TextField({
				name: 'email_address',
				fieldLabel: 'E-Mail Address',
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
					focus: removeFieldHighlight,
					invalid: function(thisField, msg) {
						removeFieldHighlight(thisField);
						XDMoD.utils.syncWindowShadow(thisField);
					},
					valid: XDMoD.utils.syncWindowShadow
				}
			});

			var passwordFieldWidth = 120;
			var minPasswordLength = XDMoD.constants.minPasswordLength;
			var maxPasswordLength = XDMoD.constants.maxPasswordLength;
			var user_profile_new_pass = new Ext.form.TextField({
				name: 'password',
				fieldLabel: 'Password',
				width: passwordFieldWidth,
				inputType: 'password',
				disabled: true,
				cls: 'user_profile_password_field',

				allowBlank: false,
				blankText: fieldRequiredText,
				minLength: minPasswordLength,
				minLengthText: 'Minimum length (' + minPasswordLength + ' characters) not met.',
				maxLength: maxPasswordLength,
				maxLengthText: 'Maximum length (' + maxPasswordLength + ' characters) exceeded.',

				listeners: {
					invalid: XDMoD.utils.syncWindowShadow,
					valid: XDMoD.utils.syncWindowShadow
				}
			});

			var user_profile_new_pass_again = new Ext.form.TextField({
				fieldLabel: 'Password Again',
				width: passwordFieldWidth,
				inputType: 'password',
				disabled: true,
				cls: 'user_profile_password_field',
				msgTarget: 'under',
				submitValue: false,

				validator: function(value) {
					if (user_profile_new_pass.getValue() === user_profile_new_pass_again.getValue()) {
						return true;
					}

					return "This password does not match the password above.";
				},

				listeners: {
					invalid: XDMoD.utils.syncWindowShadow,
					valid: XDMoD.utils.syncWindowShadow
				}
			});

			// ------------------------------------------------

			var active_layout_index = CCR.xdmod.ui.userIsFederated ? XDMoD.ProfileEditorConstants.FEDERATED_USER : XDMoD.ProfileEditorConstants.PASSWORD;


			// ------------------------------------------------

			var switchToSection = function(id) {

				rpanel = sectionBottom.getLayout();

				if (rpanel != 'card') {

					rpanel.setActiveItem(id);

				}

			}; //switchToSection

			// ------------------------------------------------

			this.cbProfile = function(options, success, response) {
				// If success reported, attempt to extract user data.
				var data;
				if (success) {
					data = CCR.safelyDecodeJSONResponse(response);
					success = CCR.checkDecodedJSONResponseSuccess(data);
				}

				if (success) {

					user_profile_firstname.setValue(data.results.first_name);
					user_profile_lastname.setValue(data.results.last_name);
					user_profile_email_addr.setValue(data.results.email_address);

					// ================================================

					//active_layout_index = XDMoD.ProfileEditorConstants.PASSWORD;
					if(data.results.is_federated_user && data.results.email_address.length === 0){
						XDMoD.Profile.logoutOnClose = true;
					}
    if (data.results.is_federated_user === true) {
        if (data.results.first_time_login) {
            // If the user is logging in for the first time, prompt them to validate their email address
            active_layout_index = XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE;
        	user_profile_email_addr.addClass('user_profile_highlight_entry');
        }
    }

    // ================================================
    // eslint-disable-next-line no-use-before-define
    lblRole.on(
      'afterrender',
      function () {
          // eslint-disable-next-line no-undef
          document.getElementById('profile_editor_most_privileged_role').innerHTML = data.results.most_privileged_role;
      }
    );

					self.parentWindow.show();

				}
				else {
					Ext.MessageBox.alert('My Profile', 'There was a problem retrieving your profile information.');
				}

			}; //cbProfile

			// ------------------------------------------------

			var updateProfile = function() {
				// This prevents the radio buttons in the password panel from being
				// submitted. Setting submitValue to false in the buttons breaks them
				// in ExtJS 3.4.0. A call to re-enable them is in the failure handler.
				optPasswordUpdate.setDisabled(true);

				self.getForm().submit({
					url: XDMoD.REST.prependPathBase('/users/current'),
					method: 'PATCH',

					success: function(form, action) {
						XDMoD.Profile.logoutOnClose = false;

						CCR.xdmod.ui.generalMessage('My Profile', action.result.message, true);

						var f_name = user_profile_firstname.getValue();
						var l_name = user_profile_lastname.getValue();

						document.getElementById('welcome_message').innerHTML = Ext.util.Format.htmlEncode(f_name) + ' ' + Ext.util.Format.htmlEncode(l_name);

						self.parentWindow.close();
					},

					failure: function(form, action) {
						optPasswordUpdate.setDisabled(false);

						if (action.failureType === Ext.form.Action.CLIENT_INVALID) {
							CCR.xdmod.ui.userManagementMessage(
								"Please resolve any problems in the form and try updating again.",
								false
							);
							return;
						}

						CCR.xdmod.ui.presentFailureResponse(action.response, {
							title: "My Profile",
							wrapperMessage: "There was a problem updating your information."
						});
					}
				});
			}; //updateProfile

			// ------------------------------------------------

			var optPasswordUpdate = new Ext.form.RadioGroup({

				fieldLabel: 'Update',
				cls: 'user_profile_option_password_update',
				columns: 2,
				width: 180,

				items: [{
					boxLabel: 'Keep Existing',
					name: 'group_view',
					inputValue: 'keep',
					checked: true
				}, {
					boxLabel: 'Update',
					name: 'group_view',
					inputValue: 'update',
					checked: false
				}],

				listeners: {

					'change': function(rg, ch) {

							var userKeepingPassword = rg.getValue().getGroupValue() === 'keep';
							user_profile_new_pass.setDisabled(userKeepingPassword);
							user_profile_new_pass_again.setDisabled(userKeepingPassword);

							if (userKeepingPassword) {
								user_profile_new_pass.setValue('');
								user_profile_new_pass_again.setValue('');
							}

						} //change

				} //listeners

			}); //optPasswordUpdate

			// ------------------------------------------------

			var lblRole = new Ext.form.Label({
				html: '<div style="width: 300px; font-size: 12px; padding-top: 5px">Top Role: <b style="margin-left: 45px"><span id="profile_editor_most_privileged_role"></span></b><br /></div>'
			});

			// ------------------------------------------------

			var sectionGeneral = new Ext.Panel({

				labelWidth: 95,
				frame: true,
				title: 'User Information',
				bodyStyle: 'padding:5px 5px 0',
				width: 350,
				defaults: {
					width: 200
				},
				cls: 'user_profile_section_general',
				defaultType: 'textfield',
				layout: 'form',

				items: [

					user_profile_firstname,
					user_profile_lastname,
					user_profile_email_addr,

					lblRole
					//cmbFieldOfScience

				]

			}); //sectionGeneral

			// ------------------------------------------------

			var sectionPassword = new Ext.Panel({
				labelWidth: 95,
				frame: true,
				title: 'Update Password',
				bodyStyle: 'padding:5px 5px 0',
				width: 350,
				defaults: {
					width: 200
				},
				cls: 'user_profile_section_password',
				defaultType: 'textfield',
				layout: 'form',
				items: [
					optPasswordUpdate,
					user_profile_new_pass,
					{
						xtype: 'tbtext',
						cls: 'user_profile_entry_password_requirements',
						text: minPasswordLength + ' min, ' + maxPasswordLength + ' max'
					},
					user_profile_new_pass_again
				]
			}); //sectionPassword

			var sectionFederatedUser = new Ext.Panel({
				labelWidth: 95,
				frame: false,
				bodyStyle: 'padding:0px 5px',
				width: 350,
				layout: 'form',
				items: [{
					xtype: 'tbtext',
					text: ""
				}]
			});

			var sectionFederatedEmail = new Ext.Panel({
				labelWidth: 95,
				frame: false,
				bodyStyle: 'padding:0px 5px',
				width: 350,
				layout: 'form',
				items: [{
					xtype: 'tbtext',
					text: 'Please ensure the email listed above is accurate. Your e-mail address is required in order to use certain features of XDMoD as well as receive important messages from the XDMoD team. Once you have validated your email, click "Update" to confirm.'
				}]
			});
			// ------------------------------------------------

			var sectionBottom = new Ext.Panel({

				labelWidth: 95,
				bodyStyle: 'padding:0px 0px',
				cls: 'user_profile_section_password',
				defaults: {
					autoHeight: true
				},

				layout: 'card',
				activeItem: 0,

				items: [
					sectionPassword,
					sectionFederatedEmail,
					sectionFederatedUser
				]

			}); //sectionBottom

			sectionBottom.on('afterrender', function() {

				switchToSection(active_layout_index);

			});

			// ------------------------------------------------


			var btnUpdate = new Ext.Button({

				iconCls: 'user_profile_btn_update_icon',
				cls: 'user_profile_btn_update',
				text: 'Update',
				handler: function() {
						updateProfile();
					} //handler

			}); //btnUpdate

			// ------------------------------------------------

			Ext.apply(this, {

				items: [
					sectionGeneral,
					sectionBottom
				],

				bbar: {

					items: [
						btnUpdate,
						'->',
						self.parentWindow.getCloseButton()
					]

				}

			});

			XDMoD.ProfileGeneralSettings.superclass.initComponent.call(this);

		} //initComponent

}); //XDMoD.ProfileGeneralSettings
