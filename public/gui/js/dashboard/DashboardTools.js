/**
 * Internal operations dashboard tools.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.DashboardTools');

/**
 * Change the currently active panel.
 *
 * Given a list of panel ids (all but the last must be tab panels),
 * changes the active panel of each tab panel.
 *
 * @param {Array} linkPath An array of tab panel ids.
 * @param {Ext.TabPanel} tabPanel The first tab panel to change
 *   (optional).  Defaults to the top level tab panel.
 */
XDMoD.DashboardTools.navigate = function (linkPath, tabPanel) {

    // NOTE: Using slice to copy array.
    var path = linkPath.slice(0),
        item = path.shift();

    // If no tab panel is specified, use the top level tab panel.
    tabPanel = tabPanel || Ext.getCmp('top-tab-panel');

    // If there is another item in the path, add a listener to change
    // the tab of the panel that was just made active.
    if (path.length > 0) {
        tabPanel.on('tabchange', function () {
            XDMoD.DashboardTools.navigate(path, tabPanel.getActiveTab());
        }, this, { single: true });
    }

    tabPanel.setActiveTab(item);
};

