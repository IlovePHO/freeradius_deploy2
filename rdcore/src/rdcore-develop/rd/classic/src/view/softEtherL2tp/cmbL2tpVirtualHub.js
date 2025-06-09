Ext.define('Rd.view.softEtherLocalBridges.cmbL2tpVirtualHub', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbL2tpVirtualHub',
    fieldLabel		: i18n('sVirtual_Hub'),
    labelSeparator	: '',
    valueField		: 'id',
    displayField	: 'hub_name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbL2tpVirtualHub',
    labelClsExtra: 'lblRd',
    initComponent: function() {
        var me = this;
        
         me.store = Ext.create('Ext.data.Store', {
            fields: ['id', 'hub_name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/soft-ether-l2tp/get-virtual-hub-list.json',
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

