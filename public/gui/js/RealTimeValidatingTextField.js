Ext.ns('XDMoD');

/**
 * This component is an extension to the standard Ext.form.TextField that will
 * execute the components 'validateValue' || 'isValid' functions on 'keypress'
 * or when the user presses 'backspace' or 'delete'. It should also correctly
 * interpret when the user has made a selection in the TextField and removed
 * the selection.
 *
 * Normal validation of Ext.form.TextField only occurs on 'blur'.
 **/
XDMoD.RealTimeValidatingTextField = Ext.extend(Ext.form.TextField, {

    /**
     * required so that the component receives the keypress & specialkey events.
     **/
    enableKeyEvents: true,

    /**
     * tracks what the state of the component validity was the last time it was
     * checked. This is used in the '_handleValidation' function.
     **/
    previouslyValid: false,

    initComponent: function() {
        XDMoD.RealTimeValidatingTextField.superclass.initComponent.call(this, arguments);

        this.validationCallback = this.validationCallback !== undefined
            ? this.validationCallback
            : function() {};
    },

    listeners: {
        keypress: function(field, event) {
            var next = field.getValue() + String.fromCharCode(event.getKey());
            if (this._handleValidation(field, next)) {
                this.validationCallback({name: next, validate: true});
            }
        },

        specialkey: function(field, event) {
            var searchName;
            if (this._deletionOccuring(event)) {
                searchName = this._handleDeletion(field, event);
                this._handleValidation(field, searchName);
            }
            this.validationCallback({name: searchName, validate: true});
        }
    },

    /**
     * Attempts to detect if the user has pressed the 'backspace' or 'delete' keys.
     * If the user has pressed 'backspace' or 'delete' then true is returned else
     * false is.
     * @param {Ext.Event} event the event to be inspected to determine if a
     *                          deletion is occurring.
     *
     * @return {Boolean}
     **/
    _deletionOccuring: function(event) {
        var keys = [event.BACKSPACE, event.DELETE];
        return keys.indexOf(event.getKey()) >= 0;
    },

    /**
     * Attempts to calculate and return the value of the provided field
     * as it would appear if the detected 'deletion' had occurred.
     *
     * @param {Ext.Component} field the field that will be used to provide the
     *                              base value for modification
     * @param {Ext.Event}     event the event that will be used in determining
     *                              what, if any, type of deletion has occurred
     **/
    _handleDeletion: function(field, event) {
        var before,
            after,
            value = field.getValue(),
            key = event.getKey();

        var selection = this._getSelection();

        if (selection.value.length > 0) {
            // if we have a selection
            before = value.substring(
                0,
                selection.start
            );
            after = value.substring(
                selection.end + 1
            );
        } else if (key === event.BACKSPACE) {
            // if we do not have a selection
            // but they pressed the backspace key
            before = value.substring(
                0,
                selection.start - 1
            );
            after = value.substring(
                selection.start
            );
        } else if (key === event.DELETE) {
            // if we do not have a selection
            // but they pressed the delete key
            before = value.substring(
                0,
                selection.start
            );
            after = value.substring(
                selection.start + 1
            );
        }

        // if we ended up with a before and after
        // value then go ahead and return it
        if (before !== undefined &&
            after !== undefined) {
            return before + after;
        }

        // default to returning the fields value if:
        //   - the user did not have a selection and
        //     they did not press backspace || delete
        return value;
    },

    /**
     * Attempts to retrieve information about the currently selected element
     * including selected value, selection start index and selection end index.
     *
     * @return {Object} {
     *                    value: '<currently selected text>',
     *                    start: <selection start index>,
     *                    end:   <selection end index>
     *                  }
     **/
    _getSelection: function() {
        var selectedTextArea = document.activeElement;
        var selection = selectedTextArea.value.substring(
            selectedTextArea.selectionStart,
            selectedTextArea.selectionEnd
        );

        return {
            value: selection,
            start: selectedTextArea.selectionStart,
            end: selectedTextArea.selectionEnd
        };
    },

    /**
     * Attempts to ascertain the validity of the provided field. It does this
     * in one of two ways. If value is provided then it attempts to discover
     * whether or not the provided 'field' considers 'value' to be valid.
     * If 'value' is not provided then the function 'field's function 'isValid'
     * will be executed.
     * The results of either of these two execution paths will be compared
     * against the fields 'previouslyValid' value and, if different,
     * 'previouslyValid' will be updated and true will be returned. If the values
     * are not different then false will be returned. The reason for the use of
     * the 'previouslyValid' check is so that we only trigger a change when the
     * field is moving from one valid state to another. This is a concern as
     * there may be a number of calls to this function while the state of the
     * fields validity has not changed.
     *
     * @param {Ext.Component} field the field that will be used as the source of
     *                              'validity'.
     * @param {String}        value optional. if provided, will be checked for
     *                              validity with the provided field.
     *
     * @return {Boolean} true if the 'valid' result differs from
     *                   field.previouslyValid, else false
     **/
    _handleValidation: function(field, value) {
        var valid;
        if (value !== undefined){
            valid = field.validateValue(value);
        } else {
            valid = field.isValid();
        }

        if (valid !== field.previouslyValid) {
            field.previouslyValid = valid;
            return true;
        }
        return false;
    }
});
