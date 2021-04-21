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

    /**
     * Initialize this component's contents.
     */
    initComponent: function () {
        let self = this;

        cytoscape.use(cytoscapeDagre);

        this.containerId = this.id + '_hc';

        this.store = new Ext.data.JsonStore({

            url: XDMoD.REST.url + '/etl/graph/pipelines/' + self.pipeline,

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
                            height: 'label',
                            width: 'label',
                            shape: 'rectangle'
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
                            'target-arrow-shape': 'triangle',
                            'line-color': '#00FF00',
                            'target-arrow-color': '#00FF00'
                        }
                    }
                ],
                layout: {
                    name: 'dagre',
                    /*@ts-ignore*/
                    fit: true,
                    /*@ts-ignore*/
                    rankDir: 'LR'
                }
            });

            this.store.load();
        }
    }
});
