/**
 * User summary store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.UsersSummary');

XDMoD.UsersSummary.Store = Ext.extend(Ext.data.JsonStore, {
    url: '/internal_dashboard/controllers/user.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        this.logLevels = [];

        Ext.apply(config, {
            baseParams: {
                operation: 'get_summary'
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                {
                    name: 'user_count',
                    type: 'int'
                },
                {
                    name: 'logged_in_last_7_days',
                    type: 'int'
                },
                {
                    name: 'logged_in_last_30_days',
                    type: 'int'
                }
            ]
        });

        XDMoD.UsersSummary.Store.superclass.constructor.call(this, config);
    }
});

