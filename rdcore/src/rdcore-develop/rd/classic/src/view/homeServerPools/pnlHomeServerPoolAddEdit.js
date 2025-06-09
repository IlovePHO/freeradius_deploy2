Ext.define('Rd.view.homeServerPools.pnlHomeServerPoolAddEdit', {
    extend      : 'Ext.form.Panel',
    alias       : 'widget.pnlHomeServerPoolAddEdit',
    autoScroll	: true,
    plain       : true,
	itemId		: 'pnlHomeServerPoolAddEditAddEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    hsp_id      : null,
    hsp_name    : null,
    record      : null, //We will supply each instance with a reference to the selected record.
    defaults    : {
            border: false
    },
    fieldDefaults: {
        msgTarget       : 'under',
        labelAlign      : 'left',
        labelSeparator  : '',
        labelWidth      : Rd.config.labelWidth,
        margin          : Rd.config.fieldMargin,
        labelClsExtra   : 'lblRdReq'
    },
    requires: [
        'Ext.form.field.Text',
        'Rd.view.homeServerPools.vcHomeServerPoolGeneric',
    ],
    controller  : 'vcHomeServerPoolGeneric',
    listeners       : {
        show        : 'loadSettings', //Trigger a load of the settings
        afterrender : 'loadSettings',
    },
    initComponent: function(){
    
        var me 	           = this;
        
        me.buttons = [
            {
                itemId  : 'save',
                text    : 'SAVE',
                scale   : 'large',
                formBind: true,
                glyph   : Rd.config.icnYes,
                margin  : Rd.config.buttonMargin,
                ui      : 'button-teal'
            }
        ]; 
        me.items = [
            {
                xtype       : 'panel',
                //bodyStyle   : 'background: #f0f0f5',
                bodyPadding : 10,
                items       : [
                    {
                        itemId      : 'id',
                        xtype       : 'textfield',
                        name        : "id",
                        hidden      : true
                    },
                     {
                        xtype       : 'fieldcontainer',
                        itemId      : 'fcPickOwner',
                        //hidden      : true,  
                        layout      : {
                            type    : 'hbox',
                            align   : 'begin',
                            pack    : 'start'
                        },
                        items:[
                            {
                                itemId      : 'owner',
                                xtype       : 'displayfield',
                                fieldLabel  : i18n('sOwner'),
                                name        : 'username',
                                itemId      : 'displUser',
                                margin      : 0,
                                padding     : 0,
                                width       : 360,
                            },
                            {
                                xtype       : 'button',
                                text        : 'Pick Owner',
                                margin      : 5,
                                padding     : 5,
                                ui          : 'button-green',
                                itemId      : 'btnPickOwner',
                                width       : 100,
                                listeners       : {
                                    click : 'onBtnPickOwnerClick'
                                }          
                            },
                            {
                                xtype       : 'textfield',
                                name        : "user_id",
                                itemId      : 'hiddenUser',
                                hidden      : true
                            }
                        ]
                    },
                    {
                        xtype       : 'textfield',
                        fieldLabel  : i18n('sName'),
                        name        : "name",
                        allowBlank  : false,
                        blankText   	: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype       	: 'cmbHomeServerPoolsTypes',
                        fieldLabel  	: i18n('sType'),
                        name        	: 'type',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq',
                    },
                    {
                        xtype       	: 'textfield',
                        fieldLabel  	: i18n('sDescription'),
                        name        	: 'description',
                        allowBlank  	: true,
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        xtype       : 'checkbox',      
                        boxLabel    : i18n('sMake_available_to_sub_providers'),
                        // fieldLabel    : i18n('sMake_available_to_sub_providers'),
                        name        : 'available_to_siblings',
                        inputValue  : 'available_to_siblings',
                        itemId      : 'a_to_s',
                        checked     : false,
                        cls         : 'lblRd'
                    },

                ],
            },
           
        ];       
        this.callParent(arguments);
    }
});
