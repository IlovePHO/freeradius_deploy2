Ext.define('Rd.view.homeServerPools.pnlHomeServerAddEdit', {
    extend      : 'Ext.form.Panel',
    alias       : 'widget.pnlHomeServerAddEdit',
    autoScroll	: true,
    plain       : true,
	itemId		: 'pnlHomeServerAddEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    hsv_id    : null,
    grid_id    : null,
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
    ],
    config : {
        urlView         : '/cake3/rd_cake/home-servers/view.json'
    },
    listeners       : {
        show        : 'loadSettings', //Trigger a load of the settings
        afterrender : 'loadSettings',
    },
    loadSettings: function(panel){ 
	    var me = this;

        me.load({
            url         :me.getUrlView(),
            method      :'GET',
            params      :{home_server_id : me.hsv_id},
            success     : function(a,b,c){
                // console.log(b.result.data);
            }
        });
        
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
                        itemId      : 'home_server_pool_id',
                        xtype       : 'textfield',
                        name        : "home_server_pool_id",
                        hidden      : true
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
                        xtype       	: 'textfield',
                        fieldLabel  	: i18n('sSecret'),
                        name        	: 'secret',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        itemId          : 'type',
                        xtype       	: 'cmbHomeServerTypes',
                        fieldLabel  	: i18n('sType'),
                        value           : 'auth+acct',
                        name        	: 'type',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq',
                    },
                    {
                        xtype       	: 'cmbHomeServerProtocols',
                        fieldLabel  	: i18n('sProtocol'),
                        name        	: 'proto',
                        value           : 'udp',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        xtype       	: 'textfield',
                        fieldLabel  	: i18n('sIP_Address'),
                        name        	: 'ipaddr',
                        allowBlank  	: false,
                        blankText   	: i18n('sEnter_a_value'),
                        vtype			: 'IPAddress',
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        itemId          : 'portNo',
                        xtype       	: 'numberfield',
                        fieldLabel  	: i18n('sPort_No'),
                        name        	: 'port',
                        value           : 1812,
                        allowBlank  	: false,
                        blankText   	: i18n('sEnter_a_value'),
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        xtype       	: 'cmbHomeServerStatusChecks',
                        fieldLabel  	: i18n('sStatus_check'),
                        name        	: 'status_check',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        xtype       	: 'numberfield',
                        fieldLabel  	: i18n('sPriority'),
                        name        	: 'priority',
                        allowBlank  	: true,
                        value           : 5,
                        labelClsExtra	: 'lblRdReq'
                    },
                    
                    {
                        xtype       	: 'textfield',
                        fieldLabel  	: i18n('sDescription'),
                        name        	: 'description',
                        allowBlank  	: true,
                        labelClsExtra	: 'lblRdReq'
                    },
                    

                ],
            },
           
        ];       
        this.callParent(arguments);
    }
});
