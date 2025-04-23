Ext.ns('Ext.ux.tree');

Ext.ux.tree.MultilineTreeNodeUI = Ext.extend(Ext.tree.TreeNodeUI, {

    renderElements: function (n, a, targetNode, bulkRender) {

        this.indentMarkup = n.parentNode ? n.parentNode.ui.getChildIndent() : '';

        var cb = typeof a.checked == 'boolean';

        var href = a.href ? a.href : Ext.isGecko ? "" : "#";
        var buf = [
            '<li class="x-tree-node ' + ((this.node.ownerTree.lines == false || this.node.ownerTree.useArrows) ? 'x-tree-multiline-node-collapsed' : 'x-tree-multiline-node-expanded') + '">',
            '<div ext:tree-node-id="', n.id, '" class="x-tree-node-el x-tree-node-leaf x-unselectable ', a.cls, '" unselectable="on">',
            // Ident of node
            '<span class="x-tree-node-indent">', this.indentMarkup, "</span>",

            // State-Icon of node
            '<img src="', this.emptyIcon, '" class="x-tree-ec-icon x-tree-elbow" />',

            // Icon of node
            '<img src="', a.icon || this.emptyIcon, '" class="x-tree-node-icon', (a.icon ? " x-tree-node-inline-icon" : ""), (a.iconCls ? " " + a.iconCls : ""), '" unselectable="on" />',

            // Add checkbox if needed
            cb ? ('<input class="x-tree-node-cb" type="checkbox" ' + (a.checked ? 'checked="checked" />' : '/>')) : '',

            '<a hidefocus="on" class="x-tree-node-anchor" href="', href, '" tabIndex="1" ', a.hrefTarget ? ' target="' + a.hrefTarget + '"' : "", '>',
            // Add the node text
            '<span unselectable="on">', n.text, "</span></a>"
        ].join('');

        if (n.attributes.details) {
            // Add the additional lines
            for (var x = 0; x < n.attributes.details.length; x++) {

                buf += [
                    // Linebreak
                    "<br/>",
                    // First ident of the line
                    '<span class="x-tree-node-indent">', this.indentMarkup, "</span>",
                    // Second ident of the node
                    (!n.nextSibling && n.hasChildNodes()) ? '<span style="margin-left: 16px;"></span>' : '',
                    // Draw the line to the next sibling if available
                    n.nextSibling ? '<img src="' + this.emptyIcon + '" class="x-tree-ec-icon x-tree-elbow-line" />' : '',
                    // Draw the line to the child nodes
                    (n.hasChildNodes()) ? '<img src="' + this.emptyIcon + '" class="x-tree-ec-icon x-tree-elbow-line-multiline-expanded" style="margin-right: 2px;"/>' : '<span style="margin-left: 16px;"></span>',
                    // Handle the last item
                    (n.isLast() && !n.isExpandable()) ? '<span style="margin-left: 16px;"></span>' : '',
                    // Add the additional text
                    '<span class="x-tree-multiline-node-details">' + n.attributes.details[x] + '</span>'
                ].join('');

            }
        }

        // Close the node list
        buf += ["</div>",
            '<ul class="x-tree-node-ct" style="display:none;"></ul>',
            "</li>"
        ].join('');

        var nel;
        if (bulkRender !== true && n.nextSibling && (nel = n.nextSibling.ui.getEl())) {
            this.wrap = Ext.DomHelper.insertHtml("beforeBegin", nel, buf);
        } else {
            this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf);
        }

        this.elNode = this.wrap.childNodes[0];
        this.ctNode = this.wrap.childNodes[1];
        var cs = this.elNode.childNodes;
        this.indentNode = cs[0];
        this.ecNode = cs[1];
        this.iconNode = cs[2];
        var index = 3;
        if (cb) {
            this.checkbox = cs[3];

            this.checkbox.defaultChecked = this.checkbox.checked;
            index++;
        }
        this.anchor = cs[index];
        this.textNode = cs[index].firstChild;
    },

    updateExpandIcon: function () {

        if (this.rendered) {
            var n = this.node,
                c1, c2;
            var cls = n.isLast() ? "x-tree-elbow-end" : "x-tree-elbow";
            if (n.isExpandable()) {
                if (n.expanded) {
                    Ext.fly(this.elNode).replaceClass('x-tree-multiline-node-collapsed', 'x-tree-multiline-node-expanded');
                    cls += "-minus";
                    c1 = "x-tree-node-collapsed";
                    c2 = "x-tree-node-expanded";
                } else {
                    Ext.fly(this.elNode).replaceClass('x-tree-multiline-node-expanded', 'x-tree-multiline-node-collapsed');
                    cls += "-plus";
                    c1 = "x-tree-node-expanded";
                    c2 = "x-tree-node-collapsed";
                }
                if (this.wasLeaf) {
                    this.removeClass("x-tree-node-leaf");
                    this.wasLeaf = false;
                }
                if (this.c1 != c1 || this.c2 != c2) {
                    Ext.fly(this.elNode).replaceClass(c1, c2);
                    this.c1 = c1;
                    this.c2 = c2;
                }
            } else {
                if (!this.wasLeaf) {
                    Ext.fly(this.elNode).replaceClass("x-tree-node-expanded", "x-tree-node-leaf");
                    delete this.c1;
                    delete this.c2;
                    this.wasLeaf = true;
                }
            }
            var ecc = "x-tree-ec-icon " + cls;
            if (this.ecc != ecc) {
                this.ecNode.className = ecc;
                this.ecc = ecc;
            }
        }
    }
});