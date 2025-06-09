Ext.define('Rd.model.mSoftEtherLocalBridge', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'hub_name',        type: 'string'  },
        {name: 'device_name',        type: 'string'  },
        {name: 'tap_mode',          type: 'bool', defaultValue: false,  },
    ]
});
