/* global cytoscape,  cytoscapeDagre */
Ext.namespace('XDMoD', 'XDMoD.Admin', 'XDMoD.Admin.ETL');

XDMoD.Admin.ETL.GraphPanel = Ext.extend(Ext.Panel, {
    title: 'Default Graph View',
    layout: 'fit',
    closable: true,

    // This GraphPanel's instance of Cytoscape
    cy: null,

    // The JsonStore for this instance of GraphPanel.
    store: null,

    // The pipeline that this Graph is meant to display.
    // *NOTE: supplied by the code instantiating this panel.
    pipeline: null,

    action: null,

    url: XDMoD.REST.url + '/etl/graph/pipelines/',

    /**
     * Initialize this component's contents.
     */
    initComponent: function () {
        let self = this;

        cytoscape.use(cytoscapeDagre);

        this.containerId = this.id + '_hc';
        if (this.pipeline) {
           this.url += self.pipeline;
        }
        if (this.action) {
            this.url += `/actions/${this.action}`;
        }

        this.store = new Ext.data.JsonStore({

            url: this.url,

            autoDestroy: false,

            root: 'data',
            fields: ['group', 'data'],
            successProperty: 'success',
            messageProperty: 'message',
            listeners: {
                load: function (store, records, options) {
                    let data = [];
                    records.forEach(function (elem) {
                        data.push(elem.json);
                    });
                    self.cy.add(data);
                    self.cy.layout({
                        name: 'dagre',
                        /*@ts-ignore*/
                        fit: true,
                        /*@ts-ignore*/
                        rankDir: 'LR'
                    }).run();
                },
                loadexception: function () {
                    console.log('loadexception');
                },
                exception: function () {
                    console.log('error');
                }
            }
        });


        Ext.apply(this, {
            items: [
                {
                    xtype: 'container',
                    id: this.containerId,
                    listeners: {
                        render: function () {
                            self.fireEvent('renderGraph', self);
                        }
                    }
                }
            ]
        });

        XDMoD.Admin.ETL.GraphPanel.superclass.initComponent.apply(this, arguments);
    },

    listeners: {
        renderGraph: function () {
            this.cy = cytoscape({
                container: document.getElementById(this.containerId),
                style: [
                    {
                        selector: 'node',
                        css: {
                            content: 'data(name)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            shape: 'round-rectangle',
                            'padding-left': '5px',
                            height: 'label',
                            width: 'label',
                            'border-width': '2px',
                            'border-color': '#000',
                            'background-color': '#262626',
                            'background-opacity': 0.3
                        }
                    },
                    {
                        selector: ':parent',
                        css: {
                            'text-valign': 'top',
                            'text-halign': 'center',
                            'background-color': '#FFF',
                            shape: 'round-rectangle',
                            'font-size': '10px',
                            'padding-top': '5px'
                        }
                    },
                    {
                        selector: 'edge',
                        css: {
                            'curve-style': 'bezier',
                            'arrow-scale': 0.66,
                            'target-arrow-shape': 'triangle',
                            'line-color': '#0099ff',
                            'line-opacity': 0.66,
                            'target-arrow-color': '#000'
                        }
                    }
                ],
                layout: {
                    name: 'dagre',
                    /*@ts-ignore*/
                    fit: true,
                    /*@ts-ignore*/
                    rankDir: 'LR',
                    nodeSep: 20
                }
            });

            this.store.load();
        }
    }
});
