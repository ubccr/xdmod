const selectors = {
    login: {
        username: '//input[@id="field_username"]',
        password: '//input[@id="field_password"]',
        submit: '//input[@type="submit" and @value="Log In"]'
    },
    logoutLink: '//a[@id="header-logout"]',
    loggedInDisplayName: '//div[@id="dashboard-header"]//b',
    header: {
        tabs: {
            summary: function () {
                return selectors.tabByText('Summary');
            },
            user_management: function () {
                return selectors.tabByText('User Management');
            }
        }
    },
    summary: {
        tabs: {
            overview: function () {
                return selectors.tabByText('Overview');
            },
            log_data: function () {
                return selectors.tabByText('Log Data');
            }
        }
    },
    user_management: {
        tabs: {
            account_requests: function () {
                return selectors.tabByText('XDMoD Account Requests');
            },
            existing_users: function () {
                return selectors.tabByText('Existing Users');
            },
            user_stats: function () {
                return selectors.tabByText('User Stats');
            }
        }
    },
    account_requests: {
        toolbar: {
            create_manage_users: '#acct_requests_create_manage_users'
        }
    },
    existing_users: {
        toolbar: {
            create_manage_users: '#existing_users_create_manage_users'
        },
        user_table: '',
        table: {
            container: '//div[contains(@class, "existing_user_grid")]',
            /**
             * Retrieve a column via `column_name`, for a user via
             * `username` which corresponds to a value located in the
             * `Username` column.
             *
             * @param username    {string}
             * @param column_name {string}
             * @returns {string}
             */
            col_for_user: function (username, column_name) {
                return `(//div[contains(@class, "existing_user_grid")]//div[contains(@class,"x-grid3-body")]//table//td[count(preceding-sibling::td) + 1 = count(//div[contains(@class,"existing_user_grid")]//div[contains(@class,"x-grid3-header")]//table//td[.="${column_name}"]/preceding-sibling::td) + 1 ])[count(//div[contains(@class,"existing_user_grid")]//div[contains(@class,"x-grid3-body")]//table//td[.="${username}"]/preceding::div[contains(@class,"x-grid3-row")]) + 1]`
            }
        }
    },
    create_manage_users: {
        loading_mask: '.admin_panel_editor_mask',
        window: '//div[contains(@class, "xdmod_admin_panel")]',
        tabs: {
            new_user: function () {
                return `${selectors.create_manage_users.window}//span[contains(@class, 'x-tab-strip-text') and contains(text(), "New User")]`;
            },
            current_users: function () {
                return `${selectors.create_manage_users.window}//span[contains(@class, 'x-tab-strip-text') and contains(text(), "Current Users")]`;
            }
        },
        buttons: {
            close: function () {
                return `${selectors.create_manage_users.window}//button[contains(@class, "general_btn_close")]`;
            },
            create_user: function () {
                return `${selectors.create_manage_users.window}//button[contains(@class, "admin_panel_btn_create_user")]`;
            },
            save_changes: function () {
                return `${selectors.create_manage_users.window}//button[contains(text(), "Save Changes")]`;
            }
        },
        current_users: {
            container: '//div[@id="admin_tab_existing_user"]',
            dialogs: {
                deleteUser: {
                    container: '//div[contains(@class, "delete_user") and contains(@class, "x-window")]',
                    button: function (text) {
                        return `${selectors.modal.containerByTitle('Delete User')}//button[.="${text}"]`;
                    }
                }
            },
            settings: {
                container: '//div[@id="admin_panel_user_editor"]',
                toolbar: {
                    container: function () {
                        return `${selectors.create_manage_users.current_users.settings.container}//div[contains(@class, "x-toolbar")]`;
                    },
                    actions: {
                        button: function () {
                            return `${selectors.create_manage_users.current_users.settings.toolbar.container()}//button[.="Actions"]`;
                        },
                        container: '//div[contains(@class, "existing_users_action_menu")]',
                        itemWithText: function (text) {
                            return `${selectors.create_manage_users.current_users.settings.toolbar.actions.container}//span[.="${text}"]`;
                        }
                    },
                    details_header: function(user) {
                        return `${selectors.create_manage_users.current_users.settings.container}//span[contains(@class, "x-panel-header-text") and contains(text(), "${user}")]`;
                    }
                },
                inputByLabel: function (labelText, inputType) {
                    return `${selectors.create_manage_users.current_users.settings.container}//label[contains(text(), "${labelText}")]/parent::*//input[@type="${inputType}"]`;
                },
                dropDownTriggerByLabel: function (labelText) {
                    return `${selectors.create_manage_users.current_users.settings.container}//label[contains(text(), "${labelText}")]/parent::*//img[contains(@class, "x-form-trigger")]`;
                },
                noUserSelectedModal: function () {
                    return `${selectors.create_manage_users.current_users.settings.container}//div[contains(@class, 'ext-el-mask-msg')]//div[contains(text(), 'Select A User From The List To The Left')]`;
                }
            },
            user_list: {
                container: function () {
                    return `${selectors.create_manage_users.current_users.container}//div[contains(@class, 'admin_panel_existing_user_list')]`;
                },
                toolbar: {
                    container: function () {
                        return `${selectors.create_manage_users.current_users.user_list.container()}//div[contains(@class, 'x-panel-tbar')]`;
                    },
                    buttonByLabel: function (labelText, buttonText) {
                        return `${selectors.create_manage_users.current_users.user_list.toolbar.container()}//div[contains(text(), "${labelText}")]/following::*//button[contains(text(), "${buttonText}")]`;
                    }
                },
                dropDownItemByText: function (text) {
                    return `//div[contains(@class, 'x-menu')]//ul[contains(@class, 'x-menu-list')]//span[contains(text(), '${text}')]/parent::*[contains(@class, 'x-menu-item')]`;
                },
                /**
                 * Retrieve a column via `column_name`, for a user via
                 * `username` which corresponds to a value located in the
                 * `Username` column.
                 *
                 * @param username    {string}
                 * @returns {string}
                 */
                col_for_user: function (username) {
                    return `${selectors.create_manage_users.current_users.user_list.container()}//div[contains(@class, "x-grid3-body")]//table//td[.="${username}"]`;
                }
            },
            button: function (text) {
                return `//div[@id="admin_tab_existing_user"]//button[.="${text}"]`;
            }
        },
        new_user: {
            container: function () {
                return `${selectors.create_manage_users.window}//div[@id="admin_tab_create_user"]`;
            },
            first_name: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_first_name")]`;
            },
            lastName: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_last_name")]`;
            },
            emailAddress: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_email_address")]`;
            },
            username: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_username")]`;
            },
            mapTo: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_map_to")]`;
            },
            mapToTrigger: function () {
                return `${selectors.create_manage_users.new_user.mapTo()}/following-sibling::img[contains(@class, "x-form-trigger")]`;
            },
            institution: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_institution")]`;
            },
            institution_trigger: function () {
                return `${selectors.create_manage_users.new_user.institution()}/following-sibling::img[contains(@class, "x-form-trigger")]`;
            },
            userType: function () {
                return `${selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_user_type")]`;
            },
            userTypeTrigger: function() {
                return `${selectors.create_manage_users.new_user.userType()}/following-sibling::img[contains(@class, "x-form-trigger")]`
            },
            aclByName: function (name) {
                return `${selectors.create_manage_users.new_user.container()}//div[contains(@class, "admin_panel_section_role_assignment_n")]//table[contains(@class, "x-grid3-row-table")]//td[div="${name}"]/following-sibling::td//div[contains(@class, "x-grid3-cell-inner")]/div`;
            },
            dialog: function (text) {
                return `//div[contains(@class, "x-window") and contains(@class, "x-notification")]//b[contains(@class, "user_management_message") and contains(text(), "${text}")]`;
            }
        },
        bottom_bar: {
            container: function () {
                return `${selectors.create_manage_users.window}//div[contains(@class, "x-panel-bbar")]`;
            },
            messageByText: function (text) {
                return `${selectors.create_manage_users.bottom_bar.container()}//span[contains(text(), "${text}")]`;
            }
        }
    },
    tabByText: function (name) {
        return `//div[@id="dashboard-tabpanel"]//span[contains(@class, "x-tab-strip-text") and contains(text(), "${name}")]`;
    },
    combo: {
        container: '//div[contains(@class, "x-combo-list") and contains(@style, "visibility: visible")]',
        itemByText: function (text) {
            return `${selectors.combo.container}//div[contains(@class, "x-combo-list-item") and contains(text(), "${text}")]`;
        }
    },
    createSuccessNotification: function (username) {
        return selectors.modal.containerByTitle('User Management') +
            '//b[text()[1][contains(., "User")] and text()[2][contains(., "created successfully")]]/b[text() = "' +
            username + '"]/ancestor::node()[1]';
    },
    deleteSuccessNotification: function (username) {
        return selectors.modal.containerByTitle('User Management') +
            '//b[text()[1][contains(., "User")] and text()[2][contains(., "deleted from the portal")]]/b[text() = "' +
            username + '"]/ancestor::node()[1]';
    },
    updateSuccessNotification: function (username) {
        return selectors.modal.containerByTitle('User Management') +
            '//b[text()[1][contains(., "User")] and text()[2][contains(., "updated successfully")]]/b[text() = "' +
            username + '"]/ancestor::node()[1]';
    },
    modal: {
        containerByTitle: function (title) {
            return `//div[contains(@class, "x-window")]//div[contains(@class, "x-window-header")]//span[contains(@class, "x-window-header-text") and text()="${title}"]/ancestor::node()[5]`;
        },
        buttonByText: function (modalTitle, buttonText) {
            return `${selectors.modal.containerByTitle(modalTitle)}//button[contains(text(), "${buttonText}")]`;
        },
        tools: {
            close: function (modalTitle) {
                return `${selectors.modal.containerByTitle(modalTitle)}//div[contains(@class, "x-tool-close")]`;
            }
        }
    }
};
export default selectors;
