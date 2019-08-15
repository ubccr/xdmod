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
    xtype: 'helptip',
    bodyCssClass: 'help-tip-body',
    baseCls: 'base-help-tip',
    spotlight: null,
    bbar: [],
    autoHide: false,
    closable: true,
    offset: [0, 0],
    anchorOffset: 0,
    initComponent: function () {
        // Adding a question make to the end of the supplied posiion makes sure
        // the tip will show up on the viewable area of the page.
        if (this.position[this.position.length - 1] !== '?') {
            this.position = this.position + '?';
        }
        this.originalAnchorPosition = this.position;
        Ext.ToolTip.superclass.initComponent.call(this);
    },
    listeners: {
        hide: function () {
            this.spotlight.hide();
            this.position = this.originalAnchorPosition;
        }
    },
    onShow: function () {
        if (this.anchorEl !== undefined) {
            Ext.destroy(this.anchorEl);
        }

        var anchor = this.position.split('-');
        this.tipAnchorPos = anchor[0];
        this.targetAnchorPos = anchor[1];

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

        this.anchorEl.setStyle('z-index', this.el.getZIndex() + 1).setVisibilityMode(Ext.Element.DISPLAY);

        Ext.ToolTip.superclass.onShow.call(this);
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
    findScrollableParent: function (el) {
        var parentEl = el.findParentNode('', 1, true);

        if (parentEl !== null && parentEl !== undefined) {
            return (parentEl.isScrollable() === true) ? parentEl : this.findScrollableParent(parentEl);
        }

        return null;
    },
    getElementAnchorPositions: function (el, constrainOffset) {
        var elementRegion = el.getRegion();
        var horizontalCenter = Math.round(elementRegion.left + ((elementRegion.right - elementRegion.left) / 2))
        var verticalCenter = Math.round(elementRegion.top + ((elementRegion.bottom - elementRegion.top) / 2));

        return [
            (elementRegion.left - constrainOffset) + ',' + elementRegion.top,
            elementRegion.right + ',' + elementRegion.top,
            (elementRegion.left - constrainOffset) + ',' + elementRegion.bottom,
            elementRegion.right + ',' + elementRegion.bottom,
            (horizontalCenter - constrainOffset) + ',' + elementRegion.top,
            (horizontalCenter - constrainOffset) + ',' + elementRegion.bottom,
            (elementRegion.left - constrainOffset) + ',' + verticalCenter,
            elementRegion.right + ',' + verticalCenter
        ];
    },
    getConstraintOffset: function (el) {
        var nonConstrainedPosition = (this.position[this.position.length - 1] === '?') ? this.position.slice(0, -1) : this.position;
        var nonConstrainedXY = this.el.getAlignToXY(el, nonConstrainedPosition);
        var constrainedXY = this.el.getAlignToXY(el, this.position);
        var constrainOffset = 0;

        if (nonConstrainedXY[0] < constrainedXY[0]) {
            constrainOffset = (nonConstrainedXY[0] >= 0) ? this.el.getConstrainOffset() : -this.el.getConstrainOffset();
        }

        return constrainOffset;
    },
    setAnchorPosition: function (el) {
        var anchorPositionMap = ['tl', 'tr', 'bl', 'br', 't', 'b', 'l', 'r'];
        var targetElementXY = this.getElementAnchorPositions(Ext.Element.get(el), 0);
        var tipXY = this.getElementAnchorPositions(this.el, this.getConstraintOffset(el));

        // Find a [x,y] that matches between the possible anchor points for the
        // HelpTip and the target element it is being anchored to
        var anchorPositionMatch = targetElementXY.map(function(value, key) {
            return (tipXY.includes(value)) ? anchorPositionMap[tipXY.indexOf(value)] + '-' + anchorPositionMap[key] : false;
        }).filter(function(el) {
            return el !== false;
        });

        // If no matching [x,y] pair is found in the statement above look for a matching
        // point between the two elements and use that point as an anchor position
        if (anchorPositionMatch.length == 0) {
            var tipElementRegions = this.el.getRegion();
            var targetElementRegions = Ext.Element.get(el).getRegion();
            if (tipElementRegions.top == targetElementRegions.top) {
                this.position = 't-t?';
            } else if (tipElementRegions.top == targetElementRegions.bottom) {
                this.position = 't-b?';
            } else if (tipElementRegions.bottom == targetElementRegions.top) {
                this.position = 'b-t?';
            } else if (tipElementRegions.bottom == targetElementRegions.bottom) {
                this.position = 'b-b?';
            } else if (tipElementRegions.right == targetElementRegions.right) {
                this.position = 'r-r?';
            } else if (tipElementRegions.right == targetElementRegions.left) {
                this.position = 'r-l?';
            } else if (tipElementRegions.left == targetElementRegions.left) {
                this.position = 'l-l?';
            } else if (tipElementRegions.left == targetElementRegions.right) {
                this.position = 'l-r?';
            }
        } else {
            this.position = anchorPositionMatch[0] + '?';
        }
    },
    getOffset: function () {
        var p = this.position.split('-');
        var alignmentOffsets = {
            bl: [-10, -7],
            tl: [-10, 7],
            t: [0, 7],
            b: [0, -7],
            tr: [17, 8],
            br: [19, -7],
            l: [7, 0],
            r: [-7, 0]
        };

        var offset = alignmentOffsets[p[0]].map(function(v, k) {
            return v + this.offset[k];
        }, this);

        return offset;
    },
    showBy: function (el) {
        if (!this.rendered) {
            this.render(Ext.getBody());
        }

        var element = Ext.get(el);

        // Find a parent element of the element you are anchoring the Help Tip to
        // that can be scrolled and use that element to scroll the page to make
        // sure the target element can be seen
        element.scrollIntoView(this.findScrollableParent(element));
        this.createSpotlight();
        this.spotlight.show(el);

        // Help tips are aligned to the specified target element relative to specific
        // anchor points. In order to correctly anchor the help tip, the help tip must
        // has a height and width which it does not have until iti si rendered on the
        // page. The help tip is first shown off the screen with the statement below so
        // it has a height and width and then the showAt() function is called again
        // to show it in the correct location relative to its target element.
        this.showAt([-1000, -1000]);
        this.showAt(this.el.getAlignToXY(el, this.position));
        this.setAnchorPosition(el);
        this.showAt(this.el.getAlignToXY(el, this.position, this.getOffset()));
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

Ext.reg('helptip', Ext.ux.HelpTip);
