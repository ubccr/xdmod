/* eslint-env node, es6 */
let roles = require('./../../../../ci/testing.json').role;
let expected = global.testHelpers.artifacts.getArtifact('internalDashboard');

class InternalDashboard {

    constructor() {
        const self = this;
        this.selectors = {
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
                        return self.selectors.tabByText('Summary');
                    },
                    user_management: function () {
                        return self.selectors.tabByText('User Management');
                    }
                }
            },
            summary: {
                tabs: {
                    overview: function () {
                        return self.selectors.tabByText('Overview');
                    },
                    log_data: function () {
                        return self.selectors.tabByText('Log Data');
                    }
                }
            },
            user_management: {
                tabs: {
                    account_requests: function () {
                        return self.selectors.tabByText('XDMoD Account Requests');
                    },
                    existing_users: function () {
                        return self.selectors.tabByText('Existing Users');
                    },
                    user_stats: function () {
                        return self.selectors.tabByText('User Stats');
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
                        return `(
                          //div[contains(@class, "existing_user_grid")]//div[contains(@class, "x-grid3-body")]//table//td[
                            count(preceding-sibling::td) + 1 =
                            count(//div[contains(@class, "existing_user_grid")]//div[contains(@class, "x-grid3-header")]//table//td[.="${column_name}"]/preceding-sibling::td) + 1
                          ]
                        ) [
                          count(//div[contains(@class, "existing_user_grid")]//div[contains(@class, "x-grid3-body")]//table//td[
                            .="${username}" and
                            (
                              count(preceding-sibling::td) + 1 =
                              count(//div[contains(@class, "existing_user_grid")]//div[contains(@class, "x-grid3-header")]//table//td[.="Username"]/preceding-sibling::td) + 1
                            )
                          ]/preceding::div[contains(@class, "x-grid3-row")]) + 1
                        ]
                        `;
                    }
                }
            },
            create_manage_users: {
                window: '//div[contains(@class, "xdmod_admin_panel")]',
                tabs: {
                    new_user: function () {
                        return `${self.selectors.create_manage_users.window}//span[contains(@class, 'x-tab-strip-text') and contains(text(), "New User")]`;
                    },
                    current_users: function () {
                        return `${self.selectors.create_manage_users.window}//span[contains(@class, 'x-tab-strip-text') and contains(text(), "Current Users")]`;
                    }
                },
                buttons: {
                    close: function () {
                        return `${self.selectors.create_manage_users.window}//button[contains(@class, "general_btn_close")]`;
                    },
                    create_user: function () {
                        return `${self.selectors.create_manage_users.window}//button[contains(@class, "admin_panel_btn_create_user")]`;
                    }
                },
                current_users: {
                    container: '//div[@id="admin_panel_user_editor"]',
                    toolbar: {
                        container: function () {
                            return `${self.selectors.create_manage_users.current_users.container}//div[contains(@class, "x-toolbar")]`;
                        },
                        actions: {
                            button: function () {
                                return `${self.selectors.create_manage_users.current_users.toolbar.container()}//button[.="Actions"]`;
                            },
                            container: '//div[contains(@class, "existing_users_action_menu")]',
                            itemWithText: function (text) {
                                return `${self.selectors.create_manage_users.current_users.toolbar.actions.container}//span[.="${text}"]`;
                            }
                        }
                    },
                    dialogs: {
                        deleteUser: {
                            container: '//div[contains(@class, "delete_user") and contains(@class, "x-window")]',
                            button: function (text) {
                                return `${self.selectors.modalDiaglogByTitle('Delete User')}//button[.="${text}"]`;
                            }
                        }
                    },
                    button: function (text) {
                        return `//div[@id="admin_tab_existing_user"]//button[.="${text}"]`;
                    }
                },
                new_user: {
                    container: function () {
                        return `${self.selectors.create_manage_users.window}//div[@id="admin_tab_create_user"]`;
                    },
                    first_name: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_first_name")]`;
                    },
                    lastName: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_last_name")]`;
                    },
                    emailAddress: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_email_address")]`;
                    },
                    username: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_username")]`;
                    },
                    mapTo: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_map_to")]`;
                    },
                    mapToTrigger: function () {
                        return `${self.selectors.create_manage_users.new_user.mapTo()}/following-sibling::img[contains(@class, "x-form-trigger")]`;
                    },
                    institution: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_institution")]`;
                    },
                    institution_trigger: function () {
                        return `${self.selectors.create_manage_users.new_user.institution()}/following-sibling::img[contains(@class, "x-form-trigger")]`;
                    },
                    userType: function () {
                        return `${self.selectors.create_manage_users.new_user.container()}//input[contains(@class, "new_user_user_type")]`;
                    },
                    aclByName: function (name) {
                        return `${self.selectors.create_manage_users.new_user.container()}//div[contains(@class, "admin_panel_section_role_assignment_n")]//table[contains(@class, "x-grid3-row-table")]//td[div="${name}"]/following-sibling::td//div[contains(@class, "x-grid3-cell-inner")]/div`;
                    },
                    dialog: function (text) {
                        return `//div[contains(@class, "x-window") and contains(@class, "x-notification")]//b[contains(@class, "user_management_message") and contains(text(), "${text}")]`;
                    }
                }
            },
            tabByText: function (name) {
                return `//div[@id="dashboard-tabpanel"]//span[contains(@class, "x-tab-strip-text") and contains(text(), "${name}")]`;
            },
            combo: {
                container: '//div[contains(@class, "x-combo-list")]',
                itemByText: function (text) {
                    return `${self.selectors.combo.container}//div[contains(@class, "x-combo-list-item") and contains(text(), "${text}")]`;
                }
            },
            createSuccessNotification: function (username) {
                return self.selectors.modalDiaglogByTitle('User Management') +
                    '//b[text()[1][contains(., "User")] and text()[2][contains(., "created successfully")]]/b[text() = "' +
                    username + '"]/ancestor::node()[1]';
            },
            deleteSuccessNotification: function (username) {
                return self.selectors.modalDiaglogByTitle('User Management') +
                    '//b[text()[1][contains(., "User")] and text()[2][contains(., "deleted from the portal")]]/b[text() = "' +
                    username + '"]/ancestor::node()[1]';
            },
            buttonInModalDialog: function (title, button) {
                return `${self.selectors.modalDiaglogByTitle(title)}//button[contains(text(), "${button}")]`;
            },
            modalDiaglogByTitle: function (title) {
                return '//div[contains(@class, "x-window")]//div[contains(@class, "x-window-header")]//span[contains(@class, "x-window-header-text") and text()="' + title + '"]/ancestor::node()[5]';
            }

        };
    }

    /**
     * Log a user w/ the desired role into the Internal Dashboard.
     *
     * @param desiredRole {string} the role / user that should be logged into
     * the Internal Dashboard.
     */
    login(desiredRole) {
        let username;
        let password;
        let displayName;
        let role;
        switch (desiredRole) {
            case 'cd':
            case 'centerdirector':
                role = 'cd';
                break;
            case 'cs':
            case 'centerstaff':
                role = 'cs';
                break;
            case 'pi':
            case 'principalinvestigator':
                role = 'pi';
                break;
            case 'usr':
            case 'user':
                role = 'usr';
                break;
            case 'mgr':
            case 'admin':
                role = 'mgr';
                break;
            default:
                role = desiredRole;
                break;
        }
        username = roles[role].username;
        password = roles[role].password;
        displayName = roles[role].givenname + ' ' + roles[role].surname;
        this.loginDirect(username, password, displayName);
    }

    /**
     * Log a user identified by the provided username, password into the
     * Internal Dashboard.
     *
     * @param username    {string} the username to use during the login process.
     * @param password    {string} the password to use during the login process.
     * @param displayName {string} the name that should be displayed when the
     * user is logged in.
     */
    loginDirect(username, password, displayName) {
        const self = this;
        describe('General', function () {
            it('Verify Title', function () {
                browser.url('/internal_dashboard');
                let actual = browser.getTitle();
                expect(actual).to.equal(expected.title);
            });
        });
        describe('Login', function () {
            it('Should Login', function () {
                browser.waitForVisible(self.selectors.login.username);
                browser.waitForVisible(self.selectors.login.password);
                browser.waitForVisible(self.selectors.login.submit);

                browser.setValue(self.selectors.login.username, username);
                browser.setValue(self.selectors.login.password, password);

                browser.waitAndClick(self.selectors.login.submit);
                browser.waitForVisible(self.selectors.logoutLink);
            });
            it('Display the logged in users name', function () {
                browser.waitForVisible(self.selectors.loggedInDisplayName);

                let actual = browser.getText(self.selectors.loggedInDisplayName);
                expect(actual).to.equal(displayName);
            });
        });
    }

    /**
     * Log out of XDMoD's internal dashboard
     */
    logout() {
        const self = this;
        describe('Logout', function () {
            it('Click the logout link', function () {
                browser.waitForVisible(self.selectors.logoutLink);

                browser.click(self.selectors.logoutLink);
            });
            it('Display the login page again', function () {
                browser.waitForVisible(self.selectors.login.username);
                browser.waitForVisible(self.selectors.login.password);
                browser.waitForVisible(self.selectors.login.submit);
            });
        });
    }
}

module.exports = new InternalDashboard();
