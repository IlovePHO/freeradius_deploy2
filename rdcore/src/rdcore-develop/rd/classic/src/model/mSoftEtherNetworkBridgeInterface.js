Ext.define('Rd.model.mSoftEtherNetworkBridgeInterface', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',            type: 'int'     },
        {name: 'bridge_id',     type: 'int'     },
        {name: 'if_name',       type: 'string'  },
        {name: 'tap_mode',      type: 'bool', defaultValue: false,  },
    ]
});
