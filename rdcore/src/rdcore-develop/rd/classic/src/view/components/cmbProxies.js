Ext.define('Rd.view.components.cmbProxies', {
    extend          : 'Ext.form.ComboBox',
    alias           : 'widget.cmbProxies',
    fieldLabel      : i18n('sProxy'),
    labelSeparator: '',
    queryMode: 'local',
    valueField: 'id',
    displayField: 'name',
    typeAhead: true,
    allowBlank: false,
    mode: 'local',
    labelClsExtra: 'lblRd',
    initComponent: function(){
        var me      = this;
        me.store    = Ext.create('Ext.data.Store', {
            fields: [
                {name: 'id',    type: 'string'},
                {name: 'name',  type: 'string'}
            ],
            proxy: {
                    type    : 'ajax',
                    format  : 'json',
                    batchActions: true, 
                    url     : '/cake3/rd_cake/proxies/index.json',
                    reader: {
                        type            : 'json',
                        rootProperty            : 'items',
                        messageProperty : 'message'
                    }
            },
            autoLoad: true
        });
        me.callParent(arguments);
    }
});
