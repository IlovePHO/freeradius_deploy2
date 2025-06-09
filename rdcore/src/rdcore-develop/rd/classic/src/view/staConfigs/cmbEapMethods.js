Ext.define('Rd.view.staConfigs.cmbEapMethods', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbEapMethods',
    fieldLabel		: i18n('sAuthentication_method'),
    labelSeparator	: '',
    valueField		: 'value',
    displayField	: 'name',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbEapMethods',
    labelClsExtra: 'lblRd',
    initComponent: function() {
        var me= this;
        var s = Ext.create('Ext.data.Store', {
            buffered: false,
            remoteSort: true,
            proxy: {
                type    : 'ajax',
                format  : 'json',
                batchActions: true, 
                url     : '/cake3/rd_cake/sta-configs/get-eap-method-list.json',
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message',
                },
            },
            autoLoad: true
        });
        me.store = s;
        this.callParent(arguments);
    }
});

