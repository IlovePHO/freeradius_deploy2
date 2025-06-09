Ext.define('Rd.model.mIdps', {
    extend: 'Ext.data.Model',
    fields: [
         {name: 'id',       type: 'int'     },
         {name: 'user_id',      type: 'int'  },
        // {name: 'owner',    type: 'string'  },

         {name: 'name',     type: 'string'  },
         {name: 'type',     type: 'string'  },
         {name: 'auth_type',     type: 'string'  },
         {name: 'available_to_siblings',  type: 'bool', defaultValue: false,  }, 
        
         {name: 'realm_id',      type: 'int'  },
         {name: 'realm_name',     type: 'string'  },
         {name: 'domain',     type: 'string'  },

         {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
         {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
         {name: 'created_in_words',  type: 'string'  },
         {name: 'modified_in_words', type: 'string'  }
        ]
});
