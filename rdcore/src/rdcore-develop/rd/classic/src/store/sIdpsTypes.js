Ext.define('Rd.store.sIdpsTypes', {
    extend: 'Ext.data.Store',
    model: 'Rd.model.mIdpsType',
    proxy: {
            type    : 'ajax',
            format  : 'json',
            batchActions: true, 
            url     : '/cake3/rd_cake/idps/types.json',
            reader: {
                type: 'json',
                rootProperty: 'items',
                messageProperty: 'message'
            }
    },
    autoLoad: true
});
