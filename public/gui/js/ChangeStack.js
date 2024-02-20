Ext.ns('XDMoD');

XDMoD.ChangeStack = Ext.extend(Ext.util.Observable, {

    /**
     * @class XDMoD.ChangeStack
     * Provides an undo/redo stack with basic bookmarking.
     *
     * @event update
     * Fires whenever the state of the stack changes.
     * @param this {XDMoD.ChangeStack} The change stack object
     * @param record {Object} The record that was updated.
     * @param operation {String} The operation that was run.
     */

    /**
     * @function constructor
     *
     * @param {Mixed} config 
     *      if a config.baseParams object is provided then it is pushed onto the stack.
     *      all other config properties are passed to the parent Ext.util.Observable class
     *      constructor.
     */
    constructor: function (config) {

        if(config.baseParams) {
            this._stack = [Ext.apply({}, config.baseParams)];
            delete config.baseParams;
        } else {
            this._stack = [];
        }
        this._stackPointer = this._stack.length - 1;

        this._markedVersion = null;
        this._savedData = null;
        this._pending = null;
        this._autocommit = true;

        this.addEvents('update');

        Ext.apply(this, config);
        
        XDMoD.ChangeStack.superclass.constructor.call(this, config);
    },

    /**
     * @function disableAutocommit
     * Prevents calls to add() from pushing data onto the stack
     */
    disableAutocommit: function() {
        this._autocommit = false;
    },

    /**
     * @function commit
     * Commit any data that was add()ed when auto commit was disabled.
     * If no pending changes were added then do nothing
     * If there were pending changes then fire the update event.
     */
    commit: function() {
        if(this._pending === null) {
            return;
        }
        this._addToStack(this._pending);
        this.fireEvent('update', this, this._pending, 'commit');
    },

    /**
     * @function enableAutocommit
     * Re-enable autocommit so that add() pushes onto the stack
     * Any data that was not explictly committed with a call to commit()
     * will be discarded
     */
    enableAutocommit: function() {
        this._autocommit = true;
        this._pending = null;
    },


    /**
     * @function add
     * Add data to the end of the stack and fire the update event.
     * @param data {Object} The data that should be added to the end of the stack
     */
    add: function (data) {
        if(this._autocommit === false) {
            this._pending = Ext.apply({}, data);
        } else {
            this._addToStack(data);
        }
        this.fireEvent('update', this, data, 'add');
    },

    /**
     * @function undo
     * move the stack pointer one entry to the left and call the update event.
     * @throws Error if the stack pointer cannot be moved.
     */
    undo: function () {
        if(!this.canUndo()) {
            throw new Error('unable to undo');
        }
        this._stackPointer -= 1;
        var data = this._stack[this._stackPointer];

        this.fireEvent('update', this, data, 'undo');
    },

    /**
     * @function redo
     * move the stack pointer one entry to the right and call the update event.
     * @throws Error if the stack pointer cannot be moved.
     */
    redo: function() {
        if(!this.canRedo()) {
            throw new Error('unable to redo');
        }
        this._stackPointer += 1;
        var data = this._stack[this._stackPointer];

        this.fireEvent('update', this, data, 'redo');
    },

    /**
     * @function isMarked
     * @return boolean whether the current version has been marked by a previous call
     * to mark().
     */
    isMarked: function() {
        return this._markedVersion == this._stackPointer;
    },

    /**
     * @function canRevert
     * @return boolean whether there is a version that has been marked by a previous call
     * to mark() that is not the current version.
     */
    canRevert: function() {
        return (this.isMarked() === false) && (this._savedData !== null);
    },

    /**
     * @function mark
     * mark the current version on the stack. The marked version can 
     * be pushed onto the stack by a call to reverttomarked. Fires the update event.
     * @throws Error if the stack is empty
     */
    mark: function() {
        if(this._stack.length == 0) {
            throw new Error('no data to mark');
        }
        this._markedVersion = this._stackPointer;
        this._savedData = this._stack[this._stackPointer];

        this.fireEvent('update', this, this._savedData, 'mark');
    },

    /**
     * @function revertToMarked
     * add the marked version to the end of the stack. A version is marked by a previous call to mark()
     * @throws Error if there is no marked version.
     */
    revertToMarked: function() {
        if(this._savedData === null) {
            throw new Error('no version is marked');
        }

        this._addToStack(this._savedData);
        this._markedVersion = this._stackPointer;
        var data = this._stack[this._stackPointer];

        this.fireEvent('update', this, data, 'reverttomarked');
    },

    /**
     * @function canUndo
     * @returns boolean whether there exists at least one version to the left of the current stack pointer
     */
    canUndo: function() {
        return (this._stack.length > 0) && this._stackPointer > 0;
    },

    /**
     * @function canRedo
     * @returns boolean whether there exists at least one version to the right of the current stack pointer
     */
    canRedo: function() {
        return (this._stack.length > 0) && this._stackPointer < (this._stack.length - 1);
    },

    /**
     * @function empty
     * @returns true if the stack is empty
     */
    empty: function() {
        return (this._stack.length == 0);
    },

    _addToStack: function(data) {
        if(!data) {
            throw new Error('must specify data');
        }
        if(this.canRedo()) {
            this._stack = this._stack.slice(0, this._stackPointer+1);
            if(this._markedVersion > this._stackPointer) {
                this._markedVersion = null;
            }
        }
        this._stack.push(Ext.apply({}, data));
        this._stackPointer = this._stack.length - 1;
    }

});
