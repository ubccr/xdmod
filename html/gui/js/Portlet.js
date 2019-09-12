/**
 * @class CCR.xdmod.ui.Portlet
 * @extends Ext.ux.Portlet
 * @author: Greg Dean
 *
 * Creates a new portlet that includes a help tour tips are added to the helpTourDetails
 * property. Example json object below.
 * XDMoD.Module.Dashboard.AllocationsComponent = Ext.extend(CCR.xdmod.ui.Portlet, {
 *  layout: 'fit',
 *  autoScroll: true,
 *  title: 'example portlet',
 *  help: {
 *     html: 'The html for the help window',
 *     title: 'The title for the help window'
 *  },
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
    collapsible: false,
    helpTour: null,
    helpTourDetails: {},
    initComponent: function () {
        if (!this.tools) {
            this.tools = [];
        }

        if (this.help) {
            var helpText = this.help.html;
            var helpTitle = 'Help for ' + this.help.title;

            // Store a reference to the window to prevent multiple windows
            // being created if the user clicks the help button multiple times.
            var helpwin;

            this.tools.push({
                id: 'help',
                qtip: 'Display help dialog',
                handler: function () {
                    if (!helpwin) {
                        helpwin = new Ext.Window({
                            layout: 'fit',
                            width: 720,
                            height: 580,
                            title: helpTitle,
                            items: {
                                html: helpText
                            },
                            listeners: {
                                close: function () {
                                    helpwin = null;
                                }
                            },
                            bbar: {
                                items: [
                                    {
                                        text: 'More Information',
                                        handler: function () {
                                            window.open('user_manual.php?t=Dashboard');
                                        }
                                    },
                                    '->',
                                    {
                                        text: 'Close',
                                        handler: function () {
                                            helpwin.close();
                                        }
                                    }
                                ]
                            }
                        });
                    }
                    helpwin.show();
                }
            });
        }

        if (this.helpTourDetails.tips !== undefined && this.helpTourDetails.tips.length > 0) {
            this.helpTourDetails.tips.forEach(function (value, index, arr) {
                arr[index].target = (value.target.slice(0, 1) !== '/') ? '#' + this.id + ' ' + value.target : value.target.slice(1, value.target.length);
            }, this);

            this.helpTour = new Ext.ux.HelpTipTour({
                title: this.helpTourDetails.title,
                items: this.helpTourDetails.tips
            });

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
