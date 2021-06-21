Ext.namespace('CCR', 'CCR.xdmod', 'CCR.xdmod.ui');

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
    remove: false,

    /**
     * Filter the data by a specific attribute.
     * @param {String/RegExp} value Either string that the attribute value
     * should start with or a RegExp to test against the attribute
     * @param {String|String[]} attrs (optional) The attribute passed in your node's attributes collection. Defaults to "text".
     * @param {Ext.tree.TreeNode} startNode (optional) The node to start the filter at.
     */
    filter: function (value, attrs, startNode) {
        attrs = attrs || ["text"];

        if (typeof attrs === 'string') {
            attrs = [attrs]
        }

        let matchFunction;
        if (typeof value == "string") {
            // auto clear empty filter
            if (value.length === 0 && this.clearBlank) {
                this.clear();
                return;
            }

            value = value.toLowerCase();
            matchFunction = function (node) {
                let found = false;
                for (let i = 0; i < attrs.length; i++) {
                    let attr = attrs[i];

                    if (node.attributes && attr in node.attributes && node.attributes[attr]) {
                        found = node.attributes[attr].toLowerCase().includes(value);
                    } else if (attr in node && node[attr]) {
                        if (typeof node[attr] === 'string') {
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
            };
        } else if (value.exec) { // regex?
            matchFunction = function (node) {
                let found = false;
                for (let i = 0; i < attrs.length; i++) {
                    let attr = attrs[i];
                    if (attr in node.attributes) {
                        found = value.test(node.attributes[attrs]);
                        break;
                    }
                }
                return found;
            };
        } else {
            throw 'Illegal filter type, must be string or regex';
        }
        this.filterBy(matchFunction, null, startNode);
    },

    /**
     * Filter by a function. The passed function will be called with each
     * node in the tree (or from the startNode). If the function returns true, the node is kept
     * otherwise it is filtered. If a node is filtered, its children are also filtered.
     * @param {Function} matchFunction The match function
     * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the function is executed. Defaults to the current Node.
     * @param {Ext.tree.TreeNode} startNode (optional)
     */
    filterBy: function (matchFunction, scope, startNode) {
        /**
         * - DFS through nodes & their child nodes
         * - current node is marked as matching or not based on the match function.
         * - After being marked, the current node is pushed onto a stack.
         * - if a node has the `leaf` attribute & it's set to `true` then that's the end of this branch.
         * - If the leaf node is not a match then hide it and add it to the filtered.
         * - Now, iterate through each node in the stack:
         *   - Match Truth Table:
         *     - Leaf Node Match | Stack Node Match | Stack Node Visible | Notes
         *     -        T        |        F         |          T         | The Stack Node needs to be visible so that the leaf node can ultimately be shown.
         *     -        T        |        T         |          T         | If they're both a match then it should of course be visible.
         *     -        F        |        T         |          T         | Even if we're not showing a leaf, if the stack node matches it should be visible.
         *     -        F        |        F         |          F         | If neither are a match then it should obviously be hidden.
         * - Once the stack is empty, than continue processing the rest of the tree.
         */

        this.processNode(matchFunction, startNode);

        let children = [];
        if ('children' in startNode && startNode.children) {
            children = startNode.children;
        } else if ('attributes' in startNode && 'children' in startNode.attributes && startNode.attributes['children']) {
            children = startNode.attributes['children']
        } else if (startNode.hasChildNodes && startNode.hasChildNodes() && startNode.childNodes && startNode.childNodes.length > 0) {
            children = startNode.childNodes;
        }

        for (let i = 0; i < children.length; i++) {
            let child = children[i];
            this.filterBy(matchFunction, null, child);
        }
    },

    processNode: function (matchFunction, node) {
        if (node === this.tree.root) {
            return;
        }
        let stack = this.stack;
        let filtered = this.filtered;

        let name = null;
        if (node.attributes && 'name' in node.attributes && node.attributes['name']) {
            name = node.attributes['name'];
        } else if (node.name) {
            name = node.name;
        }
        this.log(name, stack.length);

        let match = matchFunction(node);
        node.match = match;
        stack.push(node);

        if (('leaf' in node && node.leaf === true) || ('isLeaf' in node && typeof node.isLeaf === 'function' && node.isLeaf() === true)) {
            while (stack.length > 0) {
                let leafNode = stack.pop();
                let leafNodeMatch = null;
                if ('match' in leafNode) {
                    leafNodeMatch = leafNode.match;
                } else if ('attributes' in leafNode && 'match' in leafNode.attributes) {
                    leafNodeMatch = leafNode.attributes['match'];
                }
                if (match === false && leafNodeMatch === false) {
                    if (leafNode.ui && leafNode.ui.hide && typeof leafNode.ui.hide === 'function') {
                        leafNode.ui.hide();
                    } else {
                        leafNode.visible = false;
                    }
                    let id = leafNode.id ? leafNode.id : Ext.id();
                    filtered[id] = leafNode;
                }
            }
        }
    },

    log: function (value, indent) {
        let prefix = new Array(indent + 1).join(' ');
        console.log(`${prefix}${value}`);
    },

    /**
     * Clears the current filter. Note: with the "remove" option
     * set a filter cannot be cleared.
     */
    clear: function () {
        let filtered = this.filtered;
        for (let id in filtered) {
            if (typeof id != "function" && filtered.hasOwnProperty(id)) {
                let node = filtered[id];
                if (node && node.ui && typeof node.ui.show === 'function') {
                    node.ui.show();
                } else if (node && 'visible' in node) {
                    node.visible = true;
                }
            }
        }
        this.filtered = {};
    }
};
