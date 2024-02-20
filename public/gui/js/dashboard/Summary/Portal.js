/**
 * Status summary portal.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Summary');

XDMoD.Summary.Portal = Ext.extend(Ext.ux.Portal, {

    constructor: function (config) {
        config = config || {};

        this.portletsStore = new XDMoD.Summary.PortletsStore();

        Ext.apply(config, {
            listeners: {
                afterrender: {
                    fn: function () {
                        this.loadPortlets({
                            callback: function () {
                                this.addPortlets(
                                    this.portletsStore.getItems()
                                );
                            },
                            scope: this
                        });
                    },
                    scope: this
                }
            }
        });

        XDMoD.Summary.Portal.superclass.constructor.call(this, config);
    },

    loadPortlets: function (options) {
        this.portletsStore.load(options);
    },

    addPortlets: function (portlets) {
        var columnWidth = 480,
            columnCount = Math.floor(this.getInnerWidth() / columnWidth),
            columns = [],
            portletColumns = [],
            i;

        for (i = 0; i < columnCount; i++) {
            columns.push([]);
        }

        Ext.each(portlets, function (item, index) {
            columns[index % columnCount].push(item);
        }, this);

        for (i = 0; i < columnCount; i++) {
            portletColumns.push({
                width: columnWidth,
                style: 'padding: 1px 0 0 1px',
                items: columns[i]
            });
        }

        this.add(portletColumns);
        this.doLayout();
    }
});

