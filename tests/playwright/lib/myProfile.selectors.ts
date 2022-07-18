const myProfileSelectors ={
    container: '//div[@id="xdmod-profile-editor"]',
    genSet : function () {
        return myProfileSelectors.container + '//div[@id="xdmod-profile-general-settings"]';
    },
    userInformation : function () {
        return myProfileSelectors.genSet() + '//div[contains(@class, "user_profile_section_general")]';
    },
    tabs: {
        general: 'General',
        role_delegation: 'Role Delegation'
    },
    general: {
        user_information: {
            top_role: function () {
                return generalUserInformation('profile_editor_most_privileged_role');
            },
            first_name: function () {
                return generalUserInformation('first_name');
            },
            last_name: function () {
                return generalUserInformation('last_name');
            },
            email_address: function () {
                return generalUserInformation('email_address');
            }
        },
        update_password: {
            update: 'user_profile_option_password_update',
            password: 'new_password',
            password_again: 'password_again'
        }
    },
    role_delegation: {
        staff_member: 'staff_member'
    },
    buttons: {
        update: 'user_profile_btn_update',
        close: 'general_btn_close'
    }
}

/**
 * Retrieve an XPath for a control, identified by the name parameter, within
 * the 'User Information' section of the 'General' tab. `names` are provided
 * by `this.names.general.user_information`.
 *
 * @param name {string}
 * @returns {string}
 */
function generalUserInformation(name) {
    switch (name) {
        case 'profile_editor_most_privileged_role':
            return myProfileSelectors.userInformation() + '//span[@id="' + name + '"]';
        default:
            return myProfileSelectors.userInformation() + '//input[@name="'+ name + '"]';
    }
}

export default myProfileSelectors;
