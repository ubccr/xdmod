const selectors ={
    container: '//div[@id="xdmod-profile-editor"]',
    genSet : function () {
        return selectors.container + '//div[@id="xdmod-profile-general-settings"]';
    },
    userInformation : function () {
        return selectors.genSet() + '//div[contains(@class, "user_profile_section_general")]';
    },
    tabs: {
        byText: function(text) {
            return selectors.container + '//span[contains(@class, "x-tab-strip-text") and contains(text(),"'+ text + '")]';
        },
        general: 'General',
        role_delegation: 'Role Delegation'
    },
    general: {
        generalName: function(name) {
            return selectors.genSet() + '//button[contains(@class, "' + name + '")]';
        },
        user_information: {
            top_role: function () {
                return '//div[@id="user_profile_most_privileged_role"]';
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
        close: 'general_btn_close',
        toolbar: '#global-toolbar-profile'
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
    if (name == 'profile_editor_most_privileged_role') {
            return selectors.userInformation() + '//span[@id="' + name + '"]';
    }
    return selectors.userInformation() + '//input[@name="'+ name + '"]';
}

export default selectors;
