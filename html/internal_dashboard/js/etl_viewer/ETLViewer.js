Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

/**
 *
 */
XDMoD.Admin.ETL.ETLViewer = Ext.extend(Ext.TabPanel, {
    frame: false,
    border: false,
    activeItem: 0,
    defaults: {
        tabCls: 'tab-strip'
    },

    listeners: {
        beforerender: function (tabPanel) {
            tabPanel.initialize(tabPanel);
        },

        activate: function () {
            if (!this.loadMask) {
                this.loadMask = new Ext.LoadMask(this.id);
            }

            let token = CCR.tokenize(document.location.hash);

            this.loadMask.hide();

            let selectionModel = this.viewerTreeTab.getSelectionModel();

            let path = this._getPath(token.raw);
            let isSelected = this.compareNodePath(this.currentNode, path) && selectionModel && CCR.exists(selectionModel.getSelectedNode());

            if (!isSelected) {
                this.viewerTreeTab.fireEvent('expand_node', path);
                return;
            }
        }
    },

    /**
     *
     * @param tabPanel
     */
    initialize: function (tabPanel) {
        this.viewerTreeTab = new XDMoD.Admin.ETL.ETLViewerTreeTab();

        tabPanel.add(this.viewerTreeTab);
    }, // initialize

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

            var results = [];

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
}); // XDMOD.Admin.ETL.ETLViewer
