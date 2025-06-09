Ext.define('Rd.view.softEtherVirtualHubs.pnlSoftEtherVirtualHub', {
    extend  : 'Ext.tab.Panel',
    alias   : 'widget.pnlSoftEtherVirtualHub',
    border  : false,
    plain   : true,
    cls     : 'subTab',
    record: null, //We will supply each instance with a reference to the selected record.
    requires    : [
        'Rd.view.softEtherVirtualHubs.pnlSoftEtherVirtualHubBasic',
        'Rd.view.softEtherVirtualHubs.gridSoftEtherUsers',
        'Rd.view.softEtherVirtualHubs.pnlSoftEtherSecureNatsEdit',
    ],
    initComponent: function () {
        var me = this;
        var vHub_id = me.record.get('id');
        
        me.items = [
            {   
                title   : i18n('sVirtual_Hub_settings'),
                itemId  : 'tabVHBasicInfo_'+vHub_id,
                xtype   : 'pnlSoftEtherVirtualHubBasic',
                record : me.record,
            },
            { 
                title   : i18n('sVPN_users'),
                itemId  : 'tabVHUsers_'+vHub_id,
                layout  : 'fit',
                xtype   : 'gridSoftEtherUsers',
                vHub_id   : vHub_id,
            },
            { 
                title   : i18n('sSecureNAT'),
                itemId  : 'tabVHNat_'+vHub_id,
                layout  : 'fit',
                xtype   : 'pnlSoftEtherSecureNatsEdit',
                vHub_id   : vHub_id,
            },

        ]; 
        this.callParent(arguments);
    },


});
