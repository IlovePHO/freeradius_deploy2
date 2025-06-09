Ext.define('Rd.view.softEtherNetworkBridges.cmbEtherNetworkBridgeInterfaces', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbEtherNetworkBridgeInterfaces',
    fieldLabel		: i18n('sInterface'),
    labelSeparator	: '',
    valueField		: 'id',
    displayField	: 'if_name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbEtherNetworkBridgeInterfaces',
    labelClsExtra: 'lblRd',
    initComponent: function() {
        var me = this;
        
         me.store = Ext.create('Ext.data.Store', {
            fields: ['id', 'if_name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/soft-ether-network-bridges/get-interface-list.json',
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

