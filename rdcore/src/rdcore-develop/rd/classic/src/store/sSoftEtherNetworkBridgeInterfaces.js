Ext.define('Rd.store.sSoftEtherNetworkBridgeInterfaces', {
    extend      : 'Ext.data.Store',
    model       : 'Rd.model.mSoftEtherNetworkBridgeInterface',
    pageSize    : 100,
    remoteSort  : true,
    remoteFilter: true,
    proxy: {
            type    : 'ajax',
            format  : 'json',
            batchActions: true, 
            url     : '/cake3/rd_cake/soft-ether-network-bridges/index-interfaces.json',
            reader: {
                type            : 'json',
                rootProperty    : 'items',
                messageProperty : 'message',
                totalProperty   : 'totalCount' //Required for dynamic paging
            },
            simpleSortMode      : true //This will only sort on one column (sort) and a direction(dir) value ASC or DESC
    },
    autoLoad: false
});
