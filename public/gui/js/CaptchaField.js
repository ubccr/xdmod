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
        if (typeof grecaptcha === 'undefined') {
            CCR.xdmod.ui.userManagementMessage(
                'Error Loading reCAPTCHA. Contact an administrator directly.',
                false
            );
            return;
        }
        this.grecaptchaid = grecaptcha.render(recaptchaTag);
        if (this.grecaptchaid > 0) {
            this.elementId = 'g-recaptcha-response-' + this.grecaptchaid;
        } else {
            this.elementId = 'g-recaptcha-response';
        }
    },
    getResponseField: function () {
        if (this.elementId) {
            return document.getElementById(this.elementId).value;
        }
        return '';
    },
    initComponent: function () {
        XDMoD.CaptchaField.superclass.initComponent.call(this);
    }
});
