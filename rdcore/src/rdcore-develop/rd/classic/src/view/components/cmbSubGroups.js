Ext.define('Rd.view.components.cmbSubGroups', {
    extend          : 'Ext.form.ComboBox',
    alias           : 'widget.cmbSubGroups',
    fieldLabel      : i18n('sOrganization'),
    labelSeparator  : '',
    queryMode       : 'local',
    valueField      : 'id',
    displayField    : 'name',
    allowBlank      : false,
	realmId         : false,
    editable        : false,
    mode            : 'local',
    name            : 'sub_group_id',
    labelClsExtra   : 'lblRd',
    initComponent: function(){
        var me      = this;
        var s       = Ext.create('Ext.data.Store', {
            fields: ['id', 'name'],
            proxy: {
                type    : 'ajax',
                format  : 'json',
                url     : '/cake3/rd_cake/sub-groups/index.json',
                reader: {
                    type: 'json',
                    rootProperty: 'items',
                    messageProperty: 'message'
                }
            },
            autoLoad: true
        });

        if(me.allowBlank){
            s.getProxy().setExtraParam('append_none', 1);
        }
        if(me.realmId){
            s.getProxy().setExtraParam('realm_id', me.realmId);
		}
        me.store = s;
        me.callParent(arguments);
    }
});
