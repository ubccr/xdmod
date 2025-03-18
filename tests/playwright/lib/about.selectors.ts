const selectors = {
    tab: '//ul[contains(@class, "x-tab-strip x-tab-strip-top")]',
    container: '//div[@id="about_xdmod"]',
    center: '//div[@id="about_xdmod"]//div[contains(@class, "x-panel-body") and contains(@class, "x-border-layout-ct")]/div[contains(@class,"x-panel") and contains(@class,"x-panel-reset") and contains(@class,"x-border-panel")]',
    last_tab: '//ul/li[@id="main_tab_panel__about_xdmod"]',
    myProfile: '//button[contains(@class, "x-btn-text user_profile_16")]',
    role: '//div[@id="user_profile_most_privileged_role"]',
    navEntryPath: function (name) {
        return '//div[@class="x-tree-root-node"]//div[contains(@class,"x-tree-node-el")]//span[contains(text(),"' + String(name) + '")]';
    },
    roadMapFrame: '//iframe[@id="about_roadmap"]',
    trelloBoard: '//div[contains(@class,"full-bleed-trello-board")]',
    expiredMessageBox: '.x-window',
    continueLogoutButton: '.x-window .x-btn',
    logoutLink: '//a[@id="logout_link"]',
    signInLink: '//a[@id="sign_in_link"]'
};

export default selectors;
