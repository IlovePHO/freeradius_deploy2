Ext.define('Rd.model.mProxyDecisionCondition', {
    extend: 'Ext.data.Model',
    fields: [
         {name: 'id',           type: 'int'     },
         {name: 'proxy_id',      type: 'int'  },
         {name: 'proxy_name',  type: 'string'  },
        
         {name: 'ssid',         type: 'string'  },
         {name: 'user_name_regex',         type: 'string'  },
         {name: 'priority',  type: 'int'  },
        
         {name: 'created',    type: 'date',      dateFormat: 'Y-m-d H:i:s'   },
         {name: 'modified',    type: 'date',      dateFormat: 'Y-m-d H:i:s'   },

//         {name: 'update',       type: 'bool'},
//         {name: 'delete',       type: 'bool'}
        ]
});
