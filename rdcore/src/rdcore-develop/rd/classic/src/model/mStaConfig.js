Ext.define('Rd.model.mStaConfig', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'name',              type: 'string'  },
        {name: 'user_id',           type: 'int'     },
        {name: 'available_to_siblings',  type: 'bool'},
        {name: 'ssid',              type: 'string'  },
        
        {name: 'eap_method',              type: 'string'  },
        {name: 'home_domain',              type: 'string'  },
        {name: 'rcoi',              type: 'string'  },
        {name: 'friendly_name',              type: 'string'  },

        {name: 'encoding_scheme_id',           type: 'int'     },
        {name: 'expire',            type: 'date',  dateFormat: 'Y/m/d' },
        'realms','sub_groups',

        {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'created_in_words',  type: 'string'  },
        {name: 'modified_in_words', type: 'string'  }
    ]
});
