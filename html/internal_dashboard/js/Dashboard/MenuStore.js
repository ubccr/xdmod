/**
 * Dashboard menu store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD', 'XDMoD.Dashboard');

XDMoD.Dashboard.MenuStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/dashboard.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            baseParams: {
                operation: 'get_menu'
            },

            root: 'response',
            idProperty: 'id',
            messageProperty: 'message',
            successProperty: 'success',
            totalProperty: 'count',

            fields: [
                { name: 'class', type: 'string' },
                { name: 'config' }
            ]
        });

        XDMoD.Dashboard.MenuStore.superclass.constructor.call(this, config);
    },

    getMenuItems: function () {
        var items = [];

        this.each(function (record) {
            var item = this.createItem(
                record.get('class'),
                record.get('config')
            );

            items.push(item);
        }, this);

        return items;
    },

    createItem: function (cls, config) {
        var fn = window,
            path = cls.split('.'),
            pathLen = path.length,
            i = 0;

        for (i = 0; i < pathLen; i++) {
            fn = fn[path[i]];
        }

        if (typeof fn !== 'function') {
            throw new Error('function "' + cls + '" not found');
        }

        return new fn(config);
    }
});

