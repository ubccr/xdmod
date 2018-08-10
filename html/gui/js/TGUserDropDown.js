CCR.xdmod.ui.TGUserDropDown = Ext.extend(Ext.form.ComboBox, {
    controllerBase: 'controllers/sab_user.php',
    triggerAction: 'all',

    user_management_mode: false,

    displayField: 'person_name',
    valueField: 'person_id',

    width: 275,
    minListWidth: 310,
    pageSize: 300,
    hideTrigger: false,
    forceSelection: true,
    minChars: 1,

    piOnly: false,

    getValue: function () {
        // Username-based searches use the following format for the person ID:
        // id;username@host to ensure distinction.  This override parses out the
        // person ID from whatever value is returned via the superclass.

        var value = CCR.xdmod.ui.TGUserDropDown.superclass.getValue.call(this);

        var person_id = value.split(';')[0];

        return person_id;
    },

    setValue: function (v, def) {
        var text = v;

        CCR.xdmod.ui.TGUserDropDown.superclass.setValue.call(this, text);

        if (def) {
            this.lastSelectionText = def;
        }

        return this;
    },

    initializeWithValue: function (v, l) {
        this.setValue(v, l);
        this.setRawValue(l);
    },

    initComponent: function () {
        var self = this;

        var bParams = {
            operation: 'enum_tg_users',
            pi_only: 'n',
            search_mode: 'formal_name'
        };

        if (self.user_management_mode === true) {
            bParams.userManagement = 'y';
        }

        this.userStore = new Ext.data.JsonStore({
            url: self.controllerBase,

            autoDestroy: false,

            baseParams: bParams,

            root: 'users',
            fields: ['person_id', 'person_name'],
            totalProperty: 'total_user_count',
            successProperty: 'success',
            messageProperty: 'message',

            listeners: {
                exception: function (dp, type, action, options, response, arg) {
                    CCR.xdmod.ui.presentFailureResponse(response, {
                        title: 'XDMoD'
                    });
                }
            }
        });

        Ext.apply(this, {
            store: this.userStore
        });

        if (this.dashboardMode) {
            this.store.baseParams.dashboard_mode = 1;
        }

        CCR.xdmod.ui.TGUserDropDown.superclass.initComponent.apply(this, arguments);
    }, // initComponent

    listeners: {
        select: function (component, record, index) {
            var personId = component.getValue();
            var cascadeOptions = this.cascadeOptions;
            var comp;
            var callback;
            var valueProperty;

            if (cascadeOptions !== undefined) {
                if (cascadeOptions.component !== undefined) {
                    comp = cascadeOptions.component;
                }
                if (cascadeOptions.callback !== undefined) {
                    callback = cascadeOptions.callback;
                }
                if (cascadeOptions.valueProperty !== undefined) {
                    valueProperty = cascadeOptions.valueProperty;
                }
            }

            if (comp !== undefined) {
                Ext.Ajax.request({
                    url: XDMoD.REST.prependPathBase('persons/' + personId + '/organization'),
                    method: 'GET',
                    scope: self,
                    callback: function (options, success, response) {
                        var json;

                        if (success) {
                            json = CCR.safelyDecodeJSONResponse(response);
                            // eslint-disable-next-line no-param-reassign
                            success = CCR.checkDecodedJSONResponseSuccess(json);
                        }

                        if (!success) {
                            CCR.xdmod.ui.presentFailureResponse(response, {
                                title: 'User Management',
                                wrapperMessage: 'Setting user mapping failed.'
                            });
                            return;
                        }

                        var value = json.results[valueProperty];

                        if (comp.getValue() !== value && callback !== undefined) {
                            callback(comp.getValue(), value);
                        }
                        comp.setValue(value);
                    }
                });
            }
        }
    }
}); // CCR.xdmod.ui.TGUserDropDown
