Ext.define('Rd.view.homeServerPools.winHomeServerAddWizard', {
    extend:     'Ext.window.Window',
    alias :     'widget.winHomeServerAddWizard',
    closable:   true,
    draggable:  true,
    resizable:  true,
    title:      i18n ('sNew_Home_server'),
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
        me.setTitle( i18n('sNew_Home_server') );

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
                margin: 15
            },
            defaultType: 'textfield',
            items:[
                {
                    itemId  : 'hsp_id',
                    xtype   : 'textfield',
                    name    : "home_server_pool_id",
                    hidden  : true,
                    value   : me.hsp_id
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
            buttons: buttons
        });
        return frmData;
    }   
});
