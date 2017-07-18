/**
 * The default validation function that is to be used if one is not provided by
 * the user.
 *
 * @param {*} expected - the right hand side of the simple equality statement.
 *                       ie. the value that the selector was set to.
 * @param {*} actual   - the left hand side of the simple equality statement.
 *                       ie. the value that was retrieved from the selector.
 **/
var DEFAULT_VALIDATE_FUNCTION = function(expected, actual) {
  expect(actual).to.equal(expected);
};

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
module.exports = function waitAndSet(selector, value, ms, click, validate, validateFunc) {
  ms = ms || 5000;
  click = click !== undefined ? click : true;
  validate = validate !== undefined ? validate : true;
  validateFunc = typeof validateFunc === 'function' ? validateFunc : DEFAULT_VALIDATE_FUNCTION;

  if (click === true && validate === true) {
        this.waitForVisible(selector, ms);
        this.click(selector);
        this.setValue(selector, value);
        return this.getValue(selector)
  } else if (click === true && validate === false) {
        this.waitForVisible(selector, ms);
        this.click(selector);
        return this.setValue(selector, value);
  } else if (click === false && validate === true) {
        this.waitForVisible(selector, ms);
        this.setValue(selector, value);
        return this.getValue(selector);
  } else if (click === false && validate === false) {
        this.waitForVisible(selector, ms);
        return this.setValue(selector, value);
  }
};
