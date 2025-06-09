Ext.define('Rd.model.mEncodingScheme', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id',                type: 'int'     },
        {name: 'name',              type: 'string'  },
        
        {name: 'suffix',            type: 'string'  },
        {name: 'expire',            type: 'date',       dateFormat: 'Y-m-d H:i:s'  },

        {name: 'created',           type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'modified',          type: 'date',       dateFormat: 'Y-m-d H:i:s'   },
        {name: 'created_in_words',  type: 'string'  },
        {name: 'modified_in_words', type: 'string'  }
    ]
});
