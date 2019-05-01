class MyProfile {

    constructor() {
        let self = this;
        this.toolbarButton = '#global-toolbar-profile';
        this.container = '//div[@id="xdmod-profile-editor"]';

        this.general = `${this.container}//div[@id="xdmod-profile-general-settings"]`;
        this.userInformation = `${this.general}//div[contains(@class, "user_profile_section_general")]`;

        this.selectors = {
            tabs: {
                general: 'General',
                role_delegation: 'Role Delegation'
            },
            general: {
                user_information: {
                    top_role: function () {
                        return self.generalUserInformation('profile_editor_most_privileged_role');
                    },
                    first_name: function () {
                        return self.generalUserInformation('first_name');
                    },
                    last_name: function () {
                        return self.generalUserInformation('last_name');
                    },
                    email_address: function () {
                        return self.generalUserInformation('email_address');
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
        };
    }

    /**
     * Retrieve an XPath for a tab that contains the parameter text within the
     * My Profile window. Values can be found within `this.names.tabs`.
     *
     * @param text {string} the text found within the tab to be returned.
     * @returns {string}
     */
    tab(text) {
        return `${this.container}//span[contains(@class, "x-tab-strip-text") and contains(text(),"${text}")]`;
    }

    /**
     * Retrieve an XPath for a control, identified by the name parameter, within
     * the 'User Information' section of the 'General' tab. `names` are provided
     * by `this.names.general.user_information`.
     *
     * @param name {string}
     * @returns {string}
     */
    generalUserInformation(name) {
        switch (name) {
            case 'profile_editor_most_privileged_role':
                return `${this.userInformation}//span[@id="${name}"]`;
            default:
                return `${this.userInformation}//input[@name="${name}"]`;
        }
    }

    /**
     * Retrieve an XPath for a button, identified by the name parameter, within
     * the 'My Profile' window. Values for name provided by `this.names.buttons`
     *
     * @param name {string}
     * @returns {string}
     */
    button(name) {
        return `${this.general}//button[contains(@class, "${name}")]`;
    }
}

module.exports = new MyProfile();
