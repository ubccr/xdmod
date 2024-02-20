/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2013-Jun-27
 *
 * Extension of paging toolbar allows disabling of refresh button using extra param; showRefresh(true/false)
 *
 */
(function() {

var T = Ext.Toolbar;

CCR.xdmod.ui.CustomPagingToolbar = Ext.extend(Ext.PagingToolbar, {

    /**
     * Indicates if the paging components are enabled or not.
     *
     * @type {Boolean}
     */
    _pagingEnabled: true,

    initComponent : function(){
        this.pagingItems = [this.first = new T.Button({
            tooltip: this.firstText,
            overflowText: this.firstText,
            iconCls: 'x-tbar-page-first',
            disabled: true,
            handler: this.moveFirst,
            scope: this
        }), this.prev = new T.Button({
            tooltip: this.prevText,
            overflowText: this.prevText,
            iconCls: 'x-tbar-page-prev',
            disabled: true,
            handler: this.movePrevious,
            scope: this
        }), '-', this.beforePageText,
        this.inputItem = new Ext.form.NumberField({
            cls: 'x-tbar-page-number',
            allowDecimals: false,
            allowNegative: false,
            enableKeyEvents: true,
            selectOnFocus: true,
            submitValue: false,
            listeners: {
                scope: this,
                keydown: this.onPagingKeyDown,
                blur: this.onPagingBlur
            }
        }), this.afterTextItem = new T.TextItem({
            text: String.format(this.afterPageText, 1)
        }), '-', this.next = new T.Button({
            tooltip: this.nextText,
            overflowText: this.nextText,
            iconCls: 'x-tbar-page-next',
            disabled: true,
            handler: this.moveNext,
            scope: this
        }), this.last = new T.Button({
            tooltip: this.lastText,
            overflowText: this.lastText,
            iconCls: 'x-tbar-page-last',
            disabled: true,
            handler: this.moveLast,
            scope: this
        })];
		//Amin Ghadersohi - Allow disabling of refresh button
		this.refresh = new T.Button({
			tooltip: this.refreshText,
			overflowText: this.refreshText,
			iconCls: 'x-tbar-loading',
			handler: this.doRefresh,
			scope: this
		});
		if(this.showRefresh)
		{
			this.pagingItems.push('-');
			this.pagingItems.push(this.refresh);
		}
        var userItems = this.items || this.buttons || [];
        this.items = this.pagingItems.concat(userItems);
        
		if(this.preItems && this.preItems.length > 0)
		{
			this.preItems.push('-');
			this.items = this.preItems.concat(this.items);
		}
        delete this.buttons;
        if(this.displayInfo){
            this.items.push('->');
            this.items.push(this.displayItem = new T.TextItem({}));
        }
        Ext.PagingToolbar.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event change
             * Fires after the active page has been changed.
             * @param {Ext.PagingToolbar} this
             * @param {Object} pageData An object that has these properties:<ul>
             * <li><code>total</code> : Number <div class="sub-desc">The total number of records in the dataset as
             * returned by the server</div></li>
             * <li><code>activePage</code> : Number <div class="sub-desc">The current page number</div></li>
             * <li><code>pages</code> : Number <div class="sub-desc">The total number of pages (calculated from
             * the total number of records in the dataset as returned by the server and the current {@link #pageSize})</div></li>
             * </ul>
             */
            'change',
            /**
             * @event beforechange
             * Fires just before the active page is changed.
             * Return false to prevent the active page from being changed.
             * @param {Ext.PagingToolbar} this
             * @param {Object} params An object hash of the parameters which the PagingToolbar will send when
             * loading the required page. This will contain:<ul>
             * <li><code>start</code> : Number <div class="sub-desc">The starting row number for the next page of records to
             * be retrieved from the server</div></li>
             * <li><code>limit</code> : Number <div class="sub-desc">The number of records to be retrieved from the server</div></li>
             * </ul>
             * <p>(note: the names of the <b>start</b> and <b>limit</b> properties are determined
             * by the store's {@link Ext.data.Store#paramNames paramNames} property.)</p>
             * <p>Parameters may be added as required in the event handler.</p>
             */
            'beforechange'
        );
        this.on('afterlayout', this.onFirstLayout, this, {single: true});
        this.cursor = 0;
        this.bindStore(this.store, true);
    },

    /**
     * Disable a given component silently.
     *
     * @param  {Ext.Component} component The component to disable.
     */
    _disableComponentSilently: function (component) {
        component.disable(true);
    },

    /**
     * Set whether the paging portion of the bar is active or not.
     *
     * @param {boolean} pagingEnabled Controls whether paging is active or not.
     */
    setPagingEnabled: function(pagingEnabled) {
        if (pagingEnabled === this._pagingEnabled) {
            return;
        }

        Ext.each(this.pagingItems, function (pagingItem) {
            if (!(pagingItem instanceof Ext.Component)) {
                return;
            }

            if (pagingEnabled) {
                pagingItem.removeListener('enable', this._disableComponentSilently);
            } else {
                pagingItem.addListener('enable', this._disableComponentSilently);
            }
            pagingItem.setDisabled(!pagingEnabled);
        }, this);

        this._pagingEnabled = pagingEnabled;
    }

  
});

})();
Ext.reg('custompaging', CCR.xdmod.ui.CustomPagingToolbar);

