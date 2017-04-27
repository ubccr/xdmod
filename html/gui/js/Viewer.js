/*
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07 (version 1)
 *
 * @author Ryan Gentner
 * @date 2013-Jun-23 (version 2)
 *
 *
 * This class contains functionality for managing the main tabs of the xdmod portal user interface.
 *
 */

xdmodviewer = function() {

  return {

    init: function() {

        Ext.History.init();
        Ext.QuickTips.init();

        Ext.apply(Ext.QuickTips.getQuickTip(), {
          showDelay: 400,
          dismissDelay: 1000000
        });

        var viewer = new CCR.xdmod.ui.Viewer();
        viewer.render('viewer');

      } //init

  };

}(); //xdmodviewer

// ===========================================================================

CCR.xdmod.ui.Viewer = function(config) {

  CCR.xdmod.ui.Viewer.superclass.constructor.call(this, config);

}; //CCR.xdmod.ui.Viewer

// ===========================================================================

Ext.apply(CCR.xdmod.ui.Viewer, {

  getViewer: function() {

    return CCR.xdmod.ui.Viewer.viewerInstance;

  }, //getViewer

  // ------------------------------------------------------------------

  refreshView: function(tab_id) {

    var viewer = CCR.xdmod.ui.Viewer.getViewer();

    if (viewer.el) {
      viewer.el.mask('Loading...');
    }

    var tree = Ext.getCmp('tree_tg_usage');

    if (!tree) {
      if (viewer.el) {
        viewer.el.unmask();
      }
      return;
    }

    var nodeToSelect = tree.getSelectionModel().getSelectedNode();

    if (!nodeToSelect) {
      if (viewer.el) {
        viewer.el.unmask();
      }
      return;
    }

    tree.getSelectionModel().unselect(nodeToSelect, true);
    tree.getSelectionModel().select(nodeToSelect);

  }, //refreshView

  // ------------------------------------------------------------------

  gotoChart: function(sub_role_category, menu_id, realm, id, durationSelectorId, chartToolbarSerialized) {

      var viewer = CCR.xdmod.ui.Viewer.getViewer();

      if (viewer.el) {
        viewer.el.mask('Loading...');
      }

      var tabPanel = Ext.getCmp('main_tab_panel');

      if (!tabPanel) {
        if (viewer.el) {
          viewer.el.unmask();
        }
        return;
      }

      tabPanel.setActiveTab('tg_usage');

      var tree = Ext.getCmp('tree_tg_usage');

      if (!tree) {
        if (viewer.el) {
          viewer.el.unmask();
        }
        return;
      }

      var root = tree.getRootNode();

      tree.expandPath(root.getPath(), null, function(success, node) {

        if (!success) {
          if (viewer.el) {
            viewer.el.unmask();
          }
          return;
        }

        var menuNode = node.findChild('id', menu_id);

        tree.expandPath(menuNode.getPath(), null, function(success2, node2) {

          if (!success2) {
            if (viewer.el) {
              viewer.el.unmask();
            }
            return;
          }

          var roleCategorySelector = Ext.getCmp('role_category_selector_tg_usage');

          if (roleCategorySelector) {
            roleCategorySelector.set(sub_role_category);
          }

          var durationSelector = Ext.getCmp('duration_selector_tg_usage');

          if (durationSelector) {
            var sourceDurationSelector = Ext.getCmp(durationSelectorId);
            var durationSelectorSerialized = sourceDurationSelector.serialize(true);
            durationSelector.unserialize(durationSelectorSerialized);
          }

          var nodeToSelect = node2.findChild('id', id);

          if (!nodeToSelect) {
            if (viewer.el) {
              viewer.el.unmask();
            }
            return;
          }

          if (tree.getSelectionModel().isSelected(nodeToSelect)) {
            tree.getSelectionModel().unselect(nodeToSelect, true);
          }

          nodeToSelect.attributes.chartSettings = chartToolbarSerialized.replace(/`/g, '"');
          tree.getSelectionModel().select(nodeToSelect);

        }); //tree.expandPath(menuNode.getPath(),...

      }); //tree.expandPath(root.getPath(),...

    } //gotoChart

}); //Ext.apply(CCR.xdmod.ui.Viewer

// ===========================================================================

Ext.extend(CCR.xdmod.ui.Viewer, Ext.Viewport, {

  initComponent: function() {
    var self = this;

    var viewStore = new Ext.data.JsonStore({

      url: 'controllers/user_interface.php',
      autoDestroy: true,
      autoLoad: false,
      root: 'data',
      successProperty: 'success',
      messageProperty: 'message',
      totalProperty: 'totalCount',

      fields: [
        'tabs'
      ],

      baseParams: {
        operation: 'get_tabs',
        public_user: CCR.xdmod.publicUser
      }
    }); //viewStore

    // ---------------------------------------------------------

    viewStore.on('exception', function(dp, type, action, opt, response, arg) {

      if (response.success == false) {

        Ext.MessageBox.alert("Error", response.message);

      }

    }, this);

    // ---------------------------------------------------------

    var tabPanel = new Ext.TabPanel({

      id: 'main_tab_panel',
      frame: false,
      border: false,
      activeTab: 0,
      region: 'center',
      enableTabScroll: true,
      defaults: {
        tabCls: 'tab-strip'
      },

      listeners: {

        'tabchange': function(tabPanel, tab) {
            var hasActiveTab = CCR.xdmod.ui.activeTab !== undefined &&
              CCR.xdmod.ui.activeTab !== null &&
              CCR.xdmod.ui.activeTab.id !== undefined &&
              CCR.xdmod.ui.activeTab.id !== null;
            var hasTab = tab !== undefined &&
              tab !== null &&
              tab.id !== undefined &&
              tab.id !== null;

            var token = null;
            if (!hasActiveTab && hasTab) {

              CCR.xdmod.ui.activeTab = tab;

              XDMoD.TrackEvent("Tab Change", tab.title);

              token = tabPanel.id + CCR.xdmod.ui.tokenDelimiter + tab.id;
              Ext.History.add(token);
            } else if (hasActiveTab && hasTab && tab.id !== CCR.xdmod.ui.activeTab.id) {
              CCR.xdmod.ui.activeTab = tab;

              XDMoD.TrackEvent("Tab Change", tab.title);

              token = tabPanel.id + CCR.xdmod.ui.tokenDelimiter + tab.id;
              Ext.History.add(token);
            }
          } //tabchange

      } //listeners

    }); //tabPanel

    // ---------------------------------------------------------

    // Handle this change event in order to restore the UI to the appropriate history state
    Ext.History.on('change', function(token) {

      if (token) {

        //Ext.menu.MenuMgr.hideAll();
        var tokenType = typeof token;
        var parts = tokenType === 'string' ? CCR.tokenize(token) : token;
        if (!parts) {
          return;
        }

        var root = CCR.exists(parts.root) ? parts.root : 'main_tab_panel';
        var tabPanel = Ext.getCmp(root);

        if (!tabPanel){
            tabPanel = Ext.getCmp('main_tab_panel');
        }

        var tabId = parts.tab ? parts.tab : 'tg_summary';

        if (tabPanel.items.keys.indexOf(tabId) === -1){
            tabId = 'tg_summary';
        }

        var currentlyActive = tabPanel ? tabPanel.getActiveTab() : undefined;
        var currentlyActiveId = currentlyActive ? currentlyActive.id : undefined;

        // IF: we're changing tabs then go ahead and get on with it...
        if (tabId !== currentlyActiveId && tabPanel) {
          tabPanel.show();
          tabPanel.suspendEvents(false);
          tabPanel.setActiveTab(tabId);
          tabPanel.resumeEvents();
        } else {
          // ELSE: we have to force the issue ( because activate won't be triggered. ).
          var currentlyActiveTab = Ext.getCmp(tabId);
          if (currentlyActiveTab) {
            currentlyActiveTab.fireEvent('activate', currentlyActiveTab);
          }
        }

        var akInstanceId = self.getParameterByName('ak_instance', document.location.hash);
        if (akInstanceId !== null && akInstanceId !== undefined && akInstanceId.length > 0) {
          var akWindow = new XDMoD.AppKernel.InstanceWindow({
            instanceId: akInstanceId
          });
          var outputDataPanel = akWindow.find('title', 'Output Data');
          if (outputDataPanel.length > 0) {
            var newUrl = 'controllers/arr.php';
            outputDataPanel[0].store.proxy.api.read.url = newUrl;
            outputDataPanel[0].store.proxy.api.read.method = 'POST';
          }
          akWindow.show();
        }
      } //if (token)

    }); //Ext.History.on('change',…

    // ---------------------------------------------------------

    var userToolbar = [];

    // ---------------------------------------------------------

    var additionalWelcomeDetails = (CCR.xdmod.ui.isDeveloper == true) ? '<span style="color: #6e30fa">[Developer]</span>' : '';

    // ---------------------------------------------------------

    var welcome_message = "";

    if (CCR.xdmod.publicUser) {
      welcome_message = 'Hello, <b><a id="sign_in_link" href="javascript:CCR.xdmod.ui.actionLogin()">Sign In</a></b> to view personalized information.';
    } else {
      welcome_message = 'Hello, <b id="welcome_message">' + Ext.util.Format.htmlEncode(CCR.xdmod.ui.fullName) + '</b> ' + additionalWelcomeDetails + ' (<a href="javascript:CCR.xdmod.ui.actionLogout()" id="logout_link">logout</a>)';
      if (CCR.xdmod.ui.isManager) {
        userToolbar.push(XDMoD.GlobalToolbar.Dashboard);
      }
      userToolbar.push(XDMoD.GlobalToolbar.Profile);
    }

    var tbItems = [
      XDMoD.GlobalToolbar.Logo, {
        xtype: 'tbtext',
        text: welcome_message
      },
      '->',
      XDMoD.GlobalToolbar.CustomCenterLogo
    ];
    if (userToolbar.length > 0) {
      tbItems.push(userToolbar);
    }
    tbItems.push({
      xtype: 'buttongroup',

      items: [
        XDMoD.GlobalToolbar.About(tabPanel),
        XDMoD.GlobalToolbar.Roadmap,
        XDMoD.GlobalToolbar.Contact(),
        XDMoD.GlobalToolbar.Help(tabPanel)
      ]
    });
    var tb = new Ext.Toolbar({
      region: 'center',
      items: tbItems
    }); //Ext.Toolbar

    // ---------------------------------------------------------

    var mainPanel = new Ext.Panel({

      layout: 'border',
      tbar: tb,
      items: [tabPanel]

    }); //mainPanel

    // ---------------------------------------------------------

    Ext.apply(this, {

      id: 'xdmod_viewer',
      layout: 'fit',
      items: [mainPanel]

    }); //Ext.apply(this

    // ---------------------------------------------------------

    CCR.xdmod.ui.Viewer.superclass.initComponent.apply(this, arguments);

    // ---------------------------------------------------------

    mainPanel.on('render', function() {
      if (mainPanel.el) {
        mainPanel.el.mask('Loading...');
      }
      viewStore.load();

      viewStore.on('load', function(store) {

        if (mainPanel.el) {
          mainPanel.el.mask('Loading...');
        }
        if (store.getCount() <= 0) {
          return;
        }

        var tabs = Ext.util.JSON.decode(store.getAt(0).get('tabs'));

        // DEFINE: the token that we came in with.
        var mainTabToken = 'main_tab_panel';
          var token = CCR.tokenize(document.location.hash);
        var tabToken;

        for (var i = 0; i < tabs.length; i++) {
            var tab = tabs[i];
            tab.mainTabToken = mainTabToken;
            tab.id = tab.tab;

            var tabInstance = CCR.getInstance(tab.javascriptReference, tab.javascriptClass, tab);
            if (!tabInstance) {
                continue;
            }
            tabPanel.add(tabInstance);
            if (tab.isDefault) {
                tabToken = tabToken || mainTabToken + CCR.xdmod.ui.tokenDelimiter + tab.tab;
            }
        }

        if (mainPanel.el) {
          mainPanel.el.unmask();
        }

        //Conditionally present the profile if an e-mail address has not been set
        xsedeProfilePrompt();

          // The login dialog is presented if the user is not logged in
          // and the location requests an unavailable tab.
          if (CCR.xdmod.publicUser && token.tab) {
              var match = tabPanel.find('id', token.tab);
              if (match.length === 0) {
                  CCR.xdmod.ui.actionLogin();
              }
          }

        var hasToken = token && token.content && token.content.length > 2;
        var hasTabToken = tabToken && tabToken.length > 2;
        if (hasToken) {
          Ext.History.fireEvent('change', token);
        } else if (hasTabToken) {
          Ext.History.fireEvent('change', tabToken);
        } else {
          /* TODO: Make this pull from a config somewhere*/
          Ext.History.fireEvent('change', mainTabToken + CCR.xdmod.ui.tokenDelimiter + 'tg_summary');
        }

      }); ////viewStore.on('load',...

    }, this, {
      single: true
    }); //mainPanel.on('render',…

    CCR.xdmod.ui.Viewer.viewerInstance = this;

  }, //initComponent
  getParameterByName: function(name, source) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&#]" + name + "=([^&#]*)"),
      results = regex.exec(source);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
  }

}); //CCR.xdmod.ui.Viewer
