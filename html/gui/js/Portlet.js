/**
 * @class CCR.xdmod.ui.Portlet
 * @extends Ext.ux.Portlet
 * @author: Greg Dean
 *
 * Creates a new portlet that includes a help tour tips are added to the helpTourDetails
 * property. Example json object below.
 *
 * helpTourDetails: {
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
 *   ]
 * }
 */
CCR.xdmod.ui.Portlet = Ext.extend(Ext.ux.Portlet, {
    helpTour: null,
    helpTourDetails: [],
    initComponent: function() {
        this.makeHelpTour();

        if (this.tools === undefined) {
            this.tools = [];
        }

        this.tools.push({
            id: 'maximize',
            qtip: 'View Portlet Help Tour',
            scope: this,
            handler: function(event, toolEl, panel) {
                this.helpTour.startTour();
            }
        });

        Ext.ux.Portlet.superclass.initComponent.apply(this, arguments);
    },
    makeHelpTour: function() {
        var self = this;

        if (self.helpTourDetails.tips !== undefined && self.helpTourDetails.tips.length > 0) {
            self.helpTour = new Ext.ux.HelpTipTour({
                title: self.helpTourDetails.title,
                items: []
            });
            self.helpTourDetails.tips.forEach(function(value, key) {
                var tip = new Ext.ux.HelpTip({
                    html: value.html,
                    target: value.target,
                    position: value.position
                });

                self.helpTour.items.push(tip);
            });
        }
    }
})
