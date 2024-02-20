/**
 * Log level store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Log');

XDMoD.Log.LevelsStore = Ext.extend(Ext.data.JsonStore, {
    url: '/controllers/log.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            baseParams: {
                operation: 'get_levels'
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                { name: 'id',   type: 'int' },
                { name: 'name', type: 'string' }
            ]
        });

        XDMoD.Log.LevelsStore.superclass.constructor.call(this, config);
    }
});

