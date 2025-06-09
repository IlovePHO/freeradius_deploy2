Ext.define('Rd.model.mStaInfo', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'device_type',       type: 'string'  },
        {name: 'device_unique_id',  type: 'string'  },
        {name: 'short_unique_id',   type: 'string'  },
        {name: 'device_unique_id',  type: 'string'  },
        {name: 'device_token',      type: 'string'  },

        {name: 'permanent_user_id',       type: 'int'  },
        {name: 'permanent_user_username', type: 'string'  },
        
        {name: 'username',          type: 'string'  },
        {name: 'user_type',         type: 'string'  },

        {name: 'voucher_id',        type: 'int'     },
        {name: 'voucher_name',      type: 'string'  },
        
        "sta_configs",
        
        {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'created_in_words',  type: 'string'  },
        {name: 'modified_in_words', type: 'string'  }
    ]
});
