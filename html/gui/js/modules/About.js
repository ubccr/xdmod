XDMoD.Module.About = Ext.extend(XDMoD.PortalModule, {

    title: 'About',
    module_id: 'about_xdmod',
    initComponent: function () {
        var basePath = '#main_tab_panel:' + this.module_id + '?';
        var lastViewed = 'XDMoD';
        var contentPanel = new Ext.Panel({
            region: 'center',
            preventBodyReset: true, // Don't override default css styles in this panel
            autoScroll: true,
            bodyCssClass: "xdmod-aboutus"
        });

        var treeNodeClick = function(node) {
            Ext.History.add(basePath + encodeURIComponent(node.text));
        };

        var rootNode = {
            expanded: true,
            children: [
                {
                    text: 'XDMoD',
                    icon: '/gui/images/info.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Open XDMoD',
                    icon: '/gui/images/info.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'SUPReMM',
                    icon: '/gui/images/info.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Federated',
                    icon: '/gui/images/menu.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Roadmap',
                    icon: '/gui/images/lorry.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Team',
                    icon: '/gui/images/person.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Publications',
                    icon: '/gui/images/user_manual_16.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Presentations',
                    icon: '/gui/images/user_manual_16.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Links',
                    icon: '/gui/images/arrow_right.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                },
                {
                    text: 'Release Notes',
                    icon: '/gui/images/user_manual_16.png',
                    leaf: true,
                    listeners: {
                        click: treeNodeClick
                    }  // listeners
                }
            ]  // children
        };  //rootNode


        var westPanel = {
            region: 'west',
            xtype: 'treepanel',
            id: 'treepanel',
            root: rootNode,
            split: true,
            rootVisible: false,
            collapsible: true,
            height: 500,
            width: 200,
            useArrows: true
        };

        var mainArea = new Ext.Panel({
            layout: 'border',
            region: 'center',
            items: [
                westPanel,
                contentPanel
            ]
        });//mainArea

        this.addListener('activate', function() {
            var item = decodeURIComponent(CCR.tokenize(Ext.History.getToken()).params);
            var items = {
                XDMoD: '/about/xdmod.php',
                'Open XDMoD': '/about/openxd.html',
                SUPReMM: '/about/supremm.html',
                Federated: '/about/federated.php',
                Roadmap: '/about/roadmap.php',
                Team: '/about/team.html',
                Publications: '/about/publications.html',
                Presentations: '/about/presentations.html',
                Links: '/about/links.html',
                'Release Notes': '/about/release_notes/' + (CCR.xdmod.features.xsede ? 'xsede.html' : 'xdmod.html')
            };
            if (!item || !items[item]) {
                item = lastViewed;
            }
            Ext.Ajax.request({
                url: items[item],
                success: function (response) {
                    contentPanel.body.update(response.responseText);
                    lastViewed = item;
                    var treeNode = Ext.getCmp('treepanel').getRootNode().findChild('text', item);
                    if (treeNode) {
                        treeNode.select();
                    }
                    // else no nothing - the tree node may not exist if the tree has not been rendered
                }
            });
        });
        Ext.apply(this, {
            items: [
                mainArea
            ]
        });//Ext.apply
        XDMoD.Module.About.superclass.initComponent.apply(this, arguments);
    } //initComponent
}); //XDMoD.Module.NewModule
