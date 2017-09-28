/*  
 * JavaScript Document
 * @author Joe White
 * @date 2014-09-10
 *
 * the panel for chosing chart export settings
 */
CCR.xdmod.ui.ExportPanel = function (config) {
    CCR.xdmod.ui.ExportPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.ExportPanel

Ext.ns('Ext.ux.form'); // set up Ext.ux.form namespace

/**
 *  * @class Ext.ux.form.Spacer
 *   * @extends Ext.BoxComponent
 *    * Utility spacer class.
 *     * @constructor
 *      * @param {Number} height (optional) Spacer height in pixels (defaults to 11).
 *       */
Ext.ux.form.Spacer = Ext.extend(Ext.BoxComponent, {
      height: 11,
      autoEl: 'div' // thanks @jack =)
});
Ext.reg('spacer', Ext.ux.form.Spacer);

Ext.apply(CCR.xdmod.ui.ExportPanel, {
    format_types: [
        ['png', 'PNG - Portable Network Graphics'],
        ['svg', 'SVG - Scalable Vector Graphics'],
        ['csv', 'CSV - Comma Separated Values'],
        ['xml', 'XML - Extensible Markup Language'],
        ['pdf', 'PDF - Portable Document Format']
    ],
    format_types_noimg: [
        ['csv', 'CSV - Comma Separated Values'],
        ['xml', 'XML - Extensible Markup Language']
    ],
    template_types: [
        ['small', 'Small' ],
        ['medium', 'Medium' ],
        ['large', 'Large' ],
        ['poster', 'Poster' ],
        ['custom', 'Custom' ]
    ]
});
Ext.extend(CCR.xdmod.ui.ExportPanel, Ext.Panel, {
    active_role: 'po',
    settings: null,
    lastformatSetting: null,
    imageExportAllowed: null,
    allowImageExport: function(allow) {
        if( allow == this.imageExportAllowed) {
            return;
        }
        var cachedFormat = this.lastformatSetting;
        this.lastformatSetting = this.formatTypeCombo.getValue();

        if(allow) {
            this.formatTypeCombo.store.loadData(CCR.xdmod.ui.ExportPanel.format_types);
            this.settings.format = cachedFormat;
        } else {
            this.formatTypeCombo.store.loadData(CCR.xdmod.ui.ExportPanel.format_types_noimg);
            this.settings.format = cachedFormat;
        }
        this.formatTypeCombo.setValue(this.settings.format);
        this.setupDisplay.call(this, this.settings.format);
        this.imageExportAllowed = allow;
    },
    setupDisplay: function(format_type) {
        // Define which fields are to be shown for each export type. (Note that the xml
        // and csv exports have no fields shown).
        var fieldSettings = {
            width_inches: ['pdf'],
            height_inches: ['pdf'],
            font_pt: ['pdf'],
            show_title: ['pdf', 'png', 'svg'],
            image_size: ['png', 'svg']
        };

        var field;
        for (field in fieldSettings) {
            if (fieldSettings.hasOwnProperty(field)) {
                this.form.findField(field).setVisible(fieldSettings[field].includes(format_type));
            }
        }
    },
    initComponent: function () {

        this.settings = { format: 'png', showtitle: true, height: 484, width: 916, font_size: 0, scale: 1};
        this.template = 'medium';
        this.imageExportAllowed = true;
        this.lastformatSetting = 'csv';

        this.showTitleCheckbox = new Ext.form.Checkbox({
            fieldLabel: 'Chart title',
            name: 'show_title',
            xtype: 'checkbox',
            boxLabel: 'Show chart title',
            checked: this.settings.showtitle,
            disabled: false
        });

        this.widthTextBox = new Ext.form.NumberField( {
            fieldLabel: 'Image width',
            name: 'image_width',
            minValue: 1,
            maxValue: 40000,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 100,
            accelerate: true,
            width: 24,
            value: this.settings.width,
            hidden: true
        });

        this.heightTextBox = new Ext.form.NumberField( {
            fieldLabel: 'Image height',
            name: 'image_height',
            minValue: 1,
            maxValue: 40000,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 100,
            accelerate: true,
            width: 24,
            value: this.settings.height,
            hidden: true
        });

        this.fontSizeSlider = new Ext.slider.SingleSlider({
            fieldLabel: 'Font Size',
            name: 'font_size',
            minValue: -5,
            maxValue: 10,
            value: this.settings.font_size,
            increment: 1,
            plugins: new Ext.slider.Tip(),
            hidden: true
        });

        this.enableCustom = function(enable) {
            /* 
             * Note: the textboxes are setDisabled when they are hidden so that
             * they are ignored by the form validator.
             */
            if(enable) {
                this.widthTextBox.enable();
                this.widthTextBox.show();
                this.heightTextBox.enable();
                this.heightTextBox.show();
                this.fontSizeSlider.enable();
                this.fontSizeSlider.show();
            }
            else {
                this.widthTextBox.hide();
                this.widthTextBox.disable();
                this.heightTextBox.hide();
                this.heightTextBox.disable();
                this.fontSizeSlider.hide();
                this.fontSizeSlider.disable();
            } 
        };

        this.templateTypeCombo = new Ext.form.ComboBox( {
            flex: 2.5,
            fieldLabel: 'Image Size',
            name: 'image_size',
            xtype: 'combo',
            mode: 'local',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'text'
                ],
                data: CCR.xdmod.ui.ExportPanel.template_types
            }),
            disabled: false,
            value: 'medium',
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            itemId: 'templateTypeCombo',
            listeners: {
                scope: this,
                'select': function (combo, record, index) {
                    var templ = record.get('id');
                    if(templ == 'custom') {
                        this.enableCustom.call(this, true);
                    } else {
                        this.enableCustom.call(this, false);
                        switch(templ) {
                            case 'small':
                                this.widthTextBox.setValue(640);
                                this.heightTextBox.setValue(380);
                                break;
                            case 'medium':
                                this.widthTextBox.setValue(916);
                                this.heightTextBox.setValue(484);
                                break;
                            case 'large':
                                this.widthTextBox.setValue(1280);
                                this.heightTextBox.setValue(720);
                                break;
                            case 'poster':
                                this.widthTextBox.setValue(1920);
                                this.heightTextBox.setValue(1080);
                                break;
                        }
                    }
                },
                'beforeshow': function() {
                    this.enableCustom.call(this, this.templateTypeCombo.getValue() == 'custom');
                },
                'beforehide': function() {
                    this.enableCustom.call(this, false);
                }
            }
        }); 
        
        this.formatTypeCombo = new Ext.form.ComboBox( {
            flex: 2.5,
            fieldLabel: 'Format',
            name: 'format_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'text'
                ],
                data: CCR.xdmod.ui.ExportPanel.format_types
            }),
            disabled: false,
            value: this.settings.format,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',
            listeners: {
                scope: this,
                'select': function (combo, record, index) {
                    this.setupDisplay.call(this, record.get('id') );
                }
            }
        });
        var formpanel = new Ext.FormPanel({
            labelWidth: 125, // label settings here cascade unless overridden
            bodyStyle: 'padding:5px 5px 0',
            monitorValid: true,
            defaults: {
                width: 225,
                anchor: 0
            },
            items: [
                this.formatTypeCombo,
                { xtype: 'spacer' },
                this.showTitleCheckbox,
                this.templateTypeCombo,
                this.widthTextBox,
                this.heightTextBox,
                this.fontSizeSlider,
                {
                    xtype: 'numberfield',
                    name: 'width_inches',
                    fieldLabel: 'Width (inches)',
                    allowDecimals: true,
                    decimalPrecision: 2,
                    hidden: true,
                    minValue: 0.01,
                    maxValue: 1000000000,
                    width: 36,
                    value: 6.00
                },
                {
                    xtype: 'numberfield',
                    name: 'height_inches',
                    fieldLabel: 'Height (inches)',
                    allowDecimals: true,
                    decimalPrecision: 2,
                    hidden: true,
                    minValue: 0.01,
                    maxValue: 1000000000,
                    width: 36,
                    value: 3.20
                },
                {
                    xtype: 'numberfield',
                    name: 'font_pt',
                    fieldLabel: 'Font size (pt)',
                    allowDecimals: true,
                    decimalPrecision: 1,
                    hidden: true,
                    minValue: 0.1,
                    maxValue: 100,
                    width: 36,
                    value: 8
                }
            ],
            buttons: [{
                scope: this,
                text: 'Export',
                formBind: true,
                handler: function (b, e) {
                    var SVG_DPI = 90.0;
                    var PS_PPI = 72.0;
                    var PX_TO_XDMOD_FONT = -12;

                    var formVals = this.form.getFieldValues();
                    if (formVals.format_type === 'pdf') {
                        this.settings.scale = 2.5;
                        this.settings.format = formVals.format_type;
                        this.settings.showtitle = this.showTitleCheckbox.getValue();
                        this.settings.width = Math.round(formVals.width_inches * SVG_DPI);
                        this.settings.height = Math.round(formVals.height_inches * SVG_DPI);
                        this.settings.font_size = Math.round((this.settings.scale * SVG_DPI * formVals.font_pt) / PS_PPI) + PX_TO_XDMOD_FONT;
                        this.template = 'custom';
                    } else {
                        this.settings.scale = 1;
                        this.settings.format = this.formatTypeCombo.getValue();
                        this.settings.showtitle = this.showTitleCheckbox.getValue();
                        this.settings.width = this.widthTextBox.getValue();
                        this.settings.height = this.heightTextBox.getValue();
                        this.settings.font_size = this.fontSizeSlider.getValue();
                        this.template = this.templateTypeCombo.getValue();
                    }

                    var params = { 
                        format: this.settings.format, 
                        show_title: this.settings.showtitle ? 'y' : 'n',
                        width: this.settings.width,
                        height: this.settings.height,
                        scale: this.settings.scale,
                        inline: 'n'
                    };

                    if(this.template == 'custom' ) {
                        params.font_size = this.settings.font_size;
                    }

                    b.scope.export_function(params);
                }
            }, {
                scope: this,
                text: 'Cancel',
                handler: function (b, e) {
                    this.showTitleCheckbox.setValue(this.settings.showtitle);
                    this.widthTextBox.setValue(this.settings.width);
                    this.heightTextBox.setValue(this.settings.height);
                    this.fontSizeSlider.setValue(this.settings.font_size);
                    this.templateTypeCombo.setValue(this.template);

                    this.formatTypeCombo.setValue(this.settings.format);
                    this.setupDisplay.call(this, this.settings.format);

                    b.scope.cancel_function();
                }
            }]
        });
        this.form = formpanel.getForm();
        Ext.apply(this, {
            items: [formpanel],
            layout: 'fit',
            width: 400,
            height: 300,
            border: false
        });
        CCR.xdmod.ui.ExportPanel.superclass.initComponent.apply(this, arguments);
    }
});
