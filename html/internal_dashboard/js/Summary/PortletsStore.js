/**
 * Summary portlets configuration store.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Summary');

XDMoD.Summary.PortletsStore = Ext.extend(Ext.data.JsonStore, {
    url: 'controllers/summary.php',

    listeners: {
        exception: function (misc) {
            console.log(misc);
        }
    },

    constructor: function (config) {
        config = config || {};

        Ext.apply(config, {
            baseParams: {
                operation: 'get_portlets'
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

        XDMoD.Summary.PortletsStore.superclass.constructor.call(this, config);
    },

    getItems: function (config) {
        var items = [];

        config = config || {};

        this.each(function (record) {
            var itemConfig = Ext.apply(
                record.get('config'),
                config
            );

            var item = this.createItem(record.get('class'), itemConfig);

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

