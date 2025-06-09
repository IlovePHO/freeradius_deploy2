Ext.define('Rd.view.staConfigs.pnlStaConfig', {
    extend  : 'Ext.tab.Panel',
    alias   : 'widget.pnlStaConfig',
    border  : false,
    plain   : true,
    cls     : 'subTab',
    stsConf_id   : null,
    record  : null,
    requires    : [
        'Rd.view.staConfigs.pnlStaConfigBasic',
        'Rd.view.staConfigs.gridStaConfigRealms',
        'Rd.view.staConfigs.gridStaConfigSubGroups',
    ],
    initComponent: function(){
        var me      = this;
        var ap_id = me.record.get('user_id');
        
        me.items = [
        {   
            title   : i18n('sBasic_info'),
            itemId  : 'tabBasicInfo',
            xtype   : 'pnlStaConfigBasic',
            stsConf_id : me.stsConf_id,
        },
        { 
            title   : i18n('sRealm_Bulk'),
            itemId  : 'tabRealms',
            layout  : 'fit',
            xtype   : 'gridStaConfigRealms',
            ap_id   : ap_id,
            stsConf_id : me.stsConf_id,
        },
        { 
            title   : i18n('sOrganization'),
            itemId  : 'SubGroups',
            layout  : 'fit',
            xtype   : 'gridStaConfigSubGroups',
            stsConf_id : me.stsConf_id,
        },
    ]; 


        me.callParent(arguments);
    }
});
