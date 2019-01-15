/**
 * XDMoD.Modules.SummaryPortlets.DeveloperPortlet
 *
 * This is an example panel that can be used as a template.
 */

Ext.namespace('XDMoD.Modules.SummaryPortlets');

XDMoD.Modules.SummaryPortlets.DeveloperPortlet = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    title: 'XDMoD software developer info',
    bbar: {
        items: [{
            text: 'Reset Layout',
            handler: function () {
                Ext.Ajax.request({
                    url: XDMoD.REST.url + '/summary/layout',
                    method: 'DELETE'
                });
            }
        }]
    },

    /**
     *
     */
    initComponent: function () {
        var aspectRatio = 0.8;
        this.height = this.width * aspectRatio;

        this.items = [{
            itemId: 'console',
            html: '<pre>' + JSON.stringify(this.config, null, 4) + '</pre>'
        }];

        XDMoD.Modules.SummaryPortlets.DeveloperPortlet.superclass.initComponent.apply(this, arguments);
    },

    listeners: {
        /**
         * duration_change event gets fired when the duration settings
         * in the top toolbar are changed by the user or when the refresh
         * button is clicked.
         *
         * A typical portlet will reload its content with the updated
         * duration parameters.
         */
        duration_change: function (timeframe) {
            var console = this.getComponent('console');
            console.body.insertHtml('beforeBegin', '<pre>' + JSON.stringify(timeframe, null, 4) + '</pre>');
        }
    }
});

/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('DeveloperPortlet', XDMoD.Modules.SummaryPortlets.DeveloperPortlet);
