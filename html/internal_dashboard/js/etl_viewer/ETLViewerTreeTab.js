Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.ETLViewerTreeTab = Ext.extend(Ext.Panel, {
    title: 'ETL Tree View',
    layout: 'border',
    initComponent: function () {
        var self = this;
        this.tree = new Ext.ux.tree.TreeGrid({
            region: 'center',
            closable: false,
            updateHistory: true,
            autoScroll: true,
            enableDD: true,
            autoExpandColumn: 'value',
            tbar: {
                items: [
                    {
                        xtype: 'button',
                        text: 'Expand All',
                        iton: '',
                        cls: 'x-btn-text-icon'
                    },
                    {
                        xtype: 'button',
                        text: 'Collapse All',
                        icon: '',
                        cls: 'x-btn-text-icon'
                    },
                    {
                        xtype: 'textfield',
                        emptyText: 'Enter Search Term Here'
                    }
                ]
            },
            dataUrl: XDMoD.REST.url  + '/etl/pipelines/actions',
            keys: [
                {
                    key: Ext.EventObject.SHIFT,
                    fn: function() {
                        self.shiftClicked = !self.shiftClicked;
                    }
                }
            ],
            listeners: {
                expandnode: function(node) {
                    if (CCR.xdmod.shiftKey === true && node.hasChildNodes()) {
                        node.expandChildNodes(true);
                    }
                },
                collapsenode: function(node) {
                    if (CCR.xdmod.shiftKey === true && node.hasChildNodes()) {
                        node.collapseChildNodes(true);
                    }
                }
            },
            columns: [
                {
                    header: 'Name',
                    dataIndex: 'name',
                    width: 500
                },
                {
                    header: 'Value',
                    dataIndex: 'value',
                    width: 500
                }
            ],
            updateColumnWidths : function() {
                Ext.ux.tree.TreeGrid.updateColumnWidths();
                this.fitColumns();
            },

            /**
             * Ext.ux.TreeGrid doesn't honor `autoExpandColumn` by default as it's based on TreePanel and not GridPanel.
             * This function is what allows the column identified by the `autoExpandColumn` property to expand into
             * whatever space is left over in the this components container.
             */
            fitColumns: function() {
                var totalColWidth = this.getTotalColumnWidth(),
                    outerCtWidth = this.outerCt.getWidth(),
                    groups = this.outerCt.query('colgroup'),
                    extraWidth = outerCtWidth - totalColWidth - Ext.getScrollBarWidth(),
                    i, j;

                for ( i = 0; i < this.columns.length; i++) {
                    var column = this.columns[i];
                    if (column.dataIndex === this.autoExpandColumn && extraWidth !== 0) {
                        column.width = extraWidth;
                        for ( j = 0; j < groups.length; j++) {
                            g = groups[j];
                            g.childNodes[i].style.width = extraWidth + 'px';
                        }
                    }
                    this.updateColumnWidths();
                }
            }
        });

        Ext.apply(this, {
            items: [
                this.searchPanel,
                this.tree
            ]
        });
        XDMoD.Admin.ETL.ETLViewerTreeTab.superclass.initComponent.apply(this, arguments);
    },
    listeners: {
        expand_node: function (path) {
            this.tree.fireEvent('expand_node', path);
        }
    }
});
