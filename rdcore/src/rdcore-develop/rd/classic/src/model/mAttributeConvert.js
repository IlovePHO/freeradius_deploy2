Ext.define('Rd.model.mAttributeConvert', {
    extend: 'Ext.data.Model',
    fields: [
		
         {name: 'id',           type: 'int'     },
         {name: 'src',         type: 'string'  },
         {name: 'dst',        type: 'string'  }, 
         {name: 'nas_type',type: 'string'  },
         //{name: 'created',    	type: 'date',      dateFormat: 'Y-m-d H:i:s'   },
         //{name: 'modified',    	type: 'date',      dateFormat: 'Y-m-d H:i:s'   },

         {name: 'update',       type: 'bool'},
         {name: 'delete',       type: 'bool'}
        ]
});
