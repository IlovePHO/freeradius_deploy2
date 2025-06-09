Ext.define('Rd.view.staConfigs.cmbEncodingSchemes', {
    extend: 'Ext.form.ComboBox',
    alias: 'widget.cmbEncodingSchemes',
    fieldLabel: i18n('sAuthentication_method'),
    labelSeparator: '',
    valueField: 'id',
    displayField: 'name',
    typeAhead: true,
    allowBlank: true,
    itemId: 'cmbEncodingSchemes',
    labelClsExtra: 'lblRd',
    //store: 'sEncodingSchemes',
    initComponent: function () {
        var me = this;

        var s = Ext.create('Ext.data.Store', {
            buffered: false,
            remoteSort: true,
            proxy: {
                type: 'ajax',
                format: 'json',
                batchActions: true,
                url: '/cake3/rd_cake/encoding-schemes/index.json',
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
