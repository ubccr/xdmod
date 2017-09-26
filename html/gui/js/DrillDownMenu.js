/*  
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2010-Aug-03
 *
 * This class contains the funcationality for the menu presented on drilldown on charts in xdmod
 *
 *
 * @class CCR.xdmod.ui.DrillDownMenu
 * @extends Ext.menu.Menu
 *
 * @constructor
 * @param {Object} config The configuration options
 * @ptype drilldownmenu
 */
CCR.xdmod.ui.DrillDownMenu = function (config) {
    CCR.xdmod.ui.DrillDownMenu.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.DrillDownMenu

Ext.extend(CCR.xdmod.ui.DrillDownMenu, Ext.menu.Menu, {
    node: null,
    handler: function (drillDown) {

    },
    drillDownGroupBys: [],
    initComponent: function () {
        var items = [];
        if (this.drillDownGroupBys.length !== 0) {
            var groupByDescripter = this.drillDownGroupBys;

            for (var i = 0; i < groupByDescripter.length; i++) {
                var gbd = groupByDescripter[i];
                if (gbd.length == 2 &&
                    (this.node.attributes.parameters == null ||
                        (this.node.attributes.parameters[gbd[0]] == null &&
                            ((gbd[0] != 'provider') || (this.node.attributes.parameters.resource == null && this.node.getPath('text').search('by Resource') == -1))
                        )
                    )) {
                    var disabled = false;
                    for (var j = 0; j < CCR.xdmod.ui.disabledMenus.length; j++) {
                        if (CCR.xdmod.ui.disabledMenus[j].group_by == gbd[0] && CCR.xdmod.ui.disabledMenus[j].realm == this.realm) {
                            disabled = true;
                        }
                    }
                    var childItems = [
                        '<b class="menu-title">Available metrics:</b>'
                    ];
                    childItems.push({
                        text: 'test',
                        iconCls: 'chart'
                    });
                    items.push(
                        new Ext.menu.Item({
                            scope: this,
                            drillDown: gbd.join('-'),
                            paramLabel: gbd[1],
                            text: gbd[1],
                            iconCls: 'drill',
                            disabled: disabled,
                            handler: function (b, e) {
                                this.handler(b.drillDown);
                            }
                        })
                    );
                }
            }
        }
        if (this.valueParam == 0) {
            var items = [];
            items.push('<b class="menu-title">Further drilldown is not available for this bar.<br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        } else
        if (this.groupByIdParam < -9999) {
            var items = [];
            items.push('<b class="menu-title">Drilldown for this bar is not available at this time.</b><br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });

        } else
        if (items.length > 0) {
            items.sort(function (a, b) {
                if (a.text == b.text) return 0;
                if (a.text < b.text) return -1;
                if (a.text > b.text) return 1;
            });
            if (this.label !== null) {
                items.unshift('<b class="menu-title">For ' + this.label.wordWrap(40, '<br/>') + ', Drilldown to:</b><br/>');
            } else {
                items.unshift('<b class="menu-title">Drilldown to:</b><br/>');
            }
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        } else {
            var items = [];
            items.push('<b class="menu-title">No further drilldowns available.</b><br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        }
        // Call parent (required)
        CCR.xdmod.ui.DrillDownMenu.superclass.initComponent.apply(this, arguments);
    }

}); // CCR.xdmod.ui.DrillDownMenu