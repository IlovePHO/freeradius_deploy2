Ext.define('Rd.view.idps.cmbIdpType', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbIdpType',
    fieldLabel		: i18n('sType'),
    labelSeparator	: '',
    valueField		: 'type',
    displayField	: 'name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbIdpType',
    labelClsExtra: 'lblRd',
    store:'sIdpsTypes',
   /* store:[
        {"type":"google_workspace",  "name":"Google Workspace"},
    ],*/
    initComponent: function() {
        var me = this;
        me.callParent(arguments);
    }
});

