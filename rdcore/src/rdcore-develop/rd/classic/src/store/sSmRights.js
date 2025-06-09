Ext.define('Rd.store.sSmRights', {
    extend: 'Ext.data.TreeStore',
    model: 'Rd.model.mSmRight',
    autoLoad: true,
    proxy: {
            type: 'ajax',
            format  : 'json',
            batchActions: true, 
            url   : '/cake3/rd_cake/acos-rights/index-sm.json',
            reader: {
                type: 'json',
                rootProperty: 'items',
                messageProperty: 'message'
            },
            api: {
                read    : '/cake3/rd_cake/acos-rights/index-sm.json',
                update  : '/cake3/rd_cake/acos-rights/edit-sm.json'
            }
    },
    root: {alias: i18n('sDefault_Site_Manager_Rights'),leaf: false, id:'0', iconCls: 'root', expanded: false},
    folderSort: true,
    clearOnLoad: true,
    listeners: {
        load: function( store, records, a,successful,b) {
            if(!successful){
                Ext.ux.Toaster.msg(
                        i18n('sError_encountered'),
                        store.getProxy().getReader().rawData.message.message,
                        Ext.ux.Constants.clsWarn,
                        Ext.ux.Constants.msgWarn
                );
            //console.log(store.getProxy().getReader().rawData.message.message);
            }  
        },
        scope: this
    }
});
