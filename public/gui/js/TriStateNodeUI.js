/*  
 * JavaScript Document
 * Viewer
 * @author Amin Ghadersohi
 * @date 2013-Jan-01
 * 
 * A checkbox class for ext js with 3 states: check, uncheck and greyed out
 */

Ext.tree.TriStateNodeUI = Ext.extend(Ext.tree.TreeNodeUI, {
    onCheckChange: function () {
        Ext.tree.TriStateNodeUI.superclass.onCheckChange.apply(this, arguments);
        var p = this.node;
        while ((p = p.parentNode) && p.getUI().updateParent && p.getUI().checkbox && !p.getUI().isUpdating) {
            p.getUI().updateParent();
        }
    },
    toggleCheck: function () {
        var checked = Ext.tree.TriStateNodeUI.superclass.toggleCheck.apply(this, arguments);
        this.updateChild(checked);
        return checked;
    },
    renderElements: function (n, a, targetNode, bulkRender) {
        Ext.tree.TriStateNodeUI.superclass.renderElements.apply(this, arguments);
        this.updateChild(this.node.attributes.checked);
    },
    updateParent: function () {
        var checked;
        this.node.eachChild(function (n) {
            if (checked === undefined) {
                checked = n.attributes.checked;
            } else if (checked !== n.attributes.checked) {
                checked = this.grayedValue;
                return false;
            }
        }, this);
        this.toggleCheck(checked);
    },
    updateChild: function (checked) {
        if (typeof checked == 'boolean') {
            this.isUpdating = true;
            if (this.node.childrenRendered) {
                this.node.eachChild(function (n) {
                    n.getUI().toggleCheck(checked);
                }, this);
            } else {
                if (this.node.attributes.children)
                    for (var i = 0; i < this.node.attributes.children.length; i++) {
                        this.node.attributes.children[i].checked = checked;
                    }
            }
            delete this.isUpdating;
        }
    }
});
/*
Ext.tree.AsynchTriStateNodeUI = Ext.extend(Ext.tree.TriStateNodeUI, {
	updateChild:function(checked){
		if(this.checkbox){
			if(checked === true){
				Ext.fly(this.ctNode).replaceClass('x-tree-branch-unchecked', 'x-tree-branch-checked');
			} else if(checked === false){
				Ext.fly(this.ctNode).replaceClass('x-tree-branch-checked', 'x-tree-branch-unchecked');
			} else {
				Ext.fly(this.ctNode).removeClass(['x-tree-branch-checked', 'x-tree-branch-unchecked']);
			}
		}
	},
	getChecked: function() {
		var checked = this.node.parentNode ? this.node.parentNode.ui.getChecked() : this.grayedValue;
		return typeof checked == 'boolean' ? checked : Ext.tree.TriStateNodeUI.superclass.getChecked.call(this);
	}
});*/