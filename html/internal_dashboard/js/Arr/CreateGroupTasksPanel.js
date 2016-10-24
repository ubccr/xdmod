Ext.namespace('XDMoD', 'XDMoD.Arr');
XDMoD.Arr.date_format = 'm/d/Y';
XDMoD.Arr.time_format = 'g:i A';

XDMoD.Arr.createResourceStore = function () {
    var self = this;
    return new Ext.data.JsonStore({
        isLoaded: false,
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        idProperty: 'id',
        proxy: new Ext.data.HttpProxy({
            method: 'GET',
            url: XDMoD.REST.url + '/akrr/resources?token=' + XDMoD.REST.token
        }),
        fields: [
            {name: 'id', type: 'int'},
            {name: 'name', type: 'string'}
        ],
        /**
         * Call the given function after this store has first loaded.
         *
         * @param  {Function} fn    The function to call.
         * @param  {mixed}    scope (Optional) The scope to call the function with.
         */
        callAfterLoaded: function (fn, scope) {
            scope = Ext.isDefined(scope) ? scope : this;
            if (this.isLoaded) {
                fn.call(scope);
            } else {
                this.addListener('load', fn, scope, {
                    single: true
                });
            }
        }
    });
};


/**
 * Create a button containing a list of available resources.
 */
XDMoD.Arr.createResourceSelectionButton = function (config) {
    config = Ext.isDefined(config) ? config : {};

    var resourceSelectionButton = new Ext.Button(Ext.apply({
        text: 'Resource',
        iconCls: 'resource',
        disabled: true,
        selected_resource: null,
        tooltip: 'Select resource for new group tasks',
        menu: new Ext.menu.Menu({
            showSeparator: false
        })
    }, config));

    var store = XDMoD.Arr.createResourceStore();
    resourceSelectionButton.store = store;

    // Create a callback for after resources have been loaded.
    store.callAfterLoaded(function () {
        resourceSelectionButton.store.each(function (resourceRecord) {
            resourceSelectionButton.menu.add(new Ext.menu.CheckItem({
                text: resourceRecord.get('name'),
                resource: resourceRecord.get('name'),
                checked: false,
                group: 'resources',
                listeners: {
                    checkchange: function (thisItem, checked) {
                        if (checked) {
                            resourceSelectionButton.selected_resource = thisItem.resource;
                            resourceSelectionButton.fireEvent('resource_selected');
                        }
                    }
                }
            }));
        });

        // Enable the button.
        resourceSelectionButton.enable();
    });
    resourceSelectionButton.enableBubble('resource_selected');
    // Return the button.
    return resourceSelectionButton;
};

XDMoD.Arr.createAppKernelStore = function (config) {
    config = Ext.isDefined(config) ? config : {};
    var store = new Ext.data.JsonStore(Ext.apply({
        isLoaded: false,
        root: 'data',
        messageProperty: 'message',
        successProperty: 'success',
        idProperty: 'id',
        proxy: new Ext.data.HttpProxy({
            method: 'GET',
            url: XDMoD.REST.url + '/akrr/kernels?token=' + XDMoD.REST.token

        }),
        baseParams: {
            resource: ''
        },
        fields: [
            {name: 'id', type: 'int'},
            {name: 'name', type: 'string'},
            {
                name: 'nodes_list',
                convert: function (v, rec) {
                    nodes_list1 = v.split(';').map(Number);
                    nodes_list2 = v.split(',').map(Number);
                    return (nodes_list1.length > nodes_list2.length) ? nodes_list1 : nodes_list2;
                }
            }
        ],
        /**
         * Call the given function after this store has first loaded.
         *
         * @param  {Function} fn    The function to call.
         * @param  {mixed}    scope (Optional) The scope to call the function with.
         */
        callAfterLoaded: function (fn, scope) {
            scope = Ext.isDefined(scope) ? scope : this;
            if (this.isLoaded) {
                fn.call(scope);
            } else {
                this.addListener('load', fn, scope, {
                    single: false
                });
            }
        }
    }, config));


    return store;
};


/**
 * Create a button containing a list of available appkernels.
 */
XDMoD.Arr.createAppKernelSelectionButton = function (config) {
    config = Ext.isDefined(config) ? config : {};

    var appkernelSelectionButton = new Ext.Button(Ext.apply({
        text: 'AppKernels',
        iconCls: 'appkernel',
        disabled: true,
        tooltip: 'Select appkernel for new group tasks',
        menu: new Ext.menu.Menu({}),
        listeners: {
            'select_all': function () {
                var iitem;
                var all_checked = true;
                var all_unchecked = true;

                for (iitem = 2; iitem < this.menu.items.getCount(); ++iitem) {
                    if (this.menu.items.item(iitem).checked) {
                        all_unchecked = false;
                    } else {
                        all_checked = false;
                    }
                }

                if (all_unchecked == true || (all_unchecked == false && all_checked == false)) {
                    //select all
                    for (iitem = 2; iitem < this.menu.items.getCount(); ++iitem) {
                        this.menu.items.item(iitem).setChecked(true);
                    }
                } else {
                    //deselect all
                    for (iitem = 2; iitem < this.menu.items.getCount(); ++iitem) {
                        this.menu.items.item(iitem).setChecked(false);
                    }
                }
            }
        },
        get_selected_appkernels_items: function () {
            var iitem;
            var appkernels_items_list = [];
            for (iitem = 2; iitem < this.menu.items.getCount(); ++iitem) {
                if (this.menu.items.item(iitem).checked) {
                    appkernels_items_list.push(this.menu.items.item(iitem));
                }
            }
            return appkernels_items_list;
        }
    }, config));

    var store = XDMoD.Arr.createAppKernelStore();
    appkernelSelectionButton.store = store;

    // Create a callback for after appkernels have been loaded.
    store.callAfterLoaded(function () {
        // Record current user selection and clear menu
        appkernels_selection = {};
        if (Ext.isDefined(appkernelSelectionButton.menu.items)) {
            appkernelSelectionButton.menu.items.each(function (appkernel_checkbox) {
                appkernels_selection[appkernel_checkbox.appkernel] = appkernel_checkbox.checked;
            });
            //remove all appkernel checkboxes
            appkernelSelectionButton.menu.removeAll();
        }
        ;

        // Add appkernels to menu
        appkernelSelectionButton.menu.add({
            xtype: 'menuitem',
            text: 'Select/Deselect All',
            hideOnClick: false,
            listeners: {
                'click': function (menuItem, e) {
                    appkernelSelectionButton.fireEvent('select_all');
                }
            }
        });
        appkernelSelectionButton.menu.add('-');
        appkernelSelectionButton.store.each(function (appkernelRecord) {
            appkernel = appkernelRecord.get('name');
            appkernelSelectionButton.menu.add(new Ext.menu.CheckItem({
                text: appkernel,
                appkernel: appkernel,
                nodes_list: appkernelRecord.get('nodes_list'),
                hideOnClick: false,
                checked: Ext.isDefined(appkernels_selection[appkernel]) ? appkernels_selection[appkernel] : true
            }));
        });
        appkernelSelectionButton.fireEvent('appkernels_loaded');
        appkernelSelectionButton.enable();
    });
    appkernelSelectionButton.enableBubble('appkernels_loaded');
    // Return the button.
    return appkernelSelectionButton;
};

/**
 * Create a button containing a list of available nodes counts.
 */
XDMoD.Arr.createNodesSelectionButton = function (config) {
    config = Ext.isDefined(config) ? config : {};

    var nodesSelectionButton = new Ext.Button(Ext.apply({
        text: 'Node Counts',
        iconCls: 'units',
        disabled: true,
        tooltip: 'Select nodes numbers for new group tasks',
        menu: new Ext.menu.Menu({
            showSeparator: false
        }),
        load: function () {
            nodes_list = [];
            this.appkernelSelectionButton.store.each(function (appkernelRecord) {
                appkernelRecord.get('nodes_list').forEach(function (nodes_count) {
                    if (nodes_list.indexOf(nodes_count) < 0) {
                        nodes_list.push(nodes_count);
                    }
                    ;
                });
            });
            nodes_list.sort(function (a, b) {
                return a - b
            });

            // Record current user selection and clear menu
            nodes_selection = {};
            if (Ext.isDefined(nodesSelectionButton.menu.items)) {
                nodesSelectionButton.menu.items.each(function (nodes_checkbox) {
                    nodes_selection[nodes_checkbox.nodes] = nodes_checkbox.checked;
                });
                nodesSelectionButton.menu.removeAll();
            }
            ;

            // Add nodes counts to menu
            nodes_list.forEach(function (nodes_count) {
                nodesSelectionButton.menu.add(new Ext.menu.CheckItem({
                    text: nodes_count,
                    nodes: nodes_count,
                    hideOnClick: false,
                    checked: Ext.isDefined(nodes_selection[nodes_count]) ? nodes_selection[nodes_count] : true
                }));
            });
            nodesSelectionButton.enable();
            nodesSelectionButton.fireEvent('nodes_list_loaded');
        }
    }, config));

    nodesSelectionButton.enableBubble('nodes_list_loaded');

    // Return the button.
    return nodesSelectionButton;
};

XDMoD.Arr.createPeriodicityStore = function () {
    return new Ext.data.JsonStore({
        fields: ['name', 'akrr_format'],
        data: [
            {name: "None", akrr_format: 'None'},
            {name: "30 minutes", akrr_format: '0-00-000 00:30:00'},
            {name: "1 hour", akrr_format: '0-00-000 01:00:00'},
            {name: "2 hours", akrr_format: '0-00-000 02:00:00'},
            {name: "4 hours", akrr_format: '0-00-000 04:00:00'},
            {name: "8 hours", akrr_format: '0-00-000 08:00:00'},
            {name: "12 hours", akrr_format: '0-00-000 12:00:00'},
            {name: "1 day", akrr_format: '0-00-001 00:00:00'},
            {name: "2 days", akrr_format: '0-00-002 00:00:00'},
            {name: "3 days", akrr_format: '0-00-003 00:00:00'},
            {name: "4 days", akrr_format: '0-00-004 00:00:00'},
            {name: "5 days", akrr_format: '0-00-005 00:00:00'},
            {name: "6 days", akrr_format: '0-00-006 00:00:00'},
            {name: "1 week", akrr_format: '0-00-007 00:00:00'},
            {name: "2 weeks", akrr_format: '0-00-014 00:00:00'},
            {name: "3 weeks", akrr_format: '0-00-021 00:00:00'},
            {name: "4 weeks", akrr_format: '0-00-028 00:00:00'},
            {name: "1 month", akrr_format: '0-01-000 00:00:00'}
        ],
        padInt: function (num, size) {
            var s = num + "";
            while (s.length < size) s = "0" + s;
            return s;
        },
        validateValue: function (value) {
            var ndx = this.find('name', value)
            if (ndx >= 0) {
                return true;
            } else {
                var m;
                m = value.match(/^\s*(\d+)\s+([A-Za-z]+)\s*$/);
                if (m && m[1] != 0) {
                    if ((m[2] == 'hour' || m[2] == 'hours')) {
                        this.add(new this.recordType({
                            name: value,
                            akrr_format: '0-00-000 ' + this.padInt(m[1], 2) + ':00:00'
                        }));
                        return true;
                    }
                    if ((m[2] == 'day' || m[2] == 'days')) {
                        this.add(new this.recordType({
                            name: value,
                            akrr_format: '0-00-' + this.padInt(m[1], 3) + ' 00:00:00'
                        }));
                        return true;
                    }
                    if ((m[2] == 'day' || m[2] == 'days')) {
                        this.add(new this.recordType({
                            name: value,
                            akrr_format: '0-00-' + this.padInt(m[1], 3) + ' 00:00:00'
                        }));
                        return true;
                    }
                    if ((m[2] == 'week' || m[2] == 'weeks')) {
                        this.add(new this.recordType({
                            name: value,
                            akrr_format: '0-00-' + this.padInt(m[1] * 7, 3) + ' 00:00:00'
                        }));
                        return true;
                    }
                    if ((m[2] == 'month' || m[2] == 'months')) {
                        this.add(new this.recordType({
                            name: value,
                            akrr_format: '0-' + this.padInt(m[1], 2) + '-000 00:00:00'
                        }));
                        return true;
                    }
                }
                //value already in akrr format
                m = value.match(/^\s*(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)\s*$/);
                if (m) {
                    var years = parseInt(m[1]);
                    var months = parseInt(m[2]);
                    var days = parseInt(m[3]);
                    var hours = parseInt(m[4]);
                    var minutes = parseInt(m[5]);
                    var seconds = parseInt(m[6]);

                    if (years == 0 && seconds == 0) {
                        if (months > 0 && days == 0 && hours == 0 && minutes == 0) {
                            this.add(new this.recordType({
                                name: months + ((months == 1) ? ' month' : ' months'),
                                akrr_format: value
                            }));
                            return true;
                        }
                        if (months == 0 && days > 0 && hours == 0 && minutes == 0) {
                            this.add(new this.recordType({
                                name: days + ((days == 1) ? ' day' : ' days'),
                                akrr_format: value
                            }));
                            return true;
                        }
                        if (months == 0 && days == 0 && hours > 0 && minutes == 0) {
                            this.add(new this.recordType({
                                name: hours + ((hours == 1) ? ' hour' : ' hours'),
                                akrr_format: value
                            }));
                            return true;
                        }
                        if (months == 0 && days == 0 && hours == 0 && minutes > 0) {
                            this.add(new this.recordType({
                                name: minutes + ((minutes == 1) ? ' minute' : ' minutes'),
                                akrr_format: value
                            }));
                            return true;
                        }
                    }

                    this.add(new this.recordType({name: value, akrr_format: value}));
                    return true;
                }
                return 'Unknown format for periodicity: "' + value + '"';
            }
        }
    });
}

XDMoD.Arr.createPeriodicityStore2 = function () {
    var periodicityRecordType = Ext.data.Record.create(['name', 'akrr_format']);
    var store = new Ext.data.Store({});
    return store;
}

XDMoD.Arr.createPeriodicityComboBox = function (config) {
    config = Ext.isDefined(config) ? config : {};
    return new Ext.form.ComboBox(Ext.apply({
        fieldLabel: 'Period',
        name: 'periodicity',
        displayField: 'name',
        valueField: 'akrr_format',
        typeAhead: true,
        mode: 'local',
        forceSelection: true,
        triggerAction: 'all',
        msgTarget: 'under',
        store: XDMoD.Arr.createPeriodicityStore(),
        validator: function (value) {
            return this.store.validateValue(value);
        }
    }, config));
}

XDMoD.Arr.periodicityComboBoxRenderer = function (combo) {
    return function (value) {
        var idx = combo.store.find(combo.valueField, value);
        var rec = combo.store.getAt(idx);
        return (rec == null ? combo.getValue() : rec.get(combo.displayField) );
    };
}


XDMoD.Arr.createSubmitSettingsMenu = function () {

    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var timecoeff = 1000 * 60 * 15;
    var init_start_time = new Date(Math.round(now.getTime() / timecoeff + 1.5) * timecoeff);
    var tomorrow = new Date(today.valueOf())
    tomorrow.setDate(today.getDate() + 1);

    //setup validators
    var validateStartEndDateTime = function (theForm, senderName, value) {
        if (theForm) {
            var time_now = new Date();

            var submit_period = {
                submitStartDateField: Ext.util.Format.date(theForm.submitStartDateField.getValue(), XDMoD.Arr.date_format),
                submitStartTimeField: theForm.submitStartTimeField.getValue(),
                submitEndDateField: Ext.util.Format.date(theForm.submitEndDateField.getValue(), XDMoD.Arr.date_format),
                submitEndTimeField: theForm.submitEndTimeField.getValue()
            };
            if (senderName == 'submitStartDateField' || senderName == 'submitStartTimeField') {
                submit_period[senderName] = Ext.util.Format.date(value, XDMoD.Arr.date_format);
            } else {
                submit_period[senderName] = value;
            }


            var start_time = new Date(submit_period.submitStartDateField + ' ' + submit_period.submitStartTimeField);
            var end_time = new Date(submit_period.submitEndDateField + ' ' + submit_period.submitEndTimeField);


            if (start_time < time_now) {
                return "Start date can not be in the past";
            }
            if (theForm.submitTimeWaysRadioGroup.getValue().inputValue == "submit_time_gen_way__start_same_time") {
                return true;
            }
            if (end_time < time_now) {
                return "End date can not be in the past";
            }
            if (start_time > end_time) {
                return "End date should be after start date";
            }
            return true;
        } else {
            return true;
        }
    }

    //setup fields
    var quickTipsRegister = function (c) {
        if (c.tooltip) {
            Ext.QuickTips.register({
                target: c.getEl(),
                text: c.tooltip

            });
        }
    };
    var msgTarget = 'under';
    var groupNameField = new Ext.form.TextField({
        fieldLabel: 'Group Name',
        name: 'group_name',
        tooltip: 'group name of tasks, good for record keeping',
        listeners: {
            render: quickTipsRegister
        }
    });

    var periodicityTypeRadioGroup = new Ext.form.RadioGroup({
        fieldLabel: 'Periodicity Type',
        columns: 1,
        items: [{
            boxLabel: 'Single Event',
            name: 'periodicity_type',
            inputValue: 'periodicity_type__single_event',
            checked: true,
            tooltip: 'Periodicity type, in this case app kernels will be executed only once',
            listeners: {
                render: quickTipsRegister
            }
        }, {
            boxLabel: 'Periodic',
            name: 'periodicity_type',
            inputValue: 'periodicity_type__periodic',
            tooltip: 'Periodicity type, app kernels will be scheduled for regular execution',
            listeners: {
                render: quickTipsRegister
            }
        }]
    });
    var repetitionsNumberField = new Ext.ux.form.SpinnerField({
        fieldLabel: 'Number of Repetitions',
        minValue: 1,
        name: 'repetitions',
        value: 1,
        anchor: '-10',
        tooltip: 'Each app kernel will be scheduled for execution this number of times',
        listeners: {
            render: quickTipsRegister
        }
    });
    var periodicityComboBox = XDMoD.Arr.createPeriodicityComboBox({
        itemId: 'periodicity_item',
        listClass: 'x-menu',
        disabled: true,
        value: '0-00-001 00:00:00',
        anchor: '-60',
        tooltip: 'Periodicity with which app kernels will be executed',
        listeners: {
            render: quickTipsRegister
        }
    });
    var submitTimeWaysRadioGroup = new Ext.form.RadioGroup({
        fieldLabel: 'Submit Time',
        columns: 1,
        tooltip: 'Ways for generations of submit time',
        items: [{
            boxLabel: 'All tasks are started at same time',
            name: 'submit_time_gen_way',
            inputValue: 'submit_time_gen_way__start_same_time',
            checked: true,
            tooltip: 'Ways for generations of submit time',
            listeners: {
                render: quickTipsRegister
            }
        }, {
            boxLabel: 'Distrebute randomly, between selected time',
            name: 'submit_time_gen_way',
            inputValue: 'submit_time_gen_way__distrebute_randomly',
            tooltip: 'Ways for generations of submit time',
            listeners: {
                render: quickTipsRegister
            }
        }, {
            boxLabel: 'Distrebute evenly, between selected time',
            name: 'submit_time_gen_way',
            inputValue: 'submit_time_gen_way__distrebute_evenly',
            tooltip: 'Ways for generations of submit time',
            listeners: {
                render: quickTipsRegister
            }
        }],

    });
    var submitStartDateField = new Ext.form.DateField({
        name: 'submit_start_date',
        format: XDMoD.Arr.date_format,
        value: today,
        minValue: today,
        msgTarget: msgTarget,
        tooltip: 'Tasks will be scheduled for execution on or after this date',
        validator: function (value) {
            return validateStartEndDateTime(this.findParentByType('form'), 'submitStartDateField', value);
        },
        listeners: {
            render: quickTipsRegister
        },
        /* this set allowOtherMenus property which is importent for preventing unintended hiding of this form */
        menu: new Ext.menu.DateMenu({
            hideOnClick: false,
            focusOnSelect: false,
            allowOtherMenus: true,
        })
    });
    var submitStartTimeField = new Ext.form.TimeField({
        name: 'submit_start_time',
        increment: 30,
        format: XDMoD.Arr.time_format,
        msgTarget: msgTarget,
        tooltip: 'Tasks will be scheduled for execution on or after this date',
        value: init_start_time,
        listClass: 'x-menu',
        validator: function (value) {
            return validateStartEndDateTime(this.findParentByType('form'), 'submitStartTimeField', value);
        },
        listeners: {
            render: quickTipsRegister
        }
    });
    var submitStartDateTimeField = new Ext.form.CompositeField({
        fieldLabel: 'Start Date and Time',
        defaults: {
            flex: 1
        },
        items: [
            submitStartDateField,
            submitStartTimeField
        ]
    });
    var submitEndDateField = new Ext.form.DateField({
        name: 'submit_end_date',
        format: XDMoD.Arr.date_format,
        value: tomorrow,
        minValue: today,
        tooltip: "All tasks should be submitted by this end date",
        msgTarget: msgTarget,
        validator: function (value) {
            return validateStartEndDateTime(this.findParentByType('form'), 'submitEndDateField', value);
        },
        listeners: {
            render: quickTipsRegister
        },
        menu: new Ext.menu.DateMenu({
            hideOnClick: false,
            focusOnSelect: false,
            allowOtherMenus: true,
        })
    });
    var submitEndTimeField = new Ext.form.TimeField({
        name: 'submit_end_time',
        increment: 30,
        format: XDMoD.Arr.time_format,
        value: init_start_time,
        tooltip: "All tasks should be submitted by this end date",
        msgTarget: msgTarget,
        listClass: 'x-menu',
        validator: function (value) {
            return validateStartEndDateTime(this.findParentByType('form'), 'submitEndTimeField', value);
        },
        listeners: {
            render: quickTipsRegister
        }
    });
    var submitEndDateTimeField = new Ext.form.CompositeField({
        fieldLabel: 'End Date and Time',
        msgTarget: 'side',
        disabled: true,
        defaults: {
            flex: 1
        },
        items: [
            submitEndDateField,
            submitEndTimeField
        ]
    });
    var usePreferedTimeCheckbox = new Ext.form.Checkbox({
        fieldLabel: 'Use Prefered Time',
        tooltip: 'Submit jobs only during period specified in \n"Submit Job Between" field.\n' +
        'Convinient if you want to run app kernels during the nights\n and have periodicity of several days.',
        name: 'use_prefered_time',
        disabled: true,
        listeners: {
            render: quickTipsRegister
        }
    });
    var preferedTimeStartField = new Ext.form.TimeField({
        name: 'prefered_time_start',
        increment: 30,
        format: XDMoD.Arr.time_format,
        value: '00:00',
        tooltip: 'Tasks will be scheduled between these two times. For example, from 9 pm to 3 am for night execution',
        listClass: 'x-menu',
        listeners: {
            render: quickTipsRegister
        }
    });
    var preferedTimeEndField = new Ext.form.TimeField({
        name: 'prefered_time_end',
        increment: 30,
        format: XDMoD.Arr.time_format,
        value: '06:00',
        tooltip: 'Tasks will be scheduled between these two times. For example, from 9 pm to 3 am for night execution',
        listClass: 'x-menu',
        listeners: {
            render: quickTipsRegister
        }
    });
    var preferedTimeCompositeField = new Ext.form.CompositeField({
        fieldLabel: 'Submit Job Between:',
        disabled: true,
        msgTarget: 'side',
        defaults: {
            flex: 1
        },
        items: [
            preferedTimeStartField,
            preferedTimeEndField
        ]
    });

    var submitSettingsForm = new Ext.form.FormPanel({
        groupNameField: groupNameField,
        periodicityTypeRadioGroup: periodicityTypeRadioGroup,
        repetitionsNumberField: repetitionsNumberField,
        periodicityComboBox: periodicityComboBox,
        submitTimeWaysRadioGroup: submitTimeWaysRadioGroup,
        submitStartDateTimeField: submitStartDateTimeField,
        submitStartDateField: submitStartDateField,
        submitStartTimeField: submitStartTimeField,
        submitEndDateTimeField: submitEndDateTimeField,
        submitEndDateField: submitEndDateField,
        submitEndTimeField: submitEndTimeField,
        usePreferedTimeCheckbox: usePreferedTimeCheckbox,
        preferedTimeCompositeField: preferedTimeCompositeField,
        preferedTimeStartField: preferedTimeStartField,
        preferedTimeEndField: preferedTimeEndField,

        items: [{
            xtype: 'fieldset',
            itemId: 'submit_settings_fieldset_item',
            autoHeight: true,
            layout: 'form',
            labelWidth: 120,
            hideLabels: false,
            border: false,
            defaults: {
                anchor: '0' // '-20' // leave room for error icon
            },
            items: [
                groupNameField,
                periodicityTypeRadioGroup,
                repetitionsNumberField,
                periodicityComboBox,
                submitTimeWaysRadioGroup,
                submitStartDateTimeField,
                submitEndDateTimeField,
                usePreferedTimeCheckbox,
                preferedTimeCompositeField
            ]
        }]
    });

    //add listeners
    periodicityTypeRadioGroup.addListener('change', function (radio_group, radio_box) {
        if (radio_box.inputValue == 'periodicity_type__single_event') {
            repetitionsNumberField.enable();
            periodicityComboBox.disable();
        } else {
            repetitionsNumberField.disable();
            periodicityComboBox.enable();
        }
    });
    submitTimeWaysRadioGroup.addListener('change', function (radio_group, radio_box) {
        if (radio_box.inputValue == 'submit_time_gen_way__start_same_time') {
            submitEndDateTimeField.disable();
            usePreferedTimeCheckbox.disable();
            preferedTimeCompositeField.disable();
        } else {
            submitEndDateTimeField.enable();
            usePreferedTimeCheckbox.enable();
            if (usePreferedTimeCheckbox.getValue()) {
                preferedTimeCompositeField.enable();
            } else {
                preferedTimeCompositeField.disable();
            }
        }
    });
    usePreferedTimeCheckbox.addListener('check', function (checkbox, isChecked) {
        if (isChecked) {
            preferedTimeCompositeField.enable();
        } else {
            preferedTimeCompositeField.disable();
        }
    });

    //place everything to the menu
    var submitSettingsMenu = new Ext.menu.Menu({
        width: 420,
        renderTo: document.body,
        showSeparator: false,
        hideOnClick: false,
        menu_open: false,
        items: [submitSettingsForm],
        submitSettingsForm: submitSettingsForm
    });
    return submitSettingsMenu;
};

/**
 * Create a button containing settings for tasks submit
 */
XDMoD.Arr.createSubmitSettingsButton = function (config) {
    config = Ext.isDefined(config) ? config : {};

    var submitSettingsButton = new Ext.Button(Ext.apply({
        text: 'Submit Settings',
        iconCls: 'custom_date',
        tooltip: 'Set ways to generate submit time',
        menu: XDMoD.Arr.createSubmitSettingsMenu()
    }, config));
    // Return the button.
    return submitSettingsButton;
};

XDMoD.Arr.CreateGroupTasksPanel = Ext.extend(Ext.FormPanel, {
    id: 'create-group-tasks-panel',
    resourcesLoaded: false,
    kernelsLoaded: false,

    initComponent: function () {

        Ext.apply(this, {
            border: false,
            width: 800,
            minWidth: 800,
            height: 500,
            minHeight: 500
        });

        this.toolbar = this._createToolbar();

        this.newTasksGridPanel = this._createGrid();

        Ext.apply(this, {
            layout: 'border',
            frame: false,
            tbar: this.toolbar,
            items: [
                this.newTasksGridPanel
            ]
        });

        XDMoD.Arr.CreateGroupTasksPanel.superclass.initComponent.apply(this, arguments);

        this.tasks_to_submit = null;
    },

    listeners: {
        afterrender: function () {
            this.relayEvents(this.ownerCt, ['show']);
        },
        show: function () {
            // get list of resources
            //Ext.QuickTips.init();
            this.toolbar.resourceSelectionButton.store.load();
        },
        resource_selected: function () {
            // load list of appkernels for selected resources
            this.toolbar.appkernelSelectionButton.store.baseParams.resource = this.toolbar.resourceSelectionButton.selected_resource;
            this.toolbar.appkernelSelectionButton.store.load();
        },
        appkernels_loaded: function () {
            // load list of nodes available for selectede resouce/appkernels
            this.toolbar.nodesSelectionButton.load();
        },
        nodes_list_loaded: function () {
            this.toolbar.generateTasksListButton.enable();
        },
        generate_tasks_list: function () {
            this._generateTasksList();
        },
        delete_selected_tasks: function () {
            this._deleteSelectedTasks();
        },
        submit_tasks: function () {
            this._submitTasks();
        }
    },

    _createNodes: function () {
        return new Ext.data.ArrayStore({
            storeId: 'nnodeStore',
            fields: [
                {name: 'value', type: 'int'}
            ],
            proxy: new Ext.data.MemoryProxy()
        });
    },
    _createToolbar: function () {
        var self = this;

        var resourceSelectionButton = XDMoD.Arr.createResourceSelectionButton();
        var appkernelSelectionButton = XDMoD.Arr.createAppKernelSelectionButton();
        var nodesSelectionButton = XDMoD.Arr.createNodesSelectionButton({appkernelSelectionButton: appkernelSelectionButton});
        var submitSettingsButton = XDMoD.Arr.createSubmitSettingsButton();
        var generateTasksListButton = new Ext.Button({
            iconCls: 'refresh',
            text: 'Generate Tasks',
            tooltip: 'Generate list of tasks according to selection, the list can be modified before submittion for execution',
            disabled: true,
            handler: function () {
                self.fireEvent('generate_tasks_list');
            }
        });
        var deleteSelectedTasks = new Ext.Button({
            iconCls: 'delete',
            text: 'Delete Task(s)',
            tooltip: 'Delete tasks from generate list',
            disabled: true,
            handler: function () {
                self.fireEvent('delete_selected_tasks');
            }
        });

        var submitTasksButton = new Ext.Button({
            iconCls: 'add',
            text: 'Submit Tasks',
            tooltip: 'Submit tasks for execution',
            disabled: true,
            scope: this,
            handler: function () {
                self.fireEvent('submit_tasks');
            }
        });


        var toolbar = new Ext.Toolbar({
            resourceSelectionButton: resourceSelectionButton,
            appkernelSelectionButton: appkernelSelectionButton,
            nodesSelectionButton: nodesSelectionButton,
            submitSettingsButton: submitSettingsButton,
            generateTasksListButton: generateTasksListButton,
            deleteSelectedTasks: deleteSelectedTasks,
            submitTasksButton: submitTasksButton,
            items: [
                resourceSelectionButton,
                appkernelSelectionButton,
                nodesSelectionButton,
                submitSettingsButton,
                generateTasksListButton,
                deleteSelectedTasks,
                submitTasksButton
            ]
        });

        return toolbar;
    },
    _createGrid: function (config) {
        config = Ext.isDefined(config) ? config : {};
        var store = new Ext.data.JsonStore({
            fields: [
                {name: 'app', type: 'string'},
                {name: 'nnodes', type: 'int'},
                {name: 'submit_date', type: 'string'},
                {name: 'submit_time', type: 'string'},
                {name: 'periodicity', type: 'string'}
            ]
        });

        var periodicityComboBox = XDMoD.Arr.createPeriodicityComboBox();


        var newTasksGridPanel = new Ext.grid.EditorGridPanel(Ext.apply({
            id: 'gridPanel',
            region: 'center',
            autoScroll: true,
            border: true,
            hideLabel: true,
            columnLines: true,
            enableColumnMove: false,
            enableColumnHide: false,
            periodicityComboBox: periodicityComboBox,
            listeners: {},
            store: store,
            colModel: new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: true
                },
                columns: [
                    {header: 'App. Kernel', dataIndex: 'app', width: 240},
                    {header: 'Nodes', dataIndex: 'nnodes', width: 60},
                    {
                        header: 'Submit Date',
                        dataIndex: 'submit_date',
                        width: 90,
                        renderer: Ext.util.Format.dateRenderer(XDMoD.Arr.date_format),
                        editor: new Ext.form.DateField({
                            format: XDMoD.Arr.date_format
                        })
                    }, {
                        header: 'Submit Time',
                        dataIndex: 'submit_time',
                        width: 90,
                        editor: new Ext.form.TimeField({
                            format: XDMoD.Arr.time_format,
                            increment: 30
                        })
                    }, {
                        header: 'Period',
                        dataIndex: 'periodicity',
                        width: 120,
                        editor: periodicityComboBox,
                        renderer: XDMoD.Arr.periodicityComboBoxRenderer(periodicityComboBox)
                    }
                ]
            }),
            selModel: new Ext.grid.RowSelectionModel({})

        }, config));
        return newTasksGridPanel;
    },
    _generateTasksList: function () {
        /**
         * Generates the list of tasks based on user selection for further review
         * and submittion
         */
        var resource = this.toolbar.resourceSelectionButton.selected_resource;

        if (!resource) {
            Ext.MessageBox.alert('Error', 'Resource is not selected');
            return;
        }
        if ((!this.toolbar.appkernelSelectionButton.menu.items) || this.toolbar.appkernelSelectionButton.menu.items.getCount() == 0) {
            Ext.MessageBox.alert('Error', 'There is no application kernels in selection');
            return;
        }
        this.selectedResource = resource;

        var nodes_list_filter = [];
        if (Ext.isDefined(this.toolbar.nodesSelectionButton.menu.items)) {
            this.toolbar.nodesSelectionButton.menu.items.each(function (nodes_checkbox) {
                if (nodes_checkbox.checked) {
                    nodes_list_filter.push(nodes_checkbox.nodes);
                }
            });
        }
        ;


        var submitSettingsForm = this.toolbar.submitSettingsButton.menu.submitSettingsForm;
        var submit_settings = submitSettingsForm.getForm().getValues();

        //validate
        var invalid_message = '';

        [submitSettingsForm.periodicityComboBox, submitSettingsForm.repetitionsNumberField,
            submitSettingsForm.submitStartDateField].forEach(function (field) {
            var msg = '';
            if (field.validator && field.getValue) {
                var msg = field.validator(field.getValue());
                if (msg !== true) {
                    invalid_message += '<br>' + msg;
                }
            }
        });


        if (invalid_message !== '') {
            Ext.MessageBox.alert("Error", "Some input parameters are not valid!\n" + invalid_message);
            return;
        }

        //generate list
        var periodicity_type = submit_settings.periodicity_type;
        var submit_time_gen_way = submit_settings.submit_time_gen_way;
        var periodicity = 'None';
        if (periodicity_type === 'periodicity_type__periodic') {
            periodicity = submitSettingsForm.periodicityComboBox.getValue();
            if (this.newTasksGridPanel.periodicityComboBox.store.validateValue(periodicity) !== true) {
                Ext.MessageBox.alert('Error', 'Unknown format of periodicity: "' + periodicity + '"');
                return;
            }
        }
        var use_prefered_time = false;
        if (('use_prefered_time' in submit_settings) && submit_settings.use_prefered_time == 'on') {
            use_prefered_time = true;
        }


        var tasks_store = this.newTasksGridPanel.store;
        tasks_store.removeAll();

        var tasksRecordType = Ext.data.Record.create(['app', 'nnodes', 'submit_date', 'submit_time', 'repeat_in']);

        //get list of selected appkernels and respective nodes counts
        var appkernels_itemslist = this.toolbar.appkernelSelectionButton.get_selected_appkernels_items();
        var appkernels_list = [];
        var appkernels_nodelist = {};
        appkernels_itemslist.forEach(function (appkernel_checkbox) {
            if (appkernel_checkbox.checked) {
                var appkernel = appkernel_checkbox.appkernel;
                appkernels_list.push(appkernel);
                appkernels_nodelist[appkernel] = [];
                appkernel_checkbox.nodes_list.forEach(function (nodes_count) {
                    if (nodes_list_filter.indexOf(nodes_count) >= 0) {
                        appkernels_nodelist[appkernel].push(nodes_count);
                    }
                });
            }
        });
        if (appkernels_list.length == 0) {
            Ext.MessageBox.alert('Error', 'There is no application kernels in selection');
            return;
        }

        //populate task list
        var repetitions = 1;
        if (periodicity_type === 'periodicity_type__single_event') {
            repetitions = parseInt(submit_settings.repetitions);
        }

        //tasks store in following format [repeat number][appkernel][node] same for submit_time
        //this way we can set time in any convenient manner (for example submiting vary nodes firt and then applications)
        //and still add tasks to store in appkernel.node order
        var tasks = [];
        var submit_time = [];
        var number_of_tasks = 0;
        var number_of_tasks_in_one_round = 0;
        var irep;


        for (irep = 0; irep < repetitions; irep++) {
            number_of_tasks_in_one_round = 0;
            tasks.push({})
            submit_time.push({})
            appkernels_list.forEach(function (appkernel) {
                tasks[irep][appkernel] = {};
                submit_time[irep][appkernel] = {};

                appkernels_nodelist[appkernel].forEach(function (nodes_count) {
                    //set submit time for submit_time_gen_way__start_same_time
                    //if it is not the case it will be overwritten
                    submit_time[irep][appkernel][nodes_count] = new Date(submit_settings.submit_start_date + ' ' + submit_settings.submit_start_time);

                    tasks[irep][appkernel][nodes_count] = new tasksRecordType({
                        'app': appkernel,
                        'nnodes': parseInt(nodes_count),
                        'submit_date': '',
                        'submit_time': '',
                        'periodicity': periodicity
                    });
                    number_of_tasks++;
                    number_of_tasks_in_one_round++;
                })
            })
        }

        //set submit time if it is not start_same_time, which already done
        if (submit_time_gen_way != 'submit_time_gen_way__start_same_time') {
            if (use_prefered_time) {
                var start_time_ts = (new Date(submit_settings.submit_start_date + ' ' + submit_settings.submit_start_time)).getTime();
                var end_time_ts = (new Date(submit_settings.submit_end_date + ' ' + submit_settings.submit_end_time)).getTime();

                //aling prefered_time with submit_start_date
                var one_day = 3600 * 24 * 1000
                var prefered_time_start_ts = (new Date(submit_settings.submit_start_date + ' ' + submit_settings.prefered_time_start)).getTime();
                var prefered_time_end_ts = (new Date(submit_settings.submit_start_date + ' ' + submit_settings.prefered_time_end)).getTime();
                prefered_time_start_ts -= 2 * one_day;
                prefered_time_end_ts -= 2 * one_day;
                if (prefered_time_end_ts < prefered_time_start_ts) {
                    prefered_time_end_ts += one_day;
                }
                var prefered_dtime_ts = prefered_time_end_ts - prefered_time_start_ts;

                //now move over submit datetime window and find out time available for submitting tasks
                var windows_to_run = {
                    'start': [],
                    'end': [],
                    'dtime': [],
                    'window_time_start': [],
                    'window_time_end': []
                };
                var previous_window_end_ts = 0;
                var total_windows_time = 0;
                while (prefered_time_start_ts < end_time_ts) {
                    if (prefered_time_end_ts > start_time_ts) {
                        var window_start_ts = Math.max(prefered_time_start_ts, start_time_ts);
                        var window_end_ts = Math.min(prefered_time_end_ts, end_time_ts);
                        var window_dtime = window_end_ts - window_start_ts;

                        windows_to_run.start.push(window_start_ts);
                        windows_to_run.end.push(window_end_ts);
                        windows_to_run.dtime.push(window_end_ts - window_start_ts);
                        windows_to_run.window_time_start.push(previous_window_end_ts);
                        windows_to_run.window_time_end.push(previous_window_end_ts + window_dtime);
                        total_windows_time += window_dtime;
                        previous_window_end_ts += window_dtime;
                    }
                    prefered_time_start_ts += one_day;
                    prefered_time_end_ts += one_day;
                }

                //calculate submit time
                for (irep = 0; irep < repetitions; irep++) {
                    var window_time_per_repetition = total_windows_time / repetitions;
                    var window_time_start_ts = irep * window_time_per_repetition;
                    var window_time_end_ts = (irep + 1) * window_time_per_repetition;
                    var window_time_per_app_ts = (window_time_end_ts - window_time_start_ts) / number_of_tasks_in_one_round;
                    var itask = 0;

                    nodes_list_filter.forEach(function (nodes_count) {
                        appkernels_list.forEach(function (appkernel) {
                            if (nodes_count in tasks[irep][appkernel]) {
                                var window_time;
                                if (submit_time_gen_way == 'submit_time_gen_way__distrebute_evenly') {
                                    window_time = window_time_start_ts + itask * window_time_per_app_ts;
                                } else if (submit_time_gen_way == 'submit_time_gen_way__distrebute_randomly') {
                                    window_time = window_time_start_ts + Math.random() * window_time_per_repetition;
                                }
                                var iwindow = 0;
                                while (iwindow < windows_to_run.window_time_end.length && windows_to_run.window_time_end[iwindow] < window_time) {
                                    iwindow++;
                                }
                                if (iwindow >= windows_to_run.window_time_start.length) {
                                    iwindow = windows_to_run.window_time_start.length - 1;
                                }
                                var submit_real_time = window_time + windows_to_run.start[iwindow];
                                submit_time[irep][appkernel][nodes_count] = new Date(submit_real_time);
                                itask++;
                            }
                        })
                    })
                }
            } else { // i.e. do not use_prefered_time
                var start_time = new Date(submit_settings.submit_start_date + ' ' + submit_settings.submit_start_time);
                var end_time = new Date(submit_settings.submit_end_date + ' ' + submit_settings.submit_end_time);
                var start_time_ts = start_time.getTime();
                var end_time_ts = end_time.getTime();
                var dtime_ts = (end_time_ts - start_time_ts) / repetitions;

                for (irep = 0; irep < repetitions; irep++) {
                    var m_start_time_ts = start_time_ts + irep * dtime_ts;
                    var m_end_time_ts = m_start_time_ts + dtime_ts;
                    var m_dtime_ts = (m_end_time_ts - m_start_time_ts) / number_of_tasks_in_one_round;
                    var itask = 0;

                    nodes_list_filter.forEach(function (nodes_count) {
                        appkernels_list.forEach(function (appkernel) {
                            if (nodes_count in tasks[irep][appkernel]) {
                                var submit_real_time;
                                if (submit_time_gen_way == 'submit_time_gen_way__distrebute_evenly') {
                                    submit_real_time = m_start_time_ts + itask * m_dtime_ts;
                                } else if (submit_time_gen_way == 'submit_time_gen_way__distrebute_randomly') {
                                    submit_real_time = m_start_time_ts + Math.random() * dtime_ts;
                                }
                                submit_time[irep][appkernel][nodes_count] = new Date(submit_real_time);
                                itask++;
                            }
                        })
                    })
                }
            }
        }

        //finally add to store
        for (irep = 0; irep < repetitions; irep++) {
            appkernels_list.forEach(function (appkernel) {
                nodes_list_filter.forEach(function (nodes_count) {
                    if (nodes_count in tasks[irep][appkernel]) {
                        tasks[irep][appkernel][nodes_count].set('submit_date', submit_time[irep][appkernel][nodes_count].format(XDMoD.Arr.date_format));
                        tasks[irep][appkernel][nodes_count].set('submit_time', submit_time[irep][appkernel][nodes_count].format(XDMoD.Arr.time_format));

                        tasks_store.add(tasks[irep][appkernel][nodes_count]);
                    }
                })
            })
        }
        //enable submit button
        if (number_of_tasks > 0) {
            this.toolbar.submitTasksButton.enable();
            this.toolbar.deleteSelectedTasks.enable();
        } else {
            this.toolbar.submitTasksButton.disable();
            this.toolbar.deleteSelectedTasks.disable();
        }
    },
    _deleteSelectedTasks: function () {
        var newTasksGridPanel = this.newTasksGridPanel;
        var selected_rows = newTasksGridPanel.selModel.getSelections();
        if (selected_rows && selected_rows.length > 0) {
            Ext.MessageBox.confirm(
                'Do you want to delete tasks?',
                'Do you want to delete ' + selected_rows.length + ' task(s) from generated list?',
                function (answer) {
                    if (answer == 'yes') {
                        newTasksGridPanel.store.remove(selected_rows);
                    }
                }
            );
        }
    },
    __submitTasks__sent: function (response, options) {
        if (this.tasks_to_submit.length === 0) {
            Ext.MessageBox.alert("Done", "All tasks submitted.");
        } else {
            this.__submitTasks__sendnext();
        }
    },
    __submitTasks__sendnext: function () {
        var progressText = 'Submitting tasks...<br>' + (this.number_of_tasks_to_submit - this.tasks_to_submit.length) +
            ' out of ' + this.number_of_tasks_to_submit + ' is sent';

        Ext.MessageBox.show({
            msg: progressText,
            width: 300,
            wait: true,
            waitConfig: {
                interval: 200
            }
        });

        var self = this;
        if (this.tasks_to_submit == null) {
            console.log("Nothing to submit!");
            return;
        }
        param = this.tasks_to_submit.shift();

        Ext.Ajax.request({
            url: XDMoD.REST.url + '/akrr/tasks/scheduled?token=' + XDMoD.REST.token,
            method: 'POST',
            params: Ext.urlEncode(param),
            success: function (response, options) {
                self.__submitTasks__sent(response, options)
            },
            failure: function (response, options) {
                self.__submitTasks__failed(response, options)
            }
        });
    },
    __submitTasks__failed: function (response, options) {
        var message = '';
        if (response.responseText) {
            var response_data = JSON.parse(response.responseText);
            if (response_data.message) {
                message = response_data.message;
            }
        }
        this.tasks_to_submit = null;
        this.number_of_tasks_to_submit = null;
        Ext.MessageBox.alert("Error", "Can not submit tasks!\n<br>\n" + message);
    },
    _submitTasks: function () {
        var self = this;

        if (this.tasks_to_submit !== null) {
            console.log("Still submitting tasks!");
            return;
        }

        this.tasks_to_submit = [];

        var tasks_store = this.newTasksGridPanel.store;
        var group_name = this.toolbar.submitSettingsButton.menu.submitSettingsForm.groupNameField.getValue();

        //validate
        if (!this.selectedResource) {
            console.log("Resource is not selected");
            return;
        }

        //fill array of tasks to submit
        this.newTasksGridPanel.store.each(function (record) {
            var submit_data = new Date(record.get('submit_date') + ' ' + record.get('submit_time'));
            var periodicity = record.get('periodicity');
            if (periodicity == 'None') {
                periodicity = '';
            }
            var param = {
                repeat_in: periodicity,
                time_to_start: Ext.util.Format.date(submit_data, 'Y-m-d H:i:s'),
                resource: self.selectedResource,
                app_kernel: record.get('app'),
                resource_param: JSON.stringify({'nnodes': record.get('nnodes')}),
                app_param: '',
                task_param: '',
                group_id: group_name
            };
            self.tasks_to_submit.push(param);
        });

        //submit tasks one by one
        this.number_of_tasks_to_submit = this.tasks_to_submit.length;
        this.__submitTasks__sendnext();
    }
});