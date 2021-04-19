Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.ETLViewerTreeTab = Ext.extend(Ext.Panel, {
    title: 'ETL Tree View',
    layout: 'border',
    closable: true,
    height: '100%',
    width: '100%',
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
                        icon: '',
                        cls: 'x-btn-text-icon'
                    },
                    {
                        xtype: 'button',
                        text: 'Collapse All',
                        icon: '',
                        cls: 'x-btn-text-icon'
                    },
                    '-',
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

            /**
             * We override the parent's `updateColumnWidths` function so that we can add a call to `fitColumns` and thus
             * have an autoExpanded column.
             */
            updateColumnWidths : function() {
                var fit = arguments.length > 0 ? arguments[0] : true;
                var cols = this.columns,
                    colCount = cols.length,
                    groups = this.outerCt.query('colgroup'),
                    groupCount = groups.length,
                    c, g, i, j;

                for(i = 0; i<colCount; i++) {
                    c = cols[i];
                    for(j = 0; j<groupCount; j++) {
                        g = groups[j];
                        g.childNodes[i].style.width = (c.hidden ? 0 : c.width) + 'px';
                    }
                }

                for(i = 0, groups = this.innerHd.query('td'), len = groups.length; i<len; i++) {
                    c = Ext.fly(groups[i]);
                    if(cols[i] && cols[i].hidden) {
                        c.addClass('x-treegrid-hd-hidden');
                    }
                    else {
                        c.removeClass('x-treegrid-hd-hidden');
                    }
                }

                var tcw = this.getTotalColumnWidth();
                Ext.fly(this.innerHd.dom.firstChild).setWidth(tcw + (this.scrollOffset || 0));
                this.outerCt.select('table').setWidth(tcw);
                this.syncHeaderScroll();
                if (fit) {
                    this.fitColumns();
                }

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
                        this.updateColumnWidths(false);
                    }

                }
            }
        });

        Ext.apply(this, {
            items: [
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
