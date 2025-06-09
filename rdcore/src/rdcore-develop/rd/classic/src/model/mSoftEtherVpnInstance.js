Ext.define('Rd.model.mSoftEtherVpnInstance', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'ip_address',        type: 'string'  },
        {name: 'admin_name',        type: 'string'  },
        {name: 'password',          type: 'string'  },
        {name: 'config_hash_value', type: 'string'  },
    ]
});
