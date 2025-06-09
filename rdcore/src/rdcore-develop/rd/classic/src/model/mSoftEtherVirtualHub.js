Ext.define('Rd.model.mSoftEtherVirtualHub', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'hub_name',          type: 'string'  },
        {name: 'password',          type: 'string'  },
        {name: 'default_gateway',   type: 'string'  },
        {name: 'default_subnet',    type: 'string'  },
        {name: 'online',            type: 'bool', defaultValue: false,  },
    ]
});
