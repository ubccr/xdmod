/**
 * @class Ext.ux.HelpTip
 * @extends Ext.Tip
 * @author: Greg Dean
 *
 * Creates a new component called HelpTip which is an extension of Ext.Tip. When creating provide
 * the html to be shown in the tip, a css selector as a target for the tip and where the tip's position
 * in relation to the target element.
 *
 * var helpTip = new Ext.ux.HelpTip({
 *   html: "Some html",
 *   target: "#a_css_selector",
 *   position: "tl-br"
 * });
 */
Ext.ux.HelpTip = Ext.extend(Ext.Tip, {

    cls: 'help-tip',
    bodyCssClass: 'help-tip-body',
    baseCls: 'base-help-tip',
    spotlight: null,
    bbar: [],
    autoHide: false,
    closable: true,
    offset: [0, 0],
    anchorOffset: 0,
    initComponent: function () {
        var anchor = this.position.split('-');
        this.tipAnchorPos = anchor[0];
        this.targetAnchorPos = anchor[1];
        Ext.ToolTip.superclass.initComponent.call(this);
    },
    listeners: {
        hide: function () {
            this.spotlight.hide();
        }
    },
    // private
    onRender: function (ct, position) {
        Ext.ToolTip.superclass.onRender.call(this, ct, position);
        var anchorCls = {
            t: 'x-tip-anchor-top',
            b: 'x-tip-anchor-bottom',
            r: 'x-tip-anchor-right',
            l: 'x-tip-anchor-left'
        };

        this.anchorCls = anchorCls[this.tipAnchorPos.charAt(0)];
        this.anchorEl = this.el.createChild({
            cls: 'x-tip-anchor ' + this.anchorCls
        });
    },

    // private
    afterRender: function () {
        Ext.ToolTip.superclass.afterRender.call(this);
        this.anchorEl.setStyle('z-index', this.el.getZIndex() + 1).setVisibilityMode(Ext.Element.DISPLAY);
    },

    syncAnchor: function () {
        var anchorPos;
        var offset;
        switch (this.tipAnchorPos) {
            case 'tl':
                anchorPos = 'b';
                offset = [10 + this.anchorOffset, 2];
                break;
            case 'tr':
                anchorPos = 'b';
                offset = [-10 + this.anchorOffset, 2];
                break;
            case 't':
                anchorPos = 'b';
                offset = [this.anchorOffset, 2];
                break;
            case 'r':
                anchorPos = 'l';
                offset = [-2, this.anchorOffset];
                break;
            case 'b':
                anchorPos = 't';
                offset = [this.anchorOffset, -2];
                break;
            case 'bl':
                anchorPos = 't';
                offset = [10 + this.anchorOffset, -2];
                break;
            case 'br':
                anchorPos = 't';
                offset = [-10 + this.anchorOffset, -2];
                break;
            default:
                anchorPos = 'r';
                offset = [2, 11 + this.anchorOffset];
                break;
        }
        this.anchorEl.alignTo(this.el, anchorPos + '-' + this.tipAnchorPos, offset);
    },

    showBy: function (el) {
        if (!this.rendered) {
            this.render(Ext.getBody());
        }

        var alignmentOffsets = {
            bl: [0, -7],
            tl: [-10, 7],
            t: [0, 7],
            b: [0, -7],
            tr: [17, 8],
            br: [19, -7],
            l: [7, 0],
            r: [-7, 0]
        };

        var offset = alignmentOffsets[this.tipAnchorPos].map( function(v, k) {
            return v + this.offset[k];
        }, this);

        this.createSpotlight();
        this.spotlight.show(el);

        // Help tips are aligned to the specified target element relative to specific
        // anchor points. In order to correctly anchor the help tip, the help tip must
        // has a height and width which it does not have until iti si rendered on the
        // page. The help tip is first shown off the screen with the statement below so
        // it has a height and width and then the showAt() function is called again
        // to show it in the correct location relative to its target element.
        this.showAt([-1000, -1000]);
        this.showAt(this.el.getAlignToXY(el, this.position, offset));

        this.syncAnchor();
    },

    hideTip: function () {
        this.hide();
    },

    createSpotlight: function () {
        if (this.spotlight === null) {
            this.spotlight = new Ext.ux.Spotlight({
                easing: 'easeOut',
                duration: 0.3
            });
        }
    }
});
