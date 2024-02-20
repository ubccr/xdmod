/**
 * Users summary portlet.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.UsersSummary');

XDMoD.UsersSummary.Portlet = Ext.extend(XDMoD.Summary.Portlet, {
    title: 'Users',

    constructor: function (config) {
        config = config || {};

        this.store = new XDMoD.UsersSummary.Store();

        XDMoD.UsersSummary.Portlet.superclass.constructor.call(this, config);
    },

    getHtml: function (record) {
        return 'User Count: ' + record.get('user_count') + '<br/>' +
            'Users logged in during last 7 days: ' + record.get('logged_in_last_7_days') + '<br/>' +
            'Users logged in during last 30 days: ' + record.get('logged_in_last_30_days');
    }
});

