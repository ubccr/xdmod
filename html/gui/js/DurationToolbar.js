/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Jan-05 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
* 
* This class contains the funcationality for duration selection toolbar in xdmod.
* It extends Ext.Toolbar and thus one can add items to it. For items to be prepended, 
* use the items attribute. Items after the duration stuff can be added via the Ext.Toolbar
* funcations.
* 

This is how you include it:
<script type="text/javascript" src="gui/js/DurationToolbar.js"></script>


This is how you instantiate it:

this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
	id: 'duration_selector_' +  this.id,
	alignRight: false,
	showRefresh: false,
	showAggregationUnit: true,
	items: [' ','Role:', ' ',this.roleCategorySelectorButton, '-'], //here i am adding stuff to the toolbar on the left of the duration stuff
	handler: function()
	{
		//This is were you handle what happens on change: ie:
		//this.saveQuery.call(this,100);
		//this.reloadChart.call(this,200);
	},
	scope: this //also scope of handle
});



This is how you get the values:

var aggregation_unit = this.durationToolbar.getAggregationUnit();
var start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
var end_date =  this.durationToolbar.getEndDate().format('Y-m-d');


This is how you set the values, the format is Y-m-d for dates and {Day, Month, Quarter, Year} as string literals for aggregation_unit:

this.durationToolbar.setValues(config.start_date,config.end_date,config.aggregation_unit);



This is how you make sure its valid before doing something:

chartStore.on('beforeload', function()
{
	if(!this.durationToolbar.validate()) return;
	this.mask('Loading...');
	plotlyPanel.un('resize', onResize, this);
	
	chartStore.baseParams = {};
	Ext.apply(chartStore.baseParams, getBaseParams.call(this));
...
		
},this);

*/
CCR.xdmod.ui.DurationToolbar = function (config) {
    CCR.xdmod.ui.DurationToolbar.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.DurationToolbar

/*
 * Class members (static)
 */
Ext.apply(CCR.xdmod.ui.DurationToolbar, {

    // Returns all the values for the canned dates drop down.
    getDateRanges: function () {
        var today = new Date();
        var yesterday = today.add(Date.DAY, -1);
        var lastWeek = today.add(Date.DAY, -7);
        var lastMonth = today.add(Date.DAY, -30);
        var lastQuarter = today.add(Date.DAY, -90);
        var lastYear = today.add(Date.YEAR, -1);
        var yearToDate = today.add(Date.DAY, -1 * today.getDayOfYear());
        var thisYearStart = today.add(Date.DAY, -1 * today.getDayOfYear());
        var thisYearEnd = new Date(thisYearStart.getFullYear(), 11, 31, 23, 59, 59, 999);
        var last2Year = today.add(Date.YEAR, -2);
        var last3Year = today.add(Date.YEAR, -3);
        var last5Year = today.add(Date.YEAR, -5);
        var last10Year = today.add(Date.YEAR, -10);

        var thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);

        var thisQuarterStart = new Date(today.getFullYear(), today.getMonth() - (today.getMonth() % 3), 1);
        var thisQuarterEnd = new Date(today.getMonth() < 9 ? today.getFullYear() : today.getFullYear() + 1, (today.getMonth() - (today.getMonth() % 3) + 3) % 12, 1).add(Date.DAY, -1);
        var lastQuarterStart = new Date(today.getMonth() < 3 ? today.getFullYear() - 1 : today.getFullYear(), (today.getMonth() - (today.getMonth() % 3) + 9) % 12, 1);
        var lastQuarterEnd = new Date(thisQuarterStart).add(Date.DAY, -1);

        var lastFullMonthStart = new Date(today.getMonth() < 1 ? today.getFullYear() - 1 : today.getFullYear(), today.getMonth() < 1 ? 11 : (today.getMonth() - 1), 1);
        var lastFullMonthEnd = thisMonthStart.add(Date.DAY, -1);

        var oneYearAgoStart = new Date(today.getFullYear() - 1, 0, 1);
        var oneYearAgoEnd = new Date(today.getFullYear() - 1, 11, 31);

        var twoYearAgoStart = new Date(today.getFullYear() - 2, 0, 1);
        var twoYearAgoEnd = new Date(today.getFullYear() - 2, 11, 31);

        var threeYearAgoStart = new Date(today.getFullYear() - 3, 0, 1);
        var threeYearAgoEnd = new Date(today.getFullYear() - 3, 11, 31);

        var fourYearAgoStart = new Date(today.getFullYear() - 4, 0, 1);
        var fourYearAgoEnd = new Date(today.getFullYear() - 4, 11, 31);

        var fiveYearAgoStart = new Date(today.getFullYear() - 5, 0, 1);
        var fiveYearAgoEnd = new Date(today.getFullYear() - 5, 11, 31);

        var sixYearAgoStart = new Date(today.getFullYear() - 6, 0, 1);
        var sixYearAgoEnd = new Date(today.getFullYear() - 6, 11, 31);

        // NOTE: Changes to this array may affect the default duration.
        // Please ensure defaultCannedDateIndex points to the correct entry.
        return [{
                text: 'Yesterday',
                start: yesterday,
                end: yesterday
            }, {
                text: '7 day',
                start: lastWeek,
                end: today
            }, {
                text: '30 day',
                start: lastMonth,
                end: today
            }, {
                text: '90 day',
                start: lastQuarter,
                end: today
            }, {
                text: 'Month to date',
                start: thisMonthStart,
                end: today
            }, {
                text: 'Previous month',
                start: lastFullMonthStart,
                end: lastFullMonthEnd
            }, {
                text: 'Quarter to date',
                start: thisQuarterStart,
                end: today
            }, {
                text: 'Previous quarter',
                start: lastQuarterStart,
                end: lastQuarterEnd
            }, {
                text: 'Year to date',
                start: thisYearStart,
                end: today
            }, {
                text: 'Previous year',
                start: oneYearAgoStart,
                end: oneYearAgoEnd
            }, {
                text: '1 year',
                start: lastYear,
                end: today
            }, {
                text: '2 year',
                start: last2Year,
                end: today
            }, {
                text: '3 year',
                start: last3Year,
                end: today
            }, {
                text: '5 year',
                start: last5Year,
                end: today
            }, {
                text: '10 year',
                start: last10Year,
                end: today
            },
            {
                text: thisYearStart.getFullYear(),
                start: thisYearStart,
                end: thisYearEnd
            }, {
                text: '' + oneYearAgoStart.getFullYear(),
                start: oneYearAgoStart,
                end: oneYearAgoEnd
            }, {
                text: '' + twoYearAgoStart.getFullYear(),
                start: twoYearAgoStart,
                end: twoYearAgoEnd
            }, {
                text: '' + threeYearAgoStart.getFullYear(),
                start: threeYearAgoStart,
                end: threeYearAgoEnd
            }, {
                text: '' + fourYearAgoStart.getFullYear(),
                start: fourYearAgoStart,
                end: fourYearAgoEnd
            }, {
                text: '' + fiveYearAgoStart.getFullYear(),
                start: fiveYearAgoStart,
                end: fiveYearAgoEnd
            }, {
                text: '' + sixYearAgoStart.getFullYear(),
                start: sixYearAgoStart,
                end: sixYearAgoEnd
            }
        ];
    }
});

/*
 * Instance stuff
 */
Ext.extend(CCR.xdmod.ui.DurationToolbar, Ext.Toolbar, {
    defaultCannedDateIndex: 5, //use this to change the default one thats selected. Its an index into the array returned by CCR.xdmod.ui.DurationToolbar.getDateRanges() ^^^ above
    defaultAggregationUnit: 'Auto',
    singleRow: true,
    showRefresh: true,
    alignRight: false,
    showAggregationUnit: true,
    disable: function () {
        if (this.cannedDateButton.menu.isVisible()) {
            this.cannedDateButton.menu.wasVisible = this.cannedDateButton.menu.isVisible();
            if (this.cannedDateButton.menu.wasVisible) {
                this.cannedDateButton.menu.pos = this.cannedDateButton.menu.getPosition(false);
                this.cannedDateButton.menu.temporaryInvisible = true;
                this.cannedDateButton.menu.setVisible(false);
            }
            this.cannedDateButton.menu.setDisabled(true);
        }
    },
    enable: function () {
        this.cannedDateButton.menu.setDisabled(false);
        if (this.cannedDateButton.menu.wasVisible) {
            this.cannedDateButton.menu.wasVisible = false;
            this.cannedDateButton.menu.showAt(this.cannedDateButton.menu.pos);
        }
    },
    findCustomDateMenuItem: function(customDateRange) {
        return this.cannedDateButton.menu.items.find(function (i) {
            return i.group == 'canned_date' + this.id && i.text == customDateRange;
        }, this);
    },
    getStartDate: function () {
        return this.startDateField.getValue();
    },
    getEndDate: function () {
        return this.endDateField.getValue();
    },
    getDurationLabel: function () {
        return this.cannedDateButton.getText();
    },
    onHandle: function (refreshButtonClicked) {
        var changed = true;
        if (refreshButtonClicked) {
            changed = this.startDateField.didChange() || this.endDateField.didChange();
        }
        var refreshBtn = Ext.getCmp('refresh_button_' + this.id);
        if (refreshBtn) refreshBtn.removeClass('dateframe_refresh_button_highlight');
        Ext.getCmp('start_field_' + this.id).updatePreviousValue();
        Ext.getCmp('end_field_' + this.id).updatePreviousValue();
        var errorMessage = '';
        var errors = this.startDateField.getErrors();
        for (var i = 0; i < errors.length; i++) {
            errorMessage += errors[i] + '.<br/>';
        }
        errors = this.endDateField.getErrors();
        for (var i = 0; i < errors.length; i++) {
            errorMessage += errors[i] + '.<br/>';
        }
        if (errorMessage != '') {
            return;
        }
        errorMessage = this.validateAggregationUnit();
        if (errorMessage != '') {
            return;
        }
        if (this.handler) {
            this.handler.call(this.scope || this, {
                preset: this.getDurationLabel(),
                changed: changed,
                aggregation_unit: this.getAggregationUnit(),
                start_date: this.getStartDate().format('Y-m-d'),
                end_date: this.getEndDate().format('Y-m-d')
            });
        } //if (this.handler)
    },

    setValues: function (startDate, endDate, aggregationUnit, label, triggerHandler) {
        if (aggregationUnit == null) {
            aggregationUnit = this.getAggregationUnit();
        }
        this.startDateField.setDisabled(false);
        this.endDateField.setDisabled(false);
        var presetDateRangeSelected = this.selectCustomDateRange(label);
        if (!presetDateRangeSelected) {
            var parsedStartDate = Date.parseDate(startDate, 'Y-m-d');
            var parsedEndDate = Date.parseDate(endDate, 'Y-m-d');
            this.endDateField.setValue(parsedEndDate);
            this.startDateField.setValue(parsedStartDate);
        }
        this.validate();
        if (!aggregationUnit || aggregationUnit.length < 1) {
            aggregationUnit = 'Auto';
        } else {
            aggregationUnit = aggregationUnit.charAt(0).toUpperCase() + aggregationUnit.slice(1); //capitalize first letter
        }
        this.setAggregationUnit(aggregationUnit);
        if (triggerHandler && triggerHandler === true) {
            this.onHandle();
        }
    },
    selectCustomDateRange: function (label) {
        var customDateRange;
        var customDateMenuItem;
        if (label) {
            customDateRange = label;
            customDateMenuItem = this.findCustomDateMenuItem(customDateRange);
        }
        if (!customDateMenuItem) {
            customDateRange = 'User Defined';
            customDateMenuItem = this.findCustomDateMenuItem(customDateRange);
        }

        customDateMenuItem.setChecked(true);
        var presetDateRangeSelected = customDateRange !== 'User Defined';
        if (presetDateRangeSelected) {
            this.startDateField.setValue(customDateMenuItem.start);
            this.endDateField.setValue(customDateMenuItem.end);
        }
        this.cannedDateButton.setText(customDateRange);

        return presetDateRangeSelected;
    },
    validate: function () {
        return this.startDateField.validate() && this.endDateField.validate();
    },
    validateAggregationUnit: function (targetAggregationUnit) {
        if (targetAggregationUnit == null) {
            targetAggregationUnit = this.getAggregationUnit();
        }
        var startDate = this.startDateField.getValue();
        var endDate = this.endDateField.getValue().add(Date.HOUR, 23).add(Date.MINUTE, 59).add(Date.SECOND, 59);
        var dateDiff = endDate - startDate;
        var dayDiff = (24 * 60 * 60 * 1000 - 1);
        var weekDiff = (7 * 24 * 60 * 60 * 1000 - 1);
        var monthDiff = (28 * 24 * 60 * 60 * 1000 - 1);
        var quarterDiff = (120 * 24 * 60 * 60 * 1000 - 1);
        var yearDiff = (365 * 24 * 60 * 60 * 1000 - 1);
        var errorMessage = '';
		if(!CCR.xdmod.ui.isManager) {
			if ((targetAggregationUnit == 'Day' && dateDiff < dayDiff) || (targetAggregationUnit == 'Week' && dateDiff < weekDiff) || (targetAggregationUnit == 'Month' && dateDiff < monthDiff) || (targetAggregationUnit == 'Quarter' && dateDiff < quarterDiff) || (targetAggregationUnit == 'Year' && dateDiff < yearDiff)) {
				errorMessage = 'The aggregation unit cannot be of greater duration than the selected date range.<br/>Using "Auto" unit instead.';
				this.setAggregationUnit('Auto');
				this.onHandle();
			}
			var diffInDays = dateDiff / (24 * 60 * 60 * 1000);
			if (diffInDays > 750 && targetAggregationUnit == 'Day') {
				errorMessage = 'The aggregation unit cannot be set to "Day" for durations greater than 750 days.<br/>Using "Auto" unit instead.';
				this.setAggregationUnit('Auto');
				this.onHandle();
			}
			if (diffInDays > 366 * 10 && targetAggregationUnit == 'Month') {
				errorMessage = 'The aggregation unit cannot be set to "Month" for durations greater than 10 years.<br/>Using "Auto" unit instead.';
				this.setAggregationUnit('Auto');
				this.onHandle();
			}
		} 
        return errorMessage;

    },

    setAggregationUnit: function (unitName) {
        var checkedItem = this.aggregationUnitButton.menu.items.find(function (i) {
            return i.checked === true;
        });

        var aggregationUnitMenuItem = this.aggregationUnitButton.menu.items.find(function (i) {
            return i.group == 'aggregation_unit' + this.id && i.text == unitName;
        }, this);

        aggregationUnitMenuItem.setChecked(true, true);
        this.aggregationUnitButton.setText(unitName);
    },

    getAggregationUnit: function () {
        return this.aggregationUnitButton.getText();
    },
    getCannedDateText: function () {
        return this.cannedDateButton.getText();
    },
    serialize: function (includeCannedLabel) {
        var ret = this.getStartDate().format('Y-m-d') + '/' + this.getEndDate().format('Y-m-d') + '/' + this.getAggregationUnit();
        if (includeCannedLabel == true) {
            ret += '/' + this.getCannedDateText();
        }
        return ret;
    },
    unserialize: function (s) {
        var values = s.split('/');
        if (values.length == 3) {
            this.setValues(values[0], values[1], values[2]);
        } else if (values.length == 4) {
            this.setValues(values[0], values[1], values[2], values[3]);
        } else {
            Ext.MessageBox.alert('Duration Selector', 'unserialize error: string passed in must have 3 or 4 sections split by /');
        }
    },
    initComponent: function () {
        this.dateRanges = CCR.xdmod.ui.DurationToolbar.getDateRanges();
        this.defaultCannedDate = this.dateRanges[this.defaultCannedDateIndex].text;
        var minDate = Date.parseDate(this.minDateString, 'Y-m-d');
        var maxDate = Date.parseDate(this.maxDateString, 'Y-m-d');
        var minText = 'There is no data available before ' + this.minDateString;
        var maxText = 'There is no data available beyond ' + this.maxDateString;
        var self = this;

        var dateFieldValidator = function (field_id, label) {
            var validDates = {
                startDateField: Date.parseDate(self.startDateField.getRawValue(), 'Y-m-d'),
                endDateField: Date.parseDate(self.endDateField.getRawValue(), 'Y-m-d')
            };
            if (validDates[field_id] == undefined)
                return 'Invalid ' + label + ' (must be of the form YYYY-MM-DD)';
            if (validDates.startDateField != undefined && validDates.endDateField != undefined) {
                if (validDates.startDateField > validDates.endDateField)
                    return 'Start date must be less than or equal to the end date';
                else
                    return true;
            }

            // At this point, it's the other field which is not valid.
            // We've already established that the target field (field_id) is valid
            return true;
        }; //dateFieldValidator

        function dateFieldKeyupHandler(e, f) {
            if (self.startDateField.didChange() == true || self.endDateField.didChange() == true)
                self.refreshButton.addClass('dateframe_refresh_button_highlight');
            else
                self.refreshButton.removeClass('dateframe_refresh_button_highlight');
            self.selectCustomDateRange();
            if (e && e.getKey() == e.ENTER && f.didChange() == true) {
                // Explicitly force the fields to auto-correct the date
                self.startDateField.setValue(self.startDateField.getRawValue());
                self.endDateField.setValue(self.endDateField.getRawValue());
                self.onHandle();
            } //if (e.getKey() == e.ENTER && f.didChange() == true)                          
        } //dateFieldKeyupHandler
        var startDateFieldEventHandler = function (e, f) {
            self.endDateField.validate();
            if (f.isValid() && self.endDateField.isValid())
                dateFieldKeyupHandler(e, f);
        }; //startDateFieldEventHandler
        var endDateFieldEventHandler = function (e, f) {
            self.startDateField.validate();
            if (f.isValid() && self.startDateField.isValid())
                dateFieldKeyupHandler(e, f);
        }; //endDateFieldEventHandler

        this.startDateField = new CCR.xdmod.ui.CustomDateField({
            id: 'start_field_' + this.id,
            tooltip: 'Configure start date',
            format: 'Y-m-d',
            altFormats: 'm/d/Y|n/j/Y|n/j/y|m/j/y|n/d/y|m/j/Y|n/d/Y|m-d-y|m-d-Y|Y-m-d|Y-n-j|Y-n-d|Y-m-j',
            fieldLabel: "Start Date",
            value: this.dateRanges[this.defaultCannedDateIndex].start,
            allowBlank: false,
            enableKeyEvents: true,
            validator: function (s) {
                return dateFieldValidator('startDateField', 'start date');
            },
            listeners: {
                'keyup': function (f, e) {
                    startDateFieldEventHandler(e, f);
                }, //keyup
                'select': function (f) {
                    startDateFieldEventHandler(undefined, f);
                } //select
            } //listeners
        });
        this.endDateField = new CCR.xdmod.ui.CustomDateField({
            id: 'end_field_' + this.id,
            tooltip: 'Configure end date',
            format: 'Y-m-d',
            altFormats: 'm/d/Y|n/j/Y|n/j/y|m/j/y|n/d/y|m/j/Y|n/d/Y|m-d-y|m-d-Y|Y-m-d|Y-n-j|Y-n-d|Y-m-j',
            fieldLabel: "End Date",
            value: this.dateRanges[this.defaultCannedDateIndex].end,
            allowBlank: false,
            enableKeyEvents: true,
            validator: function (e) {
                return dateFieldValidator('endDateField', 'end date');
            },
            listeners: {
                'keyup': function (f, e) {
                    endDateFieldEventHandler(e, f);
                }, //keyup

                'select': function (f) {
                    endDateFieldEventHandler(undefined, f);
                } //select
            } //listeners

        });

        this.startDateField.endDateField = this.endDateField;
        this.endDateField.startDateField = this.startDateField;

        this.startDateField.parent = this;
        this.endDateField.parent = this;

        this.aggregationUnitItems = [
            '<b class="menu-title">Aggregation Units:</b><br/>', {
                xtype: 'menucheckitem',
                text: 'Auto',
                scope: this,
                group: 'aggregation_unit' + this.id,
                checked: this.defaultAggregationUnit == 'Auto',
                checkHandler: function (t, checked) {
                    if (!checked) return;
                    this.aggregationUnitButton.setText('Auto');
                    this.onHandle();
                }
            }, {
                xtype: 'menucheckitem',
                text: 'Day',
                scope: this,
                group: 'aggregation_unit' + this.id,
                checked: this.defaultAggregationUnit == 'Day',
                checkHandler: function (t, checked) {
                    if (!checked) return;
                    var errorMessage = this.validateAggregationUnit('Day');
                    if (errorMessage != '') {
                        Ext.MessageBox.alert('Aggregation unit selection error', errorMessage);
                        return;
                    }
                    this.aggregationUnitButton.setText('Day');
                    this.onHandle();
                }
            }, {
                xtype: 'menucheckitem',
                text: 'Month',
                scope: this,
                group: 'aggregation_unit' + this.id,
                checked: this.defaultAggregationUnit == 'Month',
                checkHandler: function (t, checked) {
                    if (!checked) return;
                    var errorMessage = this.validateAggregationUnit('Month');
                    if (errorMessage != '') {
                        Ext.MessageBox.alert('Aggregation unit selection error', errorMessage);
                        return;
                    }
                    this.aggregationUnitButton.setText('Month');
                    this.onHandle();
                }
            }, {
                xtype: 'menucheckitem',
                text: 'Quarter',
                scope: this,
                group: 'aggregation_unit' + this.id,
                checked: this.defaultAggregationUnit == 'Quarter',
                checkHandler: function (t, checked) {
                    if (!checked) return;
                    var errorMessage = this.validateAggregationUnit('Quarter');
                    if (errorMessage != '') {
                        Ext.MessageBox.alert('Aggregation unit selection error', errorMessage);
                        return;
                    }
                    this.aggregationUnitButton.setText('Quarter');
                    this.onHandle();
                }
            }, {
                xtype: 'menucheckitem',
                text: 'Year',
                scope: this,
                group: 'aggregation_unit' + this.id,
                checked: this.defaultAggregationUnit == 'Year',
                checkHandler: function (t, checked) {
                    if (!checked) return;
                    var errorMessage = this.validateAggregationUnit('Year');
                    if (errorMessage != '') {
                        Ext.MessageBox.alert('Aggregation unit selection error', errorMessage);
                        return;
                    }
                    this.aggregationUnitButton.setText('Year');
                    this.onHandle();
                }
            }
        ];
        this.aggregationUnitButton = new Ext.Button({
            id: 'aggregation_unit_' + this.id,
            scope: this,
            width: 100,
            text: 'Auto',
            fieldLabel: 'Aggregation Unit',
            tooltip: 'Aggregation Unit - the resolution of data aggregation',
            handler: function () {
                var errorMessage = this.validateAggregationUnit(this.aggregationUnitButton.getText());
                if (errorMessage != '') {
                    Ext.MessageBox.alert('Aggregation unit selection error', errorMessage);
                    return;
                }
            },

            menu: new Ext.menu.Menu({
                ignoreParentClicks: true,
                showSeparator: false,
                items: this.aggregationUnitItems

            })
        });
        this.cannedDateItems = [];
        this.cannedDateItems.push({
            xtype: 'menucheckitem',
            text: 'User Defined',
            scope: this,
            group: 'canned_date' + this.id,
            checked: this.defaultCannedDate == 'User Defined',
            handler: function (m) {
                this.cannedDateButton.setText('User Defined');
                this.startDateField.focus(true);
            }
        });
        this.cannedDateItems.push('-');
        this.cannedDateItems.push('<b class="menu-title">Duration Presets:</b><br/>');

        for (var i = 0; i < this.dateRanges.length; i++) {

            this.cannedDateItems.push({
                xtype: 'menucheckitem',
                text: this.dateRanges[i].text,
                scope: this,
                group: 'canned_date' + this.id,
                checked: this.defaultCannedDate == this.dateRanges[i].text,
                start: this.dateRanges[i].start,
                end: this.dateRanges[i].end,
                handler: function (b, e) {
                    this.endDateField.setValue(b.end);
                    this.startDateField.setValue(b.start);
                    this.cannedDateButton.setText(b.text);
                    this.onHandle();
                }
            });
        }

        if (this.showAggregationUnit) {
            this.cannedDateItems.push('-');
            this.cannedDateItems.push({
                text: 'Aggregation Unit',
                menu: this.aggregationUnitButton.menu
            });
        }
        this.cannedDateMenu = new Ext.menu.Menu({
            ignoreParentClicks: true,
            showSeparator: false,
            items: this.cannedDateItems,

            listeners: {
            }
        });

        this.cannedDateButton = new Ext.Button({
            id: 'canned_dates_' + this.id,
            scope: this,
            width: 100,
            text: this.defaultCannedDate,
            tooltip: 'Configure time frame',
            fieldLabel: 'Predefined Duration',
            iconCls: 'custom_date',
            menu: this.cannedDateMenu
        });


        this.refreshButton = new Ext.Button({
            id: 'refresh_button_' + this.id,
            rows: 2,

            iconCls: 'refresh',
            text: 'Refresh',
            tooltip: 'Refresh using selected time frame',
            scope: this,
            handler: function () {
                this.onHandle(true);
            }
        });

        this.dateSlider = new Ext.slider.MultiSlider({
            width: 300,
            values: [25, 75],
            minValue: 0,
            maxValue: 100
        });

        var items = this.items || [];
        items.push('Duration:');
        items.push({
            xtype: 'tbspacer',
            width: 2
        });
        items.push(this.cannedDateButton);
        items.push({
            xtype: 'tbspacer',
            width: 2
        });
        items.push(new Ext.form.Label({
            text: 'Start:'
        }));
        items.push({
            xtype: 'tbspacer',
            width: 2
        });
        items.push(this.startDateField);
        items.push({
            xtype: 'tbspacer',
            width: 2
        });
        items.push(new Ext.form.Label({
            text: 'End:'
        }));
        items.push({
            xtype: 'tbspacer',
            width: 2
        });
        items.push(this.endDateField);

        if (this.showRefresh) {
            if (this.alighRight) items.push('->');
            else items.push('-');
            items.push(this.refreshButton);
        }
        if (this.showSlider) {
            items.push('-');
            items.push(this.dateSlider);
        }
        Ext.apply(this, {
            enableOverflow: true,
            border: false,
            items: items
        });

        CCR.xdmod.ui.DurationToolbar.superclass.initComponent.apply(this, arguments);
    }
});
