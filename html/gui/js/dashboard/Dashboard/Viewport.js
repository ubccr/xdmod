/**
 * Internal operations dashboard viewport.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Dashboard');

XDMoD.Dashboard.Viewport = Ext.extend(Ext.Viewport, {
    id:'dashboard_viewport',
    layout: 'border',
    firstload:true,
    constructor: function (config) {
        config = config || {};
        
        var active_tab="top-tab-panel";
        var i_active_tab=0;
        //var tabpanel=this.getComponent("dashboard-tabpanel");
        if(document.location.hash!==""){
            var token = XDMoD.Dashboard.tokenize(document.location.hash);
            if (token !== undefined && "root" in token) {
                active_tab=token.root;

            }
        }
        var i;
        for(i=0;i<config.items.length;i++){
            if(config.items[i].id===active_tab){
                i_active_tab=i;
            }
        }

        this.items = [
            {
                id: 'dashboard-header',
                frame: false,
                border: false,
                region: 'north',
                height: 40,
                bodyStyle: {
                    backgroundColor: '#fef5e9'
                },
                html: '<table><tr>' +
                    '<td style="width:300px;"><img src="images/masthead.png"></td>' +
                    '<td>Welcome, <b>' + Ext.util.Format.htmlEncode(dashboard_user_full_name) + '</b>' +
                    ' [<a href="javascript:void(0)" onClick="return false;" id="header-logout">Logout</a>]</td>' +
                    '</tr></table>'
            },
            {
                id: 'dashboard-tabpanel',
                xtype: 'tabpanel',
                activeTab: i_active_tab,
                frame: false,
                border: false,
                region: 'center',
                defaults: {
                    tabCls: 'tab-strip'
                },
                items: config.items,
                listeners: {
                    'tabchange': {
                        fn: function (tabpanel,tab) {
                            var hist=tab.id;
                            if(document.location.hash!==""){
                                var token = XDMoD.Dashboard.tokenize(document.location.hash);
                                if (token !== undefined && "root" in token && "tab" in token && "params" in token) {
                                    if(token.root===tab.id){
                                        if(token.tab!==""){
                                            hist+=CCR.xdmod.ui.tokenDelimiter+token.tab;
                                        }
                                        if(token.params!==""){
                                            hist+="?"+token.params;
                                        }
                                    }
                                }
                            }
                            
                            Ext.History.add(hist);
                        },
                        scope: this
                    }
                }
            }
        ];

        delete config.items;

        Ext.apply(config, {
            listeners: {
                'afterrender': {
                    fn: function () {
                        var logoutLink = Ext.get('header-logout');
                        logoutLink.on('click', this.logout, this);
                    },
                    scope: this
                }
            }
        });

        XDMoD.Dashboard.Viewport.superclass.constructor.call(this, config);
    },

    logout: function () {
        actionLogout();
    }
});
XDMoD.Dashboard.tokenize=function(hash) {
    if ( hash !== undefined &&
         hash !== null &&
         typeof hash === 'string' &&
         hash.length > 0) {

        function first(value, delimiter) {
            if (value !== null &&
                value !== undefined &&
                typeof value === 'string' ) {
                    if (value.indexOf(delimiter) >= 0) {
                        return value.split(delimiter)[0];
                    } else {
                        return value;
                    }
                }
            return value;
        };

        function second(value, delimiter) {
            if (value !== null &&
                value !== undefined &&
                typeof value === 'string' ) {
                    if (value.indexOf(delimiter) >= 0) {
                        return value.split(delimiter)[1];
                    } else {
                        return value;
                    }
                }
            return value;
        };

        var tabDelimIndex = hash.indexOf(CCR.xdmod.ui.tokenDelimiter);
        var paramDelimIndex = hash.indexOf('?');

        var result = {
            raw: hash,
            content: second(hash, '#')
        };
        if (tabDelimIndex >= 0 && paramDelimIndex >= 0) {

            // We have a well formed hash: parent:child?name=value...
            var root = first(
                first(result.content, CCR.xdmod.ui.tokenDelimiter)
                ,'?'
            );

            var tab = first(
                second(result.content, CCR.xdmod.ui.tokenDelimiter),
                '?'
            );

            var params = second(result.content, '?');

            if (params === result.content) {
                params = '';
            }

            result['root'] = root;
            result['tab'] = tab;
            result['params'] = params;
        } else if (tabDelimIndex < 0 && paramDelimIndex < 0) {

            // We have a hash that looks like: name=value
            result['root'] = result.content;
            result['tab'] = '';
            result['params'] = result.content;
        } else if (tabDelimIndex >= 0 && paramDelimIndex < 0) {

            // We have a hash that looks like: parent:child
            root = first(result.content, CCR.xdmod.ui.tokenDelimiter);
            tab = second(result.content, CCR.xdmod.ui.tokenDelimiter);
            params = '';

            result['root'] = root;
            result['tab'] = tab;
            result['params'] = params;
        } else if (tabDelimIndex < 0 && paramDelimIndex >= 0) {

            // We have a hash that looks like: child?name=value
            root = first(result.content, '?');
            tab = '';
            params = second(result.content, '?');

            result['root'] = root;
            result['tab'] = tab;
            result['params'] = params;
        }

        return result;
    }
    return {};
}
XDMoD.Dashboard.getParameterByName=function(name, source) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&#]" + name + "=([^&#]*)"),
        results = regex.exec(source);
    return results === null
        ? ""
        : decodeURIComponent(results[1].replace(/\+/g, " "));
}