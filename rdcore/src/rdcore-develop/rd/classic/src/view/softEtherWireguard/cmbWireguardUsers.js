Ext.define('Rd.view.softEtherWireguard.cmbWireguardUsers', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbWireguardUsers',
    fieldLabel		: i18n('sVPN_users'),
    labelSeparator	: '',
    valueField		: 'id',
    displayField	: 'user_name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmWireguardUsers',
    labelClsExtra: 'lblRd',
    extraParam      : false,
    initComponent: function() {
        var me = this;
        
         me.store = Ext.create('Ext.data.Store', {
            fields: ['id', 'user_name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/soft-ether-wireguard/get-user-list.json',
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message'
                }
            },
            autoLoad: false
        });
        if(me.extraParam){
            me.store.getProxy().setExtraParam('virtual_hub_id',me.extraParam);
        }
        me.callParent(arguments);
    }
});

