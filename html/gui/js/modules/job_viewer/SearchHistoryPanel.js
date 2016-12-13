Ext.ns('XDMoD', 'XDMoD.Module', 'XDMoD.Module.JobViewer');

/**
 * This component is one of the main visual components of the Single Job Viewer.
 * It supplies a visual repository for a users Search History.
 */
XDMoD.Module.JobViewer.SearchHistoryPanel = Ext.extend(XDMoD.Module.JobViewer.SearchHistoryTree, {

    /**
     * Determines whether or not the root node is visible.
     *
     * @var {boolean}
     */
    rootVisible: false,

    /**
     * This {Ext.tree.TreePanel}'s root node.
     *
     * @var {Object|Ext.tree.TreeNode}
     */
    root: {

        /**
         * Default to expanding the root node.
         *
         * @var {boolean}
         */
        expanded: true
    },

    /**
     * The base url that this component will use to execute REST calls.
     * Provided as part of the parent class configuration.
     *
     * @var {String}
     */
    /*'/rest/datawarehouse/search/history'*/
    url: null,

    /**
     * Default Constructor
     */
    initComponent: function () {

        this.url = XDMoD.REST.url + '/' + this.jobViewer.rest.warehouse + '/search/history';

        this.loaded = false;

        XDMoD.Module.JobViewer.SearchHistoryPanel.superclass.initComponent.call(this, arguments);
    }, // initComponent

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
        load: function() {
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
         * Fired when a node is clicked by a right mouse-button. Used to
         * whether or not a context menu is displayed.
         *
         * @param {Ext.tree.TreeNode} node
         * @param {Ext.EventObject} event
         */
        contextmenu: function (node, event) {
            var self = this;
            var exists = CCR.exists;

            var toggleSort = function (menuItem /*, clickEvent */) {
                this.jobViewer.fireEvent('update_tree_sort', menuItem.itemId);
            };

            if (exists(node)) {

                var dtype = node.attributes['dtype'];
                var text = node.attributes['text'];
                var value = node.attributes[dtype];

                var title;
                var items = [];
                var hasSearchTerms = false;
                if (node.attributes.searchterms && node.attributes.searchterms.params) {
                    hasSearchTerms = true;
                }

                switch (dtype) {
                    case 'realm':
                        title = 'Realm Actions';
                        items = [
                            {
                                xtype: 'menucheckitem',
                                text: 'Create Time',
                                itemId: 'age',
                                group: 'realm-actions-menu-sort',
                                handler: toggleSort,
                                scope: this,
                                checked: this.jobViewer.sortMode == 'age'
                            },
                            {
                                xtype: 'menucheckitem',
                                text: 'Name',
                                itemId: 'name',
                                group: 'realm-actions-menu-sort',
                                handler: toggleSort,
                                scope: this,
                                checked: this.jobViewer.sortMode == 'name'
                            },
                            {
                                xtype: 'menuseparator'
                            },
                            {
                                xtype: 'menutextitem',
                                html: '<span class="menu-title" style="margin-left: 26px;">Sort Options</span><br/>'
                            },
                            {
                                xtype: 'menuseparator'
                            },
                            {
                                text: 'Delete All Searches',
                                iconCls: 'delete',
                                id: 'job-viewer-search-history-context-realm-delete',
                                handler: function() {
                                    self.jobViewer.fireEvent('search_delete_by_realm', value);
                                }
                            }
                        ];
                        break;
                    case 'recordid':
                        title = 'Search Actions';
                        items.push({
                            text: 'Delete Search',
                            iconCls: 'delete_data',
                            id: 'job-viewer-search-history-context-record-delete',
                            handler: function() {
                                self.jobViewer.fireEvent('search_delete_by_node', node);
                            }
                        });
                        items.push(
                            {
                                id: 'job-viewer-search-history-context-edit-search',
                                text: 'Edit Search',
                                iconCls: 'edit_data',
                                disabled: !hasSearchTerms,
                                handler: function() {
                                    self.jobViewer.fireEvent('edit_search', node);
                                }
                            }
                        );
                        break;
                    case 'jobid':
                        break;
                    case 'infoid':
                        break;
                    case 'tsid':
                        /*title = 'Timeseries Actions';
                        items.push({text: 'Send to Chart', iconCls: 'add_data'});*/
                        break;
                }
                if (exists(title)) {
                    var menuTitle = '<span class="menu-title" style="margin-left: 26px;">' + title + '</span><br/>';

                    items.push('-');
                    items.push(menuTitle);
                    items.reverse();

                    var menu = new Ext.menu.Menu({
                        plain: true,
                        items: items,
                        listeners: {
                            hide: function() {
                                this.destroy();
                            }
                        }
                    });
                    menu.showAt(Ext.EventObject.getXY());
                    Ext.QuickTips.register({
                        target: 'job-viewer-search-history-context-edit-search',
                        text: hasSearchTerms ?
                            'Edit the selected searches parameters and or selected jobs.' :
                            'Edit Search is not currently supported for Metric Explorer Searches.'
                    });
                }

            }
        }, // contextmenu

        /**
         * Fired when a node is 'expanded'. Ignores the root node.
         *
         * @param {Ext.tree.TreeNode} node
         */
        expandnode: function (node) {
            if (node.getDepth() !== 0) {

            }
        }, // expandnode

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
                if (!this.expansionWaiting) this.jobViewer.loading = false;
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
