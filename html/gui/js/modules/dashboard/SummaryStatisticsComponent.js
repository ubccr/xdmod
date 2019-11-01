/**
 * XDMoD.Module.Dashboard.SummaryStatisticsComponent
 *
 *
 */

Ext.namespace('XDMoD.Module.Dashboard');

XDMoD.Module.Dashboard.SummaryStatisticsComponent = Ext.extend(Ext.ux.Portlet, {

    layout: 'fit',
    autoScroll: true,
    baseTitle: 'Summary Statistics',
    tbar: {
        border: false,
        cls: 'xd-toolbar'
    },

    /**
     * The styling that will be applied to the summary statistic toolbar item
     * headers.
     */
    keyStyle: {
        marginLeft: '2px',
        marginRight: '2px',
        fontSize: '11px',
        textAlign: 'center'
    },

    /**
     * The styling that will be applied to the summary statistic toolbar item
     * values.
     */
    valueStyle: {
        marginLeft: '2px',
        marginRight: '2px',
        textAlign: 'center',
        fontFamily: 'arial,"Times New Roman",Times,serif',
        fontSize: '11px',
        letterSpacing: '0px'
    },

    /**
     *
     */
    initComponent: function () {
        var self = this;

        var aspectRatio = 0.8;
        this.height = this.width * aspectRatio;
        var title = this.baseTitle;

        var dateRanges = CCR.xdmod.ui.DurationToolbar.getDateRanges();
        for (var i = 0; i < dateRanges.length; i++) {
            var dateRange = dateRanges[i];
            if (dateRange.text === this.config.timeframe) {
                this.config.start_date = this.formatDate(dateRange.start);
                this.config.end_date = this.formatDate(dateRange.end);
                title = this.baseTitle + ' - ' + this.config.start_date + ' to ' + this.config.end_date;
            }
        }

        this.setTitle(title);

        this.summaryStatisticsStore = new CCR.xdmod.CustomJsonStore({

            root: 'data',
            totalProperty: 'totalCount',
            autoDestroy: true,
            autoLoad: false,
            successProperty: 'success',
            messageProperty: 'message',

            fields: [
                'Jobs_job_count',
                'Jobs_active_person_count',
                'Jobs_active_pi_count',
                'Jobs_total_waitduration_hours',
                'Jobs_avg_waitduration_hours',
                'Jobs_total_cpu_hours',
                'Jobs_avg_cpu_hours',
                'Jobs_total_su',
                'Jobs_avg_su',
                'Jobs_min_processors',
                'Jobs_max_processors',
                'Jobs_avg_processors',
                'Jobs_total_wallduration_hours',
                'Jobs_avg_wallduration_hours',
                'Jobs_gateway_job_count',
                'Jobs_active_allocation_count',
                'Jobs_active_institution_count',
                'Jobs_statistics_formats'
            ],

            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: XDMoD.REST.url + '/summary/statistics',
                listeners: {

                    /**
                     *
                     * @param proxy   {Ext.data.DataProxy}
                     * @param request {Ext.data.Request}
                     */
                    load: function (proxy, request) {
                        var formats = request.reader.jsonData.formats;
                        var data = request.reader.jsonData.data;

                        // only populate the statistics if we have all the data
                        // we require.
                        if (formats && data.length) {
                            self.populateSummaryStatistics(formats, data[0]);
                        }
                    } // load: function (proxy, request) {
                } // listeners: {
            }) // proxy: new Ext.data.HttpProxy({
        }); // this.summaryStatisticsStore

        XDMoD.Module.Dashboard.SummaryStatisticsComponent.superclass.initComponent.apply(this, arguments);
    }, // initComponent

    listeners: {
        /**
         * This event fires after the component has been rendered. This will only
         * occur once per page refresh.
         */
        afterrender: function () {
            this.summaryStatisticsStore.load({
                params: {
                    start_date: this.config.start_date,
                    end_date: this.config.end_date
                }
            });
        }
    }, // listeners {

    /**
     * Populates this components top toolbar w/ the series of summary statistics
     * as defined in `data`, formatted via the entries in `formats`.
     *
     * @param formats {object[]}
     * @param data    {object}
     */
    populateSummaryStatistics: function (formats, data) {
        // Clear the top toolbar before re-populating it.
        this.getTopToolbar().removeAll();

        Ext.each(formats, function (itemGroup) {
            var itemTitles = [];
            var items = [];

            Ext.each(itemGroup.items, function (item) {
                var itemData = data[item.fieldName];
                var itemNumber;

                if (itemData) {
                    if (item.numberType === 'int') {
                        itemNumber = parseInt(itemData, 10);
                    } else if (item.numberType === 'float') {
                        itemNumber = parseFloat(itemData);
                    }

                    itemTitles.push({
                        xtype: 'tbtext',
                        text: item.title + ':',
                        style: this.keyStyle
                    });

                    items.push({
                        xtype: 'tbtext',
                        text: itemNumber.numberFormat(item.numberFormat),
                        style: this.valueStyle
                    });
                } // if (itemData)
            }, this); // Ext.each(itemGroup.items, ...

            if (items.length > 0) {
                this.getTopToolbar().add({
                    xtype: 'buttongroup',
                    columns: items.length,
                    title: itemGroup.title,
                    items: itemTitles.concat(items)
                });
            }
        }, this);

        // make sure that we force the toolbar to re-lay its self out.
        this.getTopToolbar().doLayout();
    }, // populateSummaryStatistics

    /**
     * Returns a consistently formatted string from the provided `date`.
     * Ex. `2019-01-01`
     *
     * @param date {Date} the date to use when building the formatted string.
     * @returns {string} a `YYYY-MM-DD` formatted string based on the `date`
     * parameter.
     */
    formatDate: function (date) {
        return date.getFullYear() + '-' + ('' + (date.getMonth() + 1)).padStart(2, '0') + '-' + ('' + date.getDate()).padStart(2, '0');
    } // formatDate: function(date) {
}); // XDMoD.Module.Dashboard.CenterHealthComponent = Ext.extend(Ext.ux.Component, {

/**
 * The Ext.reg call is used to register an xtype for this class so it
 * can be dynamically instantiated
 */
Ext.reg('xdmod-dash-summarystat-cmp', XDMoD.Module.Dashboard.SummaryStatisticsComponent);
