Ext.define('Rd.model.mProxie', {
    extend: 'Ext.data.Model',
    fields: [
         {name: 'id',           type: 'int'     },
         {name: 'user_id',      type: 'int'  },
         {name: 'home_server_pool_id',    type: 'int'  }, 
         {name: 'home_server_pool_name',  type: 'string'  },
         {name: 'available_to_siblings',  type: 'bool', defaultValue: false,  }, 
        
         {name: 'name',         type: 'string'  },
         {name: 'description',  type: 'string'  },
         //{name: 'created',    type: 'date',      dateFormat: 'Y-m-d H:i:s'   },
         //{name: 'modified',    type: 'date',      dateFormat: 'Y-m-d H:i:s'   },

         {name: 'update',       type: 'bool'},
         {name: 'delete',       type: 'bool'}
        ]
});
