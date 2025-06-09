Ext.define('Rd.store.sIdps', {
    extend      : 'Ext.data.Store',
    model       : 'Rd.model.mIdps',
    pageSize    : 100,
    remoteSort  : true,
    remoteFilter: true,
    proxy: {
            type    : 'ajax',
            format  : 'json',
            batchActions: true, 
            url     : '/cake3/rd_cake/idps/index.json',
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
