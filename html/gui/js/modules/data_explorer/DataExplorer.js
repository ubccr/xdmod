/*
 * Data Explorer
 *
 */
XDMoD.Module.DataExplorer = Ext.extend(XDMoD.PortalModule, {

    // PORTAL MODULE PROPERTIES ===============================================
    title: 'Data Explorer',
    module_id: 'data_explorer',


    // PORTAL MODULE TOOLBAR CONFIG ===========================================
    usesToolbar: true,

    toolbarItems: {
        exportMenu: {
            enable: true,
            config: {
                allowedExports: ['png', 'svg', 'csv', 'pdf']
            }
        },
        printButton: true
    },


    /*
     */
    initComponent: function () {
        var combo = {
            xtype: 'uxgroupcombo',
            groupTextTpl: '<span style="font-weight: bold;">{gvalue}</span>',
            store: new Ext.data.GroupingStore({
                restful: true,
                url: XDMoD.REST.url + '/warehouse/search/freeform',
                root: 'results',
                groupField: 'Domain',
                reader: new Ext.data.JsonReader({
                    root: 'results',
                    idParameter: 'cid',
                    fields: ['cid', 'Domain', 'content']
                }),
                fields: [
                    { name: 'cid', type: 'int' },
                    { name: 'Domain', type: 'string' },
                    { name: 'content', type: 'string' }
                ]
            }),
            mode: 'remote',
            typeAhead: false,
            autoSelect: false,
            triggerAction: 'all',
            valueField: 'cid',
            hideTrigger: true,
            displayField: 'content'
        };

        var config = {
            realm: CCR.xdmod.ui.rawDataAllowedRealms[0],
            start_date: '2020-10-01',
            end_date: '2020-11-13',
            params: {},
            page_size: 20
        };

        var lastPageOffset = 0;
        var jobStore = new Ext.data.JsonStore({
            url: XDMoD.REST.url + '/warehouse/search/jobs',
            restful: true,
            root: 'results',
            totalProperty: 'totalCount',
            baseParams: {
                start_date: config.start_date,
                end_date: config.end_date,
                realm: config.realm,
                limit: config.page_size,
                start: 0,
                params: JSON.stringify(config.params)
            },
            fields: [
                { name: 'dtype', mapping: 'dtype', type: 'string' },
                { name: 'resource', mapping: 'resource', type: 'string' },
                { name: 'name', mapping: 'name', type: 'string' },
                { name: 'jobid', mapping: 'jobid', type: 'int' },
                { name: 'local_job_id', mapping: 'local_job_id', type: 'int' },
                { name: 'text', mapping: 'text', type: 'string' },
                'job_name',
                'cpu_user',
                'start_time_ts',
                'end_time_ts'
            ],
            listeners: {
                load: function (store, records, options) {
                    lastPageOffset = options.params.start;
                }
            }
        });

        var pageOffset = 0;
        var pageSize = 10;

        var itemHeight = 38;

        // Custom rendering Template for the View
        var resultTpl = new Ext.XTemplate(
            '<div class="search-top-pad" style="padding-top: {[38 * this.getPageOffset()]}px"></div><tpl for=".">',
            '<div class="search-item-wrap">',
            //'<div class="search-item-wrap" id="{dtype}{jobid}" style="padding-top: {[xindex === 1 ? 38 * this.getPageOffset(): 0]}px;">',
            '<div class="search-item">',
                '<div>{resource}</div><div>{local_job_id}</div><div>{job_name}</div><br />
                '{[moment(1000 * values.start_time_ts).format("Y-MM-DD HH:mm:ss z")]}',
            '</div></div></tpl><div class="search-bottom-pad" style="padding-bottom: {[this.getBottomPad()]}px;"></div>',
            {
                getPageOffset: function () {
                    return pageOffset;
                },
                getBottomPad: function () {
                    var padpx = 0;
                    if (jobStore.getTotalCount() > pageSize) {
                        padpx = itemHeight * ((jobStore.getTotalCount() - pageOffset) + pageSize);
                    }
                    return padpx;
                }
            }
        );

        var jobLoader = new Ext.util.DelayedTask();

        var panel = new Ext.Panel({
            region: 'west',
            width: 350,
            autoScroll: true,

            items: new Ext.DataView({
                tpl: resultTpl,
                store: jobStore,
                autoHeight: true,
                multiSelect: true,
                selectedClass: 'search-item-over',
                overClass: 'search-item-run',
                listeners: {
                    click: function (e) {
                        console.log(e);
                    }
                },
                itemSelector: 'div.search-item-wrap'
            }),
            listeners: {
                render: function (p) {
                    p.body.on('scroll', function (evt) {
                        // console.log('Top most visible entry', Math.floor(evt.target.scrollTop / itemHeight));
                        // console.log('Bottom   visible entry', Math.floor((this.getInnerHeight() + evt.target.scrollTop) / itemHeight));

                        var topviz = Math.floor(evt.target.scrollTop / itemHeight);
                        var bottomviz = Math.floor((this.getInnerHeight() + evt.target.scrollTop) / itemHeight);

                        if (topviz < pageOffset || bottomviz > (pageOffset + pageSize)) {

                            pageOffset = Math.max(0, topviz - 10);
                            console.log('** Do load');
                            jobLoader.delay(50, function () {
                                console.log('LOADING....');
                                jobStore.load({
                                    params: {
                                        start: pageOffset, limit: pageSize
                                    }
                                });
                            });
                        }

                        var el = p.el.child('.search-top-pad');
                        console.log('Height:', el.getHeight(), ' scrollTop ', evt.target.scrollTop);
                        if (evt.target.scrollTop < el.getHeight()) {
                            var pos = Math.min((el.getHeight() - evt.target.scrollTop) / 2, this.getInnerHeight() / 2) + evt.target.scrollTop;
                            el.setStyle('background-position', 'top ' + pos + 'px left 50%');
                        }
                        var el1 = p.el.child('.search-bottom-pad');
                        var eltop = el.getHeight() + (itemHeight * pageSize);
                        if (this.getInnerHeight() + evt.target.scrollTop > eltop) {
                            var visible = (evt.target.scrollTop + this.getInnerHeight()) - eltop;
                            console.log("Visible section = ", visible);
                            if (visible < this.getInnerHeight()) {
                                var pos = visible / 2;
                            } else {
                                var pos = visible - (this.getInnerHeight() / 2);
                            }
                            el1.setStyle('background-position', 'top ' + pos + 'px left 50%');
                        }

                        /*
                        if (evt.target.scrollTop < pageOffset * itemHeight) {
                            p.el.mask('Loading');
                            console.log('scolled to top');
                            pageOffset -= 10;
                            jobStore.load({
                                params: {
                                    start: pageOffset, limit: pageSize
                                }
                            });
                        }

                        if (this.getInnerHeight() + evt.target.scrollTop >= evt.target.scrollHeight) {
                            p.el.mask('Loading');
                            console.log('scolled to bottom', this.getInnerHeight() + evt.target.scrollTop, (pageOffset + pageSize - 20) * itemHeight);
                            pageOffset += 10;
                            jobStore.load({params: {start: pageOffset, limit: pageSize}});
                        }
                        console.log(this.getInnerHeight(), evt.target.scrollTop, evt.target.scrollHeight, evt.target.scrollWidth);
                        */
                    }, p);
                },
                bodyresize: function () {
                    jobLoader.delay(10, function () {
                        console.log('after laout');
                        // Set page size so that there are 10 more items than will fit on the page
                        pageSize = 20 + Math.floor(this.getHeight() / itemHeight);
                        jobStore.load({params: {start: pageOffset, limit: pageSize}});
                    }, this)
                }
            },
            tbar: [
                'Search: ', ' ', combo
            ]
        });

        jobStore.on('load', function () {
            panel.el.unmask();
        }, this);

        this.items = [
            panel,
            {
                xtype: 'panel',
                region: 'center'
            }
        ];

        XDMoD.Module.DataExplorer.superclass.initComponent.apply(this, arguments);
    }
});
