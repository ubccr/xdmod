/**
 * @class CCR.xdmod.ui.ETLViewerTreeFilter
 * Note this class is experimental and doesn't update the indent (lines) or expand collapse icons of the nodes
 * @param {Ext.tree.TreePanel} tree
 * @param {Object} config (optional)
 */
CCR.xdmod.ui.ETLViewerTreeFilter = function (tree, config) {
    this.tree = tree;
    this.filtered = {};
    this.stack = [];
    Ext.apply(this, config);
};

CCR.xdmod.ui.ETLViewerTreeFilter.prototype = {
    clearBlank: false,
    reverse: false,
    autoClear: false,

    /**
     * Filter the data by a specific attribute.
     * @param {String/RegExp} value Either string that the attribute value
     * should start with or a RegExp to test against the attribute
     * @param {String|String[]} attrs (optional) The attribute passed in your node's attributes collection. Defaults to "text".
     * @param {Ext.tree.TreeNode} startNode (optional) The node to start the filter at.
     */
    filter: function (value, attrs, startNode) {
        attrs = attrs || ["text"];

        if (typeof attrs === "string") {
            attrs = [attrs]
        }

        if (typeof value !== 'string') {
            throw 'Invalid value type, Expected string.'
        }

        if (value.length === 0 && this.clearBlank) {
            this.clear();
            return;
        }

        this.filterBy(value, attrs, startNode);
    },

    /**
     * Filter by a function. The passed function will be called with each
     * node in the tree (or from the node). If the function returns true, the node is kept
     * otherwise it is filtered. If a node is filtered, its children are also filtered.
     * @param {String} value The filter function
     * @param {String[]} attrs (optional) The scope (<code>this</code> reference) in which the function is executed. Defaults to the current Node.
     * @param {Ext.tree.TreeNode|Ext.tree.AsyncTreeNode} node
     */
    filterBy: function (value, attrs, node) {
        node = node || this.tree.root;

        let stack = this.stack;
        let filtered = this.filtered;

        node.match = this.matchString(value, attrs, node);

        // If this is a leaf node, make sure to hide it if needed.
        if (this.isLeaf(node)) {
            if (node.match) {
                for (let i = 0; i < stack.length; i++) {
                    let ancestor = stack[i];
                    this.show(ancestor);
                }
                this.show(node);
            } else {
                this.hide(node);
            }
        } else {
            stack.push(node);
            if (node.match) {
                this.show(node);
            } else if (!('text' in node && node.text && node.text === 'Root')) {
                this.hide(node);

                let id = node.id ? node.id : Ext.id();
                if (!node.id) {
                    node.id = id;
                }

                filtered[id] = node;
            }

            let children = this.getChildren(node);

            for (let i = 0; i < children.length; i++) {
                let child = children[i];
                this.filterBy(value, attrs, child);
            }

            // After we're done with this node then pop it off the stack.
            stack.pop();
        }
    },

    matchString: function (value, attrs, node) {
        let found = false;
        for (let i = 0; i < attrs.length; i++) {
            let attr = attrs[i];
            if (node.attributes && attr in node.attributes && node.attributes[attr]) {
                found = node.attributes[attr].toLowerCase().includes(value);
            } else if (attr in node && node[attr]) {
                if (typeof node[attr] === "string") {
                    found = node[attr].toLowerCase().includes(value);
                } else {
                    found = String(node[attr]).toLowerCase().includes(value);
                }
            }
            if (found) {
                break;
            }
        }
        return found;
    },

    hide: function (node) {
        if (node.ui && node.ui.hide && typeof node.ui.hide === "function") {
            node.ui.hide();
        } else {
            node.hidden = true;
        }
    },

    show: function (node) {
        if (node.ui && node.ui.show && typeof node.ui.show === 'function') {
            node.ui.show();
        } else {
            node.hidden = false;
        }
    },

    getChildren: function (node) {
        let children = [];
        if ("children" in node && node.children) {
            children = node.children;
        } else if ("attributes" in node && "children" in node.attributes && node.attributes["children"]) {
            children = node.attributes["children"]
        } else if (node.hasChildNodes && node.hasChildNodes() && node.childNodes && node.childNodes.length > 0) {
            children = node.childNodes;
        }
        return children;
    },

    isLeaf: function (node) {
        return ("leaf" in node && node.leaf === true) || ("isLeaf" in node && typeof node.isLeaf === "function" && node.isLeaf() === true)
    },

    /**
     * Clears the current filter. Note: with the "remove" option
     * set a filter cannot be cleared.
     */
    clear: function () {
        this.filtered = {};
        this.tree.getRootNode().cascade(function (node) {
            node.hidden = false;
            node.show();
        });
    }
};
