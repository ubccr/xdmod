/**
 * @class Ext.ux.HelpTipTour
 * @extends Ext.Component
 * @author: Greg Dean
 *
 * Creates a new component called HelpTipTour which takes a set of provided Ext.ux.HelpTip components
 * and shows them in the order they are in the items elememt for this component. Previous, Next and End
 * Tour buttons are automatically added in the Ext.ux.HelpTip bbar if no buttons exist.
 *
 * var helpTipTour = new Ext.ux.HelpTipTour({
 *   title: "Title for Help Tips",
 *   items: [helpTip1, helpTip2, helpTip3]
 * });
 */
Ext.ux.HelpTipTour = Ext.extend(Ext.Component, {
    tip_index: null,
    current_tip: null,
    title: 'XDMoD Help Tips',
    initComponent: function () {
        Ext.ux.HelpTipTour.superclass.initComponent.call(this);
    },

    getTip: function (tip_index) {
        return this.items[tip_index];
    },

    startTour: function () {
        this.showTip(0);
    },

    showTip: function (tip_index) {
        var self = this;

        if (tip_index < 0 || tip_index > this.items.length) {
            return false;
        }

        this.tip_index = tip_index;
        this.current_tip = this.getTip(this.tip_index);
        var target_element = Ext.select(this.current_tip.target);

        if (this.current_tip.title === undefined) {
            this.current_tip.title = this.title;
        }

        this.current_tip.title = this.current_tip.title + ' -- Tip ' + (this.tip_index + 1) + ' of ' + this.items.length;

        var next_button = new Ext.Button({
            text: 'Next',
            cls: 'next-help-tip',
            overCls: 'help-tip-button',
            listeners: {
                click: function() {
                    var tip_to_show = (self.tip_index < self.items.length - 1) ? self.tip_index + 1 : self.items.length - 1;
                    self.current_tip.hideTip();
                    self.showTip(tip_to_show);
                }
            }
        });

        var previous_button = new Ext.Button({
            text: 'Previous',
            cls: 'previous-help-tip',
            overCls: 'help-tip-button',
            listeners: {
                click: function() {
                    var tip_to_show = (self.tip_index > 0) ? self.tip_index - 1 : 0;
                    self.current_tip.hideTip();
                    self.showTip(tip_to_show);
                }
            }
        });

        var end_tour_button = new Ext.Button({
            text: 'End Tour',
            cls: 'end-help-tip-tour',
            overCls: 'help-tip-button',
            listeners: {
                click: function () {
                    self.current_tip.hideTip();
                }
            }
        });

        // If there is nothing in the bbar of the HelpTip being shown add appropriate
        // Previous, Next or End Tour buttons
        if (this.current_tip.bbar === null) {
            if (this.tip_index === 0) {
                this.current_tip.getBottomToolbar().addFill();
                this.current_tip.getBottomToolbar().addButton(next_button);
            } else if (this.tip_index === this.items.length - 1) {
                this.current_tip.getBottomToolbar().addButton(previous_button);
                this.current_tip.getBottomToolbar().addFill();
                this.current_tip.getBottomToolbar().addButton(end_tour_button);
            } else {
                this.current_tip.getBottomToolbar().addButton(previous_button);
                this.current_tip.getBottomToolbar().addFill();
                this.current_tip.getBottomToolbar().addButton(next_button);
            }
        }

        var el = (target_element.elements.length !== 0) ? target_element.elements[0] : '';
        this.current_tip.showBy(el);

        return true;
    }
});
