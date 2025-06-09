Ext.define('Rd.store.sIdpsAuthTypes', {
    extend: 'Ext.data.Store',
    model: 'Rd.model.mIdpsAuthType',
    proxy: {
            type    : 'ajax',
            format  : 'json',
            batchActions: true, 
            url     : '/cake3/rd_cake/idps/auth_types.json',
            reader: {
                type: 'json',
                rootProperty: 'items',
                messageProperty: 'message'
            }
    },
    autoLoad: true
});
