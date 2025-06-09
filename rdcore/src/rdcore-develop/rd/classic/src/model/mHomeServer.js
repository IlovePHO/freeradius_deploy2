Ext.define('Rd.model.mHomeServer', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',            type: 'int'     },
        {name: 'home_server_pool_id', type: 'int'},
        {name: 'name',          type: 'string'  },
        {name: 'secret',        type: 'string'  },
        {name: 'type',          type: 'string'  },
        {name: 'proto',         type: 'string'  },
        {name: 'ipaddr',        type: 'string'  },
        {name: 'port',          type: 'int'     },
        {name: 'status_check',  type: 'string'  },
        {name: 'priority',      type: 'int'     },
        {name: 'description',   type: 'string'  },
        {name: 'created',       type: 'date',   dateFormat: 'Y-m-d H:i:s'   },
        {name: 'modified',      type: 'date',   dateFormat: 'Y-m-d H:i:s'   },
    //    {name: 'update',        type: 'bool'    },
    //    {name: 'delete',        type: 'bool'    },
    ]
});
