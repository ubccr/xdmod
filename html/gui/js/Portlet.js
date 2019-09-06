/**
 * @class CCR.xdmod.ui.Portlet
 * @extends Ext.ux.Portlet
 * @author: Greg Dean
 *
 * Creates a new portlet that includes a help tour tips are added to the helpTourDetails
 * property. Example json object below.
 * XDMoD.Module.Dashboard.AllocationsPortlet = Ext.extend(CCR.xdmod.ui.Portlet, {
 *  layout: 'fit',
 *  autoScroll: true,
 *  title: 'example portlet',
 *  id: 'example_portlet',
 *  helpTourDetails: {
 *   startAt: '#some-css-selector',
 *   title: Title for help tips',
 *   tips: [
 *       {
 *          html: 'Some information',
 *          target: '#css-selector',
 *          position: 'tl-br'
 *       },
 *       {
 *          html: 'More information',
 *          target: '#css-selector',
 *          position: 'br-tl'
 *       },
 *       {
 *          html: 'Yet more information',
 *          target: '.css-selector',
 *          position: 't-b'
 *       }
 *    ]
 * }
 */
CCR.xdmod.ui.Portlet = Ext.extend(Ext.ux.Portlet, {
    helpTour: null,
    helpTourDetails: {},
    initComponent: function () {
        if (this.helpTourDetails.tips !== undefined && this.helpTourDetails.tips.length > 0) {
            this.helpTourDetails.tips.forEach(function (value, index, arr) {
                arr[index].target = (value.target.slice(0, 1) !== '/') ? '#' + this.id + ' ' + value.target : value.target.slice(1, value.target.length);
            }, this);

            this.helpTour = new Ext.ux.HelpTipTour({
                title: this.helpTourDetails.title,
                items: this.helpTourDetails.tips
            });

            if (this.tools === undefined) {
                this.tools = [];
            }

            this.tools.push({
                id: 'maximize',
                qtip: 'View Component Help Tour',
                scope: this,
                handler: function (event, toolEl, panel) {
                    this.helpTour.startTour();
                }
            });
        }

        Ext.ux.Portlet.superclass.initComponent.apply(this, arguments);
    }
});
