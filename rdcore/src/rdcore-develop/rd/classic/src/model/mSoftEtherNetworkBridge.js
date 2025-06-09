Ext.define('Rd.model.mSoftEtherNetworkBridge', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',            type: 'int'     },
        {name: 'bridge_name',   type: 'string'  },
        'if_name_list',
        {name: 'status',        type: 'bool', defaultValue: false },
        {name: 'ip_address',    type: 'string'  },
        {name: 'subnet_mask',   type: 'string'  },
    ]
});
