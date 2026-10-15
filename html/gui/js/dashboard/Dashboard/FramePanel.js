
Ext.namespace('XDMoD', 'XDMoD.Dashboard');

XDMoD.Dashboard.FramePanel = Ext.extend(Ext.BoxComponent, {
    constructor: function (config) {
        config.autoEl = {
            tag: 'iframe',
            src: config.url
        };

        delete config.url;

        XDMoD.Dashboard.FramePanel.superclass.constructor.call(this, config);
    }
});

