Ext.ns('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.ETLViewerTreePanel = Ext.extend(XDMoD.Admin.ETL.ETLViewerTree, {
    rootVisible: false,
    root: {
        expanded: true
    },
    url: null,

    initComponent: function () {
        this.url = XDMoD.REST.url + '/etl/pipelines';
        this.loaded = false;

        XDMoD.Admin.ETL.ETLViewerTreePanel.superclass.initComponent.call(this, arguments);
    },

    /**
     * Function to be utilized by {XDMoD.Module.JobViewer.SearchHistoryTree} when determining the final URL to use when
     * making a rest call. The results of this function will be used to provide query parameters to said URL. As such
     * they will ( and should ) be returned as an {Object} ( otherwise known as an associative array ).
     *
     * @param {Ext.tree.TreeNode} node
     * @returns {{}}
     */
    getParams: function (node) {
        var results = {};
        for (var n = node; n && !n.isRoot; n = n.parentNode) {

            var key = typeof n.attributes.dtype === 'string'
                ? n.attributes.dtype
                : "" + n.attributes.dtype;

            var value = n.attributes[key];
            results[key] = value;
        }
        return results;
    }, // getParams

    /**
     * The events that this component will listen for and react to.
     */
    listeners: {

        /**
         * This will be called each time a node is loaded.
         */
        load: function () {
            this.loaded = true;
            if (this.expansionWaiting) {
                this.expansionWaiting = this._handleExpandNode(this.expansionPath);
            }
        }, // load

        /**
         * Fired before a new node is appended to the the 'tree'. Is used here to ensure
         * that each node has an appropriately set icon class based on it's 'type' attribute.
         *
         * @param {Ext.tree.TreePanel} tree that is having the 'node' appended to it.
         * @param {Ext.tree.TreeNode} parent that is having the 'node' added to it.
         * @param {Ext.tree.TreeNode} node that is being added to the 'parent'.
         */
        beforeappend: function (tree, parent, node) {
            this.loaded = false;

            switch (node.attributes.type) {
                case 'keyvaluedata':
                    node.setIconCls('dataset');
                    break;
                case 'timeseries':
                    node.setIconCls('metric');
                    break;
                case 'nested':
                    node.setIconCls('menu');
                    break;
                case 'metrics':
                case 'detailedmetrics':
                    node.setIconCls('chart');
                    break;
                default:
                    switch (node.attributes.dtype) {
                        case 'realm':
                            node.setIconCls('realm');
                            break;
                        case 'recordid':
                            node.setIconCls('search');
                            break;
                        default:
                            // icon not changed
                            break;
                    }
                    break;
            }
        }, // beforeappend

        /**
         * Fired when a node is 'clicked'. Note, this is different than when
         * a node is 'expanded'. See the event listener 'expandnode' for that
         * functionality.
         *
         * @param {Ext.tree.TreeNode} node
         * @param {Ext.EventObject} event
         */
        click: function (node, event) {
            this._handleNodeClick(node);
        }, // click
        /**
         * Not to be confused with 'expandnode'. This event is triggered when
         * a third party is requesting a node to be expanded. Not when it has
         * been expanded.
         *
         * @param {Array} path an array of objects in the following format:
         * [
         *   {
         *     dtype: <dtype>,
         *     value: <value>
         *   }, ....
         * ]
         * each entry should describe, from the first non-root node, how to
         * arrive at the node identified by the last entry.
         */
        expand_node: function(path, select) {
            this.loaded = this.loaded || (this.root.loaded && !this.root.loading);

            if (this.loaded) {
                this.expansionWaiting = this._handleExpandNode(path, select);
                if (this.expansionWaiting) this.expansionPath = path;
                if (!this.expansionWaiting) this.parentTab.loading = false;
            } else {
                this.expansionWaiting = true;
                this.expansionPath = path;
                this.expansionSelection = select;
            }
        }, // expand_node

        /**
         * This event indicates that the component should attempt to reload
         * it's root node, which is another way of saying, reload its data
         * source. If a path is provided then we set the expansion waiting flag
         * so that the node identified by the path is expanded / selected
         * correctly.
         *
         * @param {Array} path
         */
        reload_root: function (path) {
            var exists = CCR.exists;

            this.loaded = false;
            var selectionModel = this.getSelectionModel();
            if (selectionModel && selectionModel.selNode) selectionModel.selNode = null;
            this.root.reload();
            if (exists(path)) {
                this.expansionWaiting = true;
                this.expansionPath = path;
            }
        } // reload_root

    },
    /**
     *
     * Example 'attributes':
     *
     * {
     *   dtype: 'infoid',
     *   infoid: 0,
     *   text: 'Accounting data',
     *   type: 'keyvaluedata',
     *   url: '/datawarehouse/search/jobs/accounting'
     * }
     *
     * @param {Ext.tree.TreeNode} node
     * @private
     */
    _handleNodeClick: function (node) {
        var exists = CCR.exists;
        if (exists(node)) {
            this.jobViewer.fireEvent('node_selected', node);
        }
    }, // _handleNodeClick

    /**
     * Helper function that takes care of expanding the provided path.
     *
     * @param {Array} path
     * @returns {boolean}
     * @private
     */
    _handleExpandNode: function(path) {
        var exists = CCR.exists;
        var result = true;
        if (exists(path)) {
            var node = this.root;

            for (var i = 0; i < path.length && exists(node); i++) {
                var entry = path[i];
                var child = node.findChild(entry['dtype'], entry['value']);
                var endOfPath = i === (path.length - 1);
                if (exists(child)) {
                    child.expand();
                    node = child;
                    if (endOfPath && child.rendered) {
                        child.select();
                        this.jobViewer.fireEvent('node_selected', child);
                        result = false;
                    }
                } else {
                    result = !node.loaded && node.loading;
                    node = null;
                }
            }
        }
        return result;
    } // _handleExpandNode
});
