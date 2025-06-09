Ext.define('Rd.view.proxyDecisionConditions.winProxyDecisionConditionAddWizard', {
    extend:     'Ext.window.Window',
    alias :     'widget.winProxyDecisionConditionAddWizard',
    closable:   true,
    draggable:  true,
    resizable:  true,
    title:      i18n ('sNew_Proxy_decision_conditions'),
    width:      500,
    height:     470,
    plain:      true,
    border:     false,
    layout:     'card',
    iconCls:    'add',
    glyph: Rd.config.icnAdd,
    autoShow:   false,
    defaults: {
            border: false
    },
    no_tree: false, //If the user has no children we don't bother giving them a branchless tree
    hsp_id: null,
    grid_id:null,
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',
    ],
    initComponent: function() {
        var me = this;
        // title Reset in current language
        me.setTitle( i18n('sNew_Proxy_decision_conditions') );

        var scrnData        = me.mkScrnData();
        me.items = [
            scrnData
        ];
        this.callParent(arguments);
    },
  
    //_______ Data form _______
    mkScrnData: function(){


        var me      = this;
        var buttons = [       
                {
                    itemId: 'btnDataNext',
                    text: i18n('sNext'),
                    scale: 'large',
                    iconCls: 'b-next',
                    glyph: Rd.config.icnNext,
                    formBind: true,
                    margin: '0 20 40 0'
                }
            ];

        var frmData = Ext.create('Ext.form.Panel',{
            border      : false,
            layout      : 'anchor',
            itemId      : 'scrnData',
            autoScroll  : true,
            defaults    : {
                anchor: '100%'
            },
            fieldDefaults: {
                msgTarget: 'under',
                labelClsExtra: 'lblRd',
                labelAlign: 'left',
                labelSeparator: '',
                labelWidth: 170,
                margin: 15
            },
            defaultType: 'textfield',
            items:[
                
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
            buttons: buttons
        });
        return frmData;
    }   
});
