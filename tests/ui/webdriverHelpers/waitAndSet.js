/**
 * Wait for the element identified by the provided selector to be visible and
 * then set it's value to the provided value. To more accurately simulate a user
 * setting a value the click argument defaults to true. Also, as a simple sanity
 * check, validate is also defaulted to true with a default validate function
 * that just checks that the value that the selector was set to is what is
 * retrieved via a getValue.
 *
 * @param {String} selector  - will be used to identify the element.
 * @param {*}      value     - the value to use while performing a setValue
 *                             on the now visible element.
 * @param {Number} [ms=5000] - the amount of time to wait for the selector
 *                             to become visible.
 * @param {Boolean} [click=true] - A value of true indicates that a click
 *                                 event will be executed before the setValue
 *                                 for the provided selector. A value of false
 *                                 indicates that the click event should be
 *                                 skipped.
 * @param {Boolean} [validate=true] - validate that the value the selector
 *                                    is set to matches the value that is
 *                                    retrieved after the setValue is
 *                                    performed.
 * @param {Function} [validateFunc=function(expected, actual){
 *                      expect(actual).to.be.equal(expected);
 *                    }
 *                   ] - The function that will be used to validate the value retrieved
 *                       from the selector if the validate argument is true.
 **/
// eslint-disable-next-line consistent-return
module.exports = function waitAndSet(selector, value, ms, click, validate) {
    var timeOut = ms || 5000;
    var thisClick = click !== undefined ? click : true;
    var thisValidate = validate !== undefined ? validate : true;

    if (thisClick === true && thisValidate === true) {
        this.waitForVisible(selector, timeOut);
        this.click(selector);
        this.setValue(selector, value);
        return this.getValue(selector);
    } else if (thisClick === true && thisValidate === false) {
        this.waitForVisible(selector, timeOut);
        this.click(selector);
        return this.setValue(selector, value);
    } else if (thisClick === false && thisValidate === true) {
        this.waitForVisible(selector, timeOut);
        this.setValue(selector, value);
        return this.getValue(selector);
    } else if (thisClick === false && thisValidate === false) {
        this.waitForVisible(selector, timeOut);
        return this.setValue(selector, value);
    }
};
