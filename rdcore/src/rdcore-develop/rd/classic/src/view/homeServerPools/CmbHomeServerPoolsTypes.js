Ext.define('Rd.view.homeServerPools.cmbHomeServerPoolsTypes', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbHomeServerPoolsTypes',
    fieldLabel		: i18n('sType'),
    labelSeparator	: '',
    valueField		: 'type',
    displayField	: 'type',
    typeAhead       : true,
    allowBlank      : true,
    itemId			: 'cmbHomeServerPoolsTypes',
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
                url     : '/cake3/rd_cake/home-server-pools/types.json',
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

