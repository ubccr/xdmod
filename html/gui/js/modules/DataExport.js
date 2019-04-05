XDMoD.Module.DataExport = Ext.extend(XDMoD.PortalModule, {

    title: 'Data Export', // <-- rename this

    module_id: 'data_export', // <-- rename this (see the section on REPORT CHECKBOX for how to name this)

    usesToolbar: false,

    // ------------------------------------------------------------------

    initComponent: function () {

        this.on('role_selection_change', function (config) {

            /*

               Fired upon selecting an entry from the Role menu

               'config' represents the configuration of the selected menu entry

               The properties which will be of use are the following:

                  config.text  (the label of the selected item)
                  config.value (the internal value associated with the selected item)

            */

        }); //role_selection_change

        // ==============================================

        this.on('duration_change', function (config) {

            /*

               Fired upon any of the following actions:
                  - Selecting a duration preset
                  - Selecting an aggregation unit
                  - Pressing ENTER to confirm an updated (and valid) date in the Start or End field
                  - Pressing the Refresh button

               'config' is an object with the following details:

               {
                  aggregation_unit: ______,
                  preset: _____,
                  start_date: YYYY-MM-DD,
                  end_date: YYYY-MM-DD
               }

            */

        }); //duration_change

        // ==============================================

        this.on('export_option_selected', function (config) {

            /*

               Fired upon selecting an entry from the Export menu

               'config' represents the export parameters associated with the selected
               entry, and is an object with the following details:

               {
                  format:     ____,
                  width:      ____,
                  height:     ____,
                  inline:     ____,
                  scale:      ____,
                  show_title: ____,
               }

            */

        }); //export_option_selected

        // ==============================================

        this.on('print_clicked', function () {

            /*

               Fired upon clicking the Print button

            */

        }); //print_clicked

        // ==============================================

        /*

           REPORT CHECKBOX

           Setting the check state of the 'Available For Report' checkbox is handled manually:

           self.getReportCheckbox().storeChartArguments(chart_args, title, subtitle, start_date, end_date, included_in_report);

           When the above call is made, the included_in_report value (either 'y' or 'n') determines whether the 'Available For Report'
           checkbox is checked or not.  When the user manually checks the 'Available For Report' checkbox, the chart arguments cached
           via the last call to storeChartArguments() will be used.

           It is important that the module_id set in the configuration to this XDMoD.PortalModule subclass be unique among all other
           XDMoD.PortalModule subclasses.  The reporting layer relies on distinct module_id(s) to function properly.

           NOTE: The value of module_id needs to match the base name of the corresponding controller used to serve up the chart data.

           e.g.  If your module consults 'controllers/abc.php' to get its chart data, and you want to add that chart data to a report,
                 the module_id needs to be named 'abc'

        */

        // ==============================================

        var mainArea = new Ext.Panel({

            region: 'center',
            html: 'Batch Data Warehouse Exporter'

        }); //mainArea

        // ==============================================

        var downloadButton = new Ext.Button({

            text: 'Download'

        }); //customToolbarComponent

        var submitButton = new Ext.Button({

            text: 'Submit'

        }); //customToolbarComponent

        var downloadOptions = new Ext.FormPanel({
            labelWidth: 75, // label settings here cascade unless overridden
            frame: true,
            title: 'Export Options',
            bodyStyle: 'padding:5px 5px 0',
            width: 350,
            defaults: {
                width: 230
            },
            defaultType: 'textfield',

            items: [{
                fieldLabel: 'Desired Realm',
                name: 'first',
                allowBlank: false
            }, new Ext.form.DateField({
                fieldLabel: 'Start Date',
                name: 'startDate',
            }),new Ext.form.DateField({
                fieldLabel: 'End Date',
                name: 'endDate',
            })],

            buttons: [{
                text: 'Save'
            }, {
                text: 'Cancel'
            }]
        });

        // ==============================================

        Ext.apply(this, {

            /*

            If custom components are to be placed in the toolbar, you can specify where they are to be placed, relative
            to the components available to you by default.  Simply define a 'customOrder' property, which is an array
            representing the order in which the components are to be placed/arranged.

            customOrder: [

               XDMoD.ToolbarItem.DURATION_SELECTOR,
               XDMoD.ToolbarItem.EXPORT_MENU,
               customToolbarComponent,
               XDMoD.ToolbarItem.PRINT_BUTTON,
               XDMoD.ToolbarItem.REPORT_CHECKBOX

            ],

            The top-down ordering of the components in the 'customOrder' array corresponds to the left-right ordering of
            the components in the top toolbar.  In this example, the 'Custom' button (specific to this module) is placed
            to the right of the Export menu and to the left of the Print button.

            */

            items: [
                mainArea,
                downloadOptions,
                submitButton,
                downloadButton
            ]

        }); //Ext.apply

        XDMoD.Module.DataExport.superclass.initComponent.apply(this, arguments);

    }, //initComponent

}); //XDMoD.Module.DataExport
