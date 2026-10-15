/**
 * Summary portlet base class.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Summary');

XDMoD.Summary.Portlet = Ext.extend(Ext.ux.Portlet, {
    height: 140,

    bodyStyle: {
        backgroundColor: 'white'
    },

    listeners: {
        afterrender: function () {
            if (this.store !== undefined) {
                this.store.load();
            }
        }
    },

    constructor: function (config) {
        config = config || {};

        if (this.store !== undefined) {
            this.store.on('load', function (store, records) {
                this.update(this.getHtml(records[0]));
            }, this);
        }

        config.tools = [];

        if (config.linkPath) {
            config.tools.push({
                id: 'gear',
                handler: function () {
                    XDMoD.DashboardTools.navigate(config.linkPath);
                }
            });
        }

        XDMoD.Summary.Portlet.superclass.constructor.call(this, config);
    },

    formatDuration: function (duration) {
        if (duration < 1000) {
            return '< 1 second';
        }

        var groups = [
            { name: 'day',    ms: 24 * 60 * 60 * 1000 },
            { name: 'hour',   ms:      60 * 60 * 1000 },
            { name: 'minute', ms:           60 * 1000 },
            { name: 'second', ms:                1000 }
        ];
        var sections = [];

        Ext.each(groups, function (item, index) {
            if (duration >= item.ms) {
                var quantity = Math.floor(duration / item.ms);
                if (quantity === 1) {
                    sections.push('1 ' + item.name);
                } else {
                    sections.push(quantity + ' ' + item.name + 's');
                }
                duration = duration % item.ms;
            }
        }, this);

        return sections.join(', ');
    },

    getHtml: function (record) {
        // Implement in subclass.
    }
});

