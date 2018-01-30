XDMoD.CaptchaField = Ext.extend(Ext.Panel, {

    frame: false,
    border: false,
    height: 150,
    width: 335,
    baseCls: 'x-plain',
    html: '<g-recaptcha id="contact-recaptcha" data-sitekey="' + CCR.xdmod.captcha_sitekey + '"></g-recaptcha>',
    afterRender: function () {
        var recaptchaTag = document.getElementById('contact-recaptcha');
        recaptchaTag.dataset.callback = this.signupFormSubmit;
        this.grecaptchaid = grecaptcha.render(recaptchaTag);
    },
    initComponent: function () {
        var self = this;
        self.grecaptchaid = 0;
        self.getResponseField = function () {
            var elementId = 'g-recaptcha-response';
            if (self.grecaptchaid > 0) {
                elementId += '-' + self.grecaptchaid;
            }
            return document.getElementById(elementId).value;
        };
        XDMoD.CaptchaField.superclass.initComponent.call(this);
    }
});
