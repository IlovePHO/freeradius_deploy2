Ext.define('Rd.model.mSoftEtherUser', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'hub_id',                type: 'int'     },

        {name: 'user_name',          type: 'string'  },
        {name: 'real_name',          type: 'string'  },

        {name: 'auth_password',          type: 'string'  },
        {name: 'note',   type: 'string'  },
    ]
});
