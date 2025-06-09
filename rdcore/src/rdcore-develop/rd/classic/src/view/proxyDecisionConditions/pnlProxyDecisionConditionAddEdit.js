Ext.define('Rd.view.proxyDecisionConditions.pnlProxyDecisionConditionAddEdit', {
    extend      : 'Ext.form.Panel',
    alias       : 'widget.pnlProxyDecisionConditionAddEdit',
    autoScroll	: true,
    plain       : true,
	itemId		: 'pnlProxyDecisionConditionAddEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    proxyDC_id      : null,
    defaults    : {
            border: false
    },
    fieldDefaults: {
        msgTarget       : 'under',
        labelAlign      : 'left',
        labelSeparator  : '',
        //labelWidth      : Rd.config.labelWidth,
        labelWidth      : 170,
        margin          : Rd.config.fieldMargin,
        labelClsExtra   : 'lblRdReq'
    },
    config : {
        urlView  : '/cake3/rd_cake/proxy-decision-conditions/view.json',
    },
    requires: [
        'Ext.form.field.Text',
    ],
    listeners       : {
        show        : 'loadSettings', //Trigger a load of the settings
        afterrender : 'loadSettings',
        
    },
    loadSettings: function(panel){ 
        var me = this;

        me.load({
            url         :me.getUrlView(), 
            method      :'GET',
            params      :{proxy_decision_condition_id : me.proxyDC_id},
            success     : function(a,b,c){
                // console.log(b.result.data);
            }
        });
        
    },
    initComponent: function(){
    
        var me = this;
        
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
                        xtype       : 'textfield',
                        fieldLabel  : i18n('sSSID_Regex'),
                        name        : "ssid",
                        allowBlank  : false,
                        blankText   	: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                   {
                        xtype       	: 'textfield',
                        fieldLabel  	: i18n('sUser_name_Regex'),
                        name        	: 'user_name_regex',
                        allowBlank  	: true,
                        labelClsExtra	: 'lblRdReq'
                    },
                    {
                        xtype       	: 'cmbProxies',
                        fieldLabel  	: i18n('sForwarding_proxy'),
                        name        	: 'proxy_id',
                        allowBlank  	: false,
                        blankText   	: i18n('sSupply_a_value'),
                        labelClsExtra	: 'lblRdReq',
                    },
                    {
                        xtype       	: 'numberfield',
                        fieldLabel  	: i18n('sPriority'),
                        name        	: 'priority',
                        allowBlank  	: true,
                        value           : 5,
                        labelClsExtra	: 'lblRdReq'
                    },

                ],
            },
           
        ];       
        this.callParent(arguments);
    },
  

});
