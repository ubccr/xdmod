Ext.ns('XDMoD.Admin');

function oc(a)
{
  var o = {};
  for(var i=0;i<a.length;i++)
  {
    o[a[i]]='';
  }
  return o;
}

// =============================================================

XDMoD.Admin.Roles = {
   CENTER_DIRECTOR: 0,
   CENTER_STAFF: 1
};

// =============================================================

Array.prototype.itemExists = function(needle){

   var L = this.length;
   var i = 0;

   while(i < L){

      if(this[i] == needle) return 1;
      ++i;

   }

   return -1;

};//Array.prototype.itemExists

// =============================================================

XDMoD.Admin.RoleGrid = Ext.extend(Ext.grid.EditorGridPanel,  {

   selectedRoles: null,

   selectedPrimaryRole: '',

   selectionChangeHandler: null,

   role_description_column_width: 109,

   enableHdMenu: false,

    listeners: {
        beforerender: function(grid) {
            Dashboard.ControllerProxy(grid.getStore(), {operation: 'enum_roles'});
        }
    },

   initComponent: function() {

      var fm = Ext.form;
      var self = this;

      var numChecked = {};

      numChecked[XDMoD.Admin.Roles.CENTER_DIRECTOR] = {
         ref: Ext.id(),
         value: ''
      };

      numChecked[XDMoD.Admin.Roles.CENTER_STAFF] = {
         ref: Ext.id(),
         value: ''
      };

      var isDirty = false;

      // --------------------------------------------

      var dropRole = function(role_id) {

         var tmpArr = [];

         for (var x = 0; x < self.selectedRoles.length; x++)
            if (self.selectedRoles[x] != role_id)
               tmpArr.push(self.selectedRoles[x]);

         self.selectedRoles = tmpArr.slice(0);

      };//dropRole

      // --------------------------------------------

      var appendRole = function(role_id) {

         var roleSetConfig = oc(self.selectedRoles);

         if ((role_id in roleSetConfig) == false) self.selectedRoles.push(role_id);

      };//appendRole

      // --------------------------------------------

      var updateCenterCount = function(type, count) {

         if (document.getElementById(numChecked[type].ref) == undefined) return;

         document.getElementById(numChecked[type].ref).innerHTML = (count > 0) ? '<b> (' + count + ')</b>' : '';
         numChecked[type].value = (count > 0) ? '<b> (' + count + ')</b>' : '';

      };//updateCenterCount

      // --------------------------------------------

      var centerAssignments = [XDMoD.Admin.Roles.CENTER_DIRECTOR, XDMoD.Admin.Roles.CENTER_STAFF];
      var primaryAssignment = [XDMoD.Admin.Roles.CENTER_DIRECTOR, XDMoD.Admin.Roles.CENTER_STAFF];

      centerAssignments[XDMoD.Admin.Roles.CENTER_DIRECTOR] = [];
      centerAssignments[XDMoD.Admin.Roles.CENTER_STAFF] = [];

      primaryAssignment[XDMoD.Admin.Roles.CENTER_DIRECTOR] = -1;
      primaryAssignment[XDMoD.Admin.Roles.CENTER_STAFF] = -1;

      // =============================

      self.reset = function() {

         self.rselector.reset();
         self.prselector.reset();

         centerAssignments[XDMoD.Admin.Roles.CENTER_DIRECTOR].length = 0;
         centerAssignments[XDMoD.Admin.Roles.CENTER_STAFF].length = 0;

         primaryAssignment[XDMoD.Admin.Roles.CENTER_DIRECTOR] = -1;
         primaryAssignment[XDMoD.Admin.Roles.CENTER_STAFF] = -1;

         updateCenterCount(XDMoD.Admin.Roles.CENTER_DIRECTOR, 0);
         updateCenterCount(XDMoD.Admin.Roles.CENTER_STAFF, 0);

      };//reset

      // =============================

      self.areRolesSpecified = function() {

         if ((self.getSelectedRoles().length == 0) &&
             (self.getCenterSelections(XDMoD.Admin.Roles.CENTER_DIRECTOR).length == 0) &&
             (self.getCenterSelections(XDMoD.Admin.Roles.CENTER_STAFF).length == 0))
               return false;
         else
               return true;

      };//areRolesSpecified

      // =============================

      self.isPrimaryRoleSpecified = function() {

         if ((self.getPrimaryRole() == 'cs') && (self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_STAFF) == -1)) {
            return false;
         }

         if ((self.getPrimaryRole() == 'cd') && (self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_DIRECTOR) == -1)) {
            return false;
         }

         if ((self.getPrimaryRole().length == 0) &&
             (self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_DIRECTOR) == -1) &&
             (self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_STAFF) == -1))
               return false;
         else
               return true;

      };//isPrimaryRoleSpecified

      // =============================

      self.getSelections = function() {

         var selections = {};

         if (self.getCenterSelections(XDMoD.Admin.Roles.CENTER_DIRECTOR).length > 0) appendRole('cd'); else dropRole('cd');

         if (self.getCenterSelections(XDMoD.Admin.Roles.CENTER_STAFF).length > 0) appendRole('cs'); else dropRole('cs');

         selections.mainRoles = self.getSelectedRoles();
         selections.primaryRole = self.getPrimaryRole();

         selections.centerDirectorSites = self.getCenterSelections(XDMoD.Admin.Roles.CENTER_DIRECTOR);
         selections.primaryCenterDirectorSite = self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_DIRECTOR);

         selections.centerStaffSites = self.getCenterSelections(XDMoD.Admin.Roles.CENTER_STAFF);
         selections.primaryCenterStaffSite = self.getPrimaryCenterSelection(XDMoD.Admin.Roles.CENTER_STAFF);

         return selections;

      };//getSelections

      // =============================

      self.setCenterConfig = function(type, config) {

         updateCenterCount(type, config.length);

         // config is an array of objects of the form: {provider: ###, is_primary: #}

         primaryAssignment[type] = -1;
         centerAssignments[type].length = 0;

         for (var j = 0; j < config.length; j++) {

            centerAssignments[type].push(config[j].provider);

            if (config[j].is_primary == 1)
               primaryAssignment[type] = config[j].provider;

         }//for

      };//setCenterConfig

      // =============================

      self.setDirtyState = function (b) {

         isDirty = b;

         if (self.selectionChangeHandler)
            self.selectionChangeHandler();

      };//setDirtyState

      // =============================

      self.isInDirtyState = function() {

         return isDirty;

      };//isInDirtyState

      // =============================

      self.storeCenterSelections = function(type, selections, primary) {

         updateCenterCount(type, selections.length);

         var role_id;

         if (type == XDMoD.Admin.Roles.CENTER_DIRECTOR) role_id = 'cd';
         if (type == XDMoD.Admin.Roles.CENTER_STAFF)    role_id = 'cs';

         if (selections.length > 0)
            appendRole(role_id);
         else
            dropRole(role_id);

         centerAssignments[type] = selections;
         primaryAssignment[type] = primary;

         if (primary != -1) {

            self.selectedPrimaryRole = role_id;

            //need to reset all the other 'primary' checkboxes for the other roles

            self.store.each(function(r){

               r.set('primary', false);

            }, this);

            if (type == XDMoD.Admin.Roles.CENTER_STAFF)
               primaryAssignment[XDMoD.Admin.Roles.CENTER_DIRECTOR] = -1;

            if (type == XDMoD.Admin.Roles.CENTER_DIRECTOR)
               primaryAssignment[XDMoD.Admin.Roles.CENTER_STAFF] = -1;

         }
         else {

            if (self.selectedPrimaryRole == role_id)
               self.selectedPrimaryRole = '';

         }

      };//storeCenterSelections

      // =============================

      self.getCenterSelections = function(type) {

         return centerAssignments[type];

      };//getCenterSelections

      // =============================

      self.getPrimaryCenterSelection = function(type) {

         return primaryAssignment[type];

      };//getPrimaryCenterSelection

      // --------------------------------------------

      var chkIncludeRenderer = function(val, metaData, record, rowIndex, colIndex, store){

         var entryData = store.getAt(rowIndex).data;

         var addedStyle = "";

         if (entryData.role == 'Center Director' || entryData.role == 'Center Staff') {

            var activeType = XDMoD.Admin.Roles.CENTER_DIRECTOR;

            if (entryData.role == 'Center Staff') activeType = XDMoD.Admin.Roles.CENTER_STAFF;

            return '<div style="margin-top: -1px; margin-left: 14px">' +
                   '<a title="Specify Centers" href="javascript:void(0)" onClick="XDMoD.Admin.RoleGrid.prepCenterMenu(this, ' + activeType + ', \'' + self.id + '\')">' +
                   '<img src="images/center_edit.png"></a></div>';


         }
         else {

            return '<div style="' + addedStyle + '" class="x-grid3-check-col' + (val ? "-on" : "") + ' x-grid3-cc-' + this.createId() + '"> </div>';

         }

      };//chkIncludeRenderer

      // --------------------------------------------

      var chkPrimaryRenderer = function(val, metaData, record, rowIndex, colIndex, store){

         var entryData = store.getAt(rowIndex).data;

         var addedStyle = "";

         if (entryData.role == 'Center Director' || entryData.role == 'Center Staff') {

            return '<div style="margin-top: -1px; margin-left: 14px">' +
                   '<img title="Click on the edit icon to the left to manage the primary role regarding centers" src="images/arrow_left.png">' +
                   '</div>';


         }
         else if (entryData.role == 'Manager' || entryData.role == 'Developer') {

            return '<div style="margin-top: -1px; margin-left: 14px">' +
                   '<img title="The ' + entryData.role + ' role cannot be primary" src="images/arrow_left.png">' +
                   '</div>';

         }
         else {

            return '<div style="' + addedStyle + '" class="x-grid3-check-col' + (val ? "-on" : "") + ' x-grid3-cc-' + this.createId() + '"> </div>';

         }

      };//chkPrimaryRenderer

      // --------------------------------------------

      var ccInclude = new Ext.grid.CheckColumn({

         header: 'Include',
         dataIndex: 'include',
         width: 55,
         renderer: chkIncludeRenderer,

         onMouseDown : function(e, t) {

            if (t.className && t.className.indexOf('x-grid3-cc-' + this.id) != -1) {

               var index = this.grid.getView().findRowIndex(t);
               var record = this.grid.store.getAt(index);

               e.stopEvent();

               if (record.data['primary'] == true) {
                  Ext.MessageBox.alert('Role Manager', 'You cannot uncheck this role because it is the primary role.');
                  return;
               }

               // Invert the state of the record.data['include']..
               // Any records which have their 'include' property set to true will now be pushed onto the selectedRoles array

               record.set(this.dataIndex, !record.data[this.dataIndex]);

               // Reset and repopulate the selectedRoles array ...

               self.selectedRoles.length = 0;

               this.grid.store.each(function(r){

                  // By default, r.data[this.dataIndex] is set to false
                  if (r.data[this.dataIndex] == true)
                     self.selectedRoles.push(r.data.role_id);

               }, this);

               isDirty = true;

               if (self.selectionChangeHandler)
                  self.selectionChangeHandler();

            }//if (t.className ...

         },//onMouseDown

         setValues : function (v) {

            self.selectedRoles.length = 0;

            this.grid.store.each(function(r){

               r.set(this.dataIndex, v.itemExists(r.data.role_id) == 1);

               if (v.itemExists(r.data.role_id) == 1)
                  self.selectedRoles.push(r.data.role_id);

            }, this);

         },//setValues

         reset : function () {

            self.selectedRoles.length = 0;

            this.grid.store.each(function(r){

               r.set(this.dataIndex, false);

            }, this);

         }//reset

      });//ccInclude

      // --------------------------------------------

      var roleDescriptionRenderer = function(val) {

         if (val == 'Center Director')
            return val + ' <span id="' + numChecked[XDMoD.Admin.Roles.CENTER_DIRECTOR].ref + '">' + numChecked[XDMoD.Admin.Roles.CENTER_DIRECTOR].value + '</span>';
         else if (val == 'Center Staff')
            return val + ' <span id="' + numChecked[XDMoD.Admin.Roles.CENTER_STAFF].ref + '">' + numChecked[XDMoD.Admin.Roles.CENTER_STAFF].value + '</span>';
         else
            return val;

      };//roleDescriptionRenderer

      // --------------------------------------------

      var cm = new Ext.grid.ColumnModel({

         defaults: {sortable: false, hideable: false},
         columns: [
            {
               header: 'Role',
               dataIndex: 'role',
               renderer: roleDescriptionRenderer,
               width: self.role_description_column_width
            },
            ccInclude
         ]

      });//cm

      // --------------------------------------------

      var store = new DashboardStore({

          autoLoad: false,  // Load the store before render
          autoDestroy: true,
          url: '../controllers/user_admin.php',
          baseParams: {operation: 'enum_roles'},
          root: 'roles',
          fields: ['role', 'role_id', 'include', 'primary']
      });//store

      // --------------------------------------------

      Ext.apply(this, {

         store: store,
         cm: cm,
         enableColumnResize: false,

         plugins: [ccInclude]

      });

      this.selectedRoles = [];

      this.rselector = ccInclude;

      // As far as I can tell, the ControllerProxy doesn't provide any useful functionality. -smg 2015-07-13
      // Dashboard.ControllerProxy(store, {operation: 'enum_roles'});

      XDMoD.Admin.RoleGrid.superclass.initComponent.call(this);

   },

   onRender : function(ct, position){
      XDMoD.Admin.RoleGrid.superclass.onRender.call(this, ct, position);
   },

   setRoles: function (roles) {
      this.rselector.setValues(roles);
   },

   getSelectedRoles: function () {
      return this.selectedRoles;
   },

   getPrimaryRole: function () {
      return this.selectedPrimaryRole;
   }

});//XDMoD.Admin.RoleGrid

// =============================================================

XDMoD.Admin.RoleGrid.CenterSelector = Ext.extend(Ext.menu.Menu,  {

   // Default role type if none is specified in constructor

   roleType: XDMoD.Admin.Roles.CENTER_DIRECTOR,

   initComponent: function(){

      var self = this;

      if (self.defaultSelections == undefined) self.defaultSelections = [];
      if (self.primarySelection == undefined) self.primarySelection = -1;

      var centerAssignments = [];

      for (var j = 0; j < self.defaultSelections.length; j++)
         centerAssignments.push(self.defaultSelections[j]);

      var primaryCenterAssignment = self.primarySelection;

      // ==================================

      var saveLocalData = function (chkCol) {

         // Reset and repopulate the centerAssignments array ...

         centerAssignments.length = 0;

         primaryCenterAssignment = -1;

         centersGrid.store.each(function(r){

            if (r.data['include'] == true)
               centerAssignments.push(r.data['id']);

            if (r.data['primary'] == true)
               primaryCenterAssignment = r.data['id'];

         });

         Ext.getCmp(self.parentID).storeCenterSelections(self.roleType, centerAssignments, primaryCenterAssignment);
         Ext.getCmp(self.parentID).setDirtyState(true);

         /*
         centersGrid.el.mask('<b style="color: #080">Selections Saved</b>');

         (function() {
            centersGrid.el.unmask();
         }).defer(1000);
         */

      };//saveLocalData

      // ==================================

      var store = new Ext.data.JsonStore({

         url: '../controllers/user_admin.php',
         fields: ['id', 'organization'],
         root: 'providers',
         idProperty: 'id',
         baseParams: {'operation': 'enum_resource_providers'},
         autoLoad: true

      });

      // ==================================

      var ccInclude = new Ext.grid.CheckColumn({

         width: 50,
         dataIndex: 'include',
         scope: this,
         header: 'Include',

         onMouseDown : function(e, t)
         {

            if(Ext.fly(t).hasClass(this.createId()))
            {

               e.stopEvent();
               var index = this.grid.getView().findRowIndex(t);
               var record = this.grid.store.getAt(index);
               record.set(this.dataIndex, !record.data[this.dataIndex]);

               if (record.data[this.dataIndex] == false) {

                  // When unchecking the 'include' checkbox, make sure that the
                  // corresponding 'primary' checkbox gets unchecked

                  record.set('primary', false);

               }

               saveLocalData();

               //rebuildLocalData(this);

            }

         }

      });

      // ==================================

      var ccPrimary = new Ext.grid.CheckColumn(
      {
         width: 50,
         dataIndex: 'primary',
         scope: this,
         header: 'Primary',
         type: 'radio',
         singleSelect: true,

         onMouseDown : function(e, t)
         {

            if(Ext.fly(t).hasClass(this.createId()))
            {

               e.stopEvent();
               var index = this.grid.getView().findRowIndex(t);
               var record = this.grid.store.getAt(index);

               if (this.singleSelect) {

                  this.grid.store.each(function(r) {

                     // Enforces 'single select / radio' behavior

                     r.set(this.dataIndex, r.id == record.id);

                     // Need to update role information

                     if (r.id == record.id) {

                        // The 'primary role' must be in the 'included' role set
                        if (!r.data['include']) {
                           record.set('include', true);
                        }

                        //rebuildLocalData(this);

                     }//if (r.id == record.id)

                  }, this);

                  saveLocalData();

              }//if (this.singleSelect)

            }//if(Ext.fly(t).hasClass(this.createId()))

         }//onMouseDown

      });

      var cm = new Ext.grid.ColumnModel({

         defaults: {sortable: false, hideable: false, resizable: false},
         columns: [
            {
               header: 'Center',
               dataIndex: 'organization',
               width: 280
            },
            ccInclude,
            ccPrimary
         ]

      });//cm

      var roleTypeText = '';
      if (this.roleType == XDMoD.Admin.Roles.CENTER_DIRECTOR) roleTypeText = 'Center Director';
      if (this.roleType == XDMoD.Admin.Roles.CENTER_STAFF)    roleTypeText = 'Center Staff';

      var loadDefaultSelections = function() {

         ca = oc(centerAssignments);

         centersGrid.store.each(function(r){

            r.set('include', (r.data['id'] in ca));
            r.set('primary', (r.data['id'] == primaryCenterAssignment));

         });

      };//loadDefaultSelections

      /*
      self.on('show', function(){

         (function(){ loadDefaultSelections(); }).defer(600);

      });
      */

      var btnReset = new Ext.Button({

         text: 'Reset Selections',
         iconCls: 'btn_center_selections_reset',
         handler: function() {

            loadDefaultSelections();

         }

      });

      var btnSave = new Ext.Button({

         text: 'Save Selections',
         iconCls: 'btn_center_selections_save',
         handler: function() {

            saveLocalData();

         }

      });

      var btnClose = new Ext.Button({

         text: 'Close',
         iconCls: 'general_btn_close',
         handler: function() {

            self.hide();

         }

      });

      var centersGrid = new Ext.grid.GridPanel({

         title: 'Select the centers associated with the <span style="color: #00f">' + roleTypeText + '</span> role',
         store: store,
         autoScroll: true,
         rowNumberer: true,
         border: true,
         stripeRows: true,
         enableHdMenu: false,
         plugins: [ccInclude, ccPrimary],
         cm: cm,
         region: 'center',
         height: 335,
         layout: 'fit',
         viewConfig : {
            forceFit: true,
            scrollOffset: 2 // the grid will never have scrollbars
         },

         bbar: {
            items: [
               /*
               btnSave,
               '|',
               btnReset,
               */
               '->',
               btnClose
            ]
         }

      });

      centersGrid.store.on('load', function() {

         (function(){ loadDefaultSelections(); }).defer(100);

      });

      Ext.apply(this,
      {

         width: 420,
         height: 345,

         border: false,
         header: false,

         //layout: 'border',
         showSeparator: false,

         items: [
            centersGrid
         ]

      });


      XDMoD.Admin.RoleGrid.CenterSelector.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.Admin.RoleGrid.CenterSelector

// =============================================================

XDMoD.Admin.RoleGrid.prepCenterMenu = function(objSrcEl, role_type, parent_id) {

   var parent = Ext.getCmp(parent_id);

   var mnu = new XDMoD.Admin.RoleGrid.CenterSelector({
      roleType: role_type,
      parentID: parent_id,
      defaultSelections: parent.getCenterSelections(role_type),
      primarySelection: parent.getPrimaryCenterSelection(role_type)
   });

   mnu.show(objSrcEl, 'tl-bl?');

};//prepCenterMenu
