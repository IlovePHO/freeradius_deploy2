Ext.define('Rd.model.mSubGroup', {
    extend: 'Ext.data.Model',
    fields: [
         {name: 'id',       type: 'int'     },
         {name: 'user_id',      type: 'int'  },

         {name: 'user_id',      type: 'int'  },
         {name: 'realm_id',      type: 'int'  },
         {name: 'idp_id',      type: 'int'  },

         {name: 'name',     type: 'string'  },
         {name: 'path',     type: 'string'  },
         {name: 'profile',     type: 'string'  },
         {name: 'profile_id',     type: 'int'  },
         {name: 'unique_id',     type: 'int'  },
        
         {name: 'description',     type: 'string'  },

         {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
         {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
         {name: 'created_in_words',  type: 'string'  },
         {name: 'modified_in_words', type: 'string'  }
        ]
});
