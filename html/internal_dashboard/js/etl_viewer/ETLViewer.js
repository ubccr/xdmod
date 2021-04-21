Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

/**
 *
 */
XDMoD.Admin.ETL.ETLViewer = Ext.extend(Ext.Panel, {
    id: 'etl_viewer',
    layout: 'border',
    initComponent: function() {
        let self = this;
        this.tabPanel = new Ext.TabPanel({
            id: 'etl_viewer_tab_panel',
            region: 'center',
            border: false,
            activeItem: 0,
            defaults: {
                tabCls: 'tab-strip'
            }
        });
        Ext.apply(this, {
            items: [
                this.tabPanel
            ],
            tbar: {
                items: [
                    {
                        xtype: 'button',
                        text: 'Tree View',
                        cls: 'x-btn-text-icon',
                        icon: '',
                        handler: function() {
                            self.addTab('tree', {
                                etlViewer: self
                            });
                        }
                    }
                ]
            }
        });
        XDMoD.Admin.ETL.ETLViewer.superclass.initComponent.apply(this, arguments);
    },

    listeners: {
        add_tab: function (type, config) {
            this.addTab(type, config);
        }
    },

    addTab: function (type, config = {}) {
        let tab = null;
        switch (type) {
            case 'tree':
                tab = new XDMoD.Admin.ETL.ETLViewerTreeTab(config);
                break;
            case 'graph':
                tab = new XDMoD.Admin.ETL.GraphPanel(config);
                break;
        }

        if (tab !== null) {
            this.tabPanel.add(tab);
            this.tabPanel.setActiveTab(tab);
        }
    },

    /*listeners: {
        activate: function () {
            if (!this.loadMask) {
                this.loadMask = new Ext.LoadMask(this.id);
            }

            let token = CCR.tokenize(document.location.hash);

            this.loadMask.hide();

            let selectionModel = this.viewerTreeTab.tree.getSelectionModel();

            let path = this._getPath(token.raw);
            let isSelected = this.compareNodePath(this.currentNode, path) && selectionModel && CCR.exists(selectionModel.getSelectedNode());

            if (!isSelected) {
                this.viewerTreeTab.fireEvent('expand_node', path);
                return;
            }
        }
    },*/

    /**
     * Retrieve the 'path' values from the provided tree node or window hash.
     *
     * @param {String|Ext.tree.TreeNode} node
     * @returns {Array}
     * @private
     */
    _getPath: function (node, keys) {
        var exists = CCR.exists;
        var isType = CCR.isType;
        var results = [];
        if (isType(node, CCR.Types.Object)) {
            for (; exists(node); node = node.parentNode) {
                var attributes = node.attributes || [];
                var dtype = attributes['dtype'];
                var value = exists(dtype) ? attributes[dtype] : undefined;
                if (exists(value)) results.push({dtype: dtype, value: value});
            }
            return results.reverse();
        } else if (isType(node, CCR.Types.String)) {
            var token = CCR.tokenize(node);
            var params = token && token.params && token.params.split ? token.params.split('&') : [];
            for (var i = 0; i < params.length; i++) {
                var param = params[i].split('=');
                var key = param[0];
                var value = param[1];

                var keyIndex = !exists(keys) || keys.indexOf(key);
                if (keyIndex >= 0) {
                    results.push({dtype: key, value: value});
                }
            }
            return results;
        }
    }, // _getPath

    /**
     * Compare the given search history tree node with the provided path array.
     * The path encoding from the _getPath function.
     *
     * @param Ext.tree.TreeNode node
     * @param Array path
     * @returns boolean true if the path array matches the tree node, false otherwise
     */
    compareNodePath: function (node, path) {
        var i;
        var np;
        for (np = node, i = path.length - 1; np && np.attributes && np.attributes.dtype; np = np.parentNode, --i) {
            if (i < 0) {
                return false;
            }
            if (path[i].dtype !== np.attributes.dtype || path[i].value !== String(np.attributes[np.attributes.dtype])) {
                return false;
            }
        }
        return i === -1;
    },
}); // XDMOD.Admin.ETL.ETLViewer
