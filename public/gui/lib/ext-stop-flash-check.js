/**
 * Stop Ext JS from performing its check for Flash at load time.
 *
 * This must be loaded before Ext JS.
 *
 * Ext JS tests for the availability of Flash at load time by attempting
 * to insert a temporary Flash object into the page. Unfortunately,
 * this causes a plugin warning to appear in browsers that don't allow
 * plugins or Flash to run by default, which is an increasingly-common
 * scenario. In projects that do not use Flash, it is better to break this
 * code than to erroneously warn users that the project wants to use Flash.
 */

document.addEventListener("DOMContentLoaded", function() {
    // Temporarily wrap the function to append elements to the document
    // body with a function that instead returns null when a Flash
    // element is given.
    var origDocumentBodyAppendChild = document.body.appendChild;
    document.body.appendChild = function(child) {
        // If the given child is not a Flash element,
        // allow the original function to handle it.
        if (
            !Ext.isElement(child)
            || child.type !== "application/x-shockwave-flash"
        ) {
            return origDocumentBodyAppendChild.apply(this, arguments);
        }

        // If the given child is a Flash object, reset the document
        // body's appendChild function to the original function and
        // return null.
        //
        // This interceptor is removed after its first successful
        // intercept of a Flash object because ExtJS only attempts this
        // check once and wrapping a fundamental native code function
        // with a JavaScript function incurs performance penalties.
        document.body.appendChild = origDocumentBodyAppendChild;
        return null;
    };
}, false);
