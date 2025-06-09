Ext.define('Rd.view.idps.cmbIdpAuthType', {
    extend			: 'Ext.form.ComboBox',
    alias 			: 'widget.cmbIdpAuthType',
    fieldLabel		: i18n('sAuth_type'),
    labelSeparator	: '',
    queryMode       : 'local',
    valueField		: 'value',
    displayField	: 'name',
    //typeAhead       : true,
    allowBlank      : true,
    editable        : false,
    itemId			: 'cmbIdpAuthType',
    mode            : 'local',
    name            : 'auth_type',
    type            : false,
    labelClsExtra: 'lblRd',
    store:'sIdpsAuthTypes',
    /* store:[
        {"value":"oauth",  "name":"OAuth"},
    ], */
    initComponent: function() {
        var me = this;
        var s       = Ext.create('Ext.data.Store', {
            fields: ['value', 'name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/idps/auth_types.json',
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message'
                },
            },
            autoLoad: true
        });

        if (me.type) {
            s.getProxy().setExtraParam('type', me.type);
        }

        me.store = s;
        me.callParent(arguments);
    }
});

