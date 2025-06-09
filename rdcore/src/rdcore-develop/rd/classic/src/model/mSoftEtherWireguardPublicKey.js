Ext.define('Rd.model.mSoftEtherWireguardPublicKey', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',              type: 'int'     },
        {name: 'public_key',     type: 'string'  },
        {name: 'hub_name',        type: 'string'  },
        {name: 'user_name',     type: 'string'  },
    ]
});
