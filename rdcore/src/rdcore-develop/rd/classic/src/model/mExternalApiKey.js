Ext.define('Rd.model.mExternalApiKey', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'name',              type: 'string'  },
        
        {name: 'user_id',           type: 'int'     },

        {name: 'realm_id',      type: 'int'  },
        {name: 'realm_name',     type: 'string'  },
        
        {name: 'profile_id',      type: 'int'  },
        {name: 'profile_name',     type: 'string'  },

        {name: 'api_key',     type: 'string'  },

        {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'created_in_words',  type: 'string'  },
        {name: 'modified_in_words', type: 'string'  }
    ]
});
