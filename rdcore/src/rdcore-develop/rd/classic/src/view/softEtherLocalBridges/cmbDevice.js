Ext.define('Rd.view.softEtherLocalBridges.cmbDevice', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbDevice',
    fieldLabel		: i18n('sDevice'),
    labelSeparator	: '',
    valueField		: 'id',
    displayField	: 'if_name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbDevice',
    labelClsExtra: 'lblRd',
    initComponent: function() {
        var me = this;
        
         me.store = Ext.create('Ext.data.Store', {
            fields: ['id', 'if_name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/soft-ether-local-bridges/get-device-list.json',
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message'
                }
            },
            autoLoad: true
        });
        me.callParent(arguments);
    }
});

