Ext.define('Rd.view.proxies.winProxieAddWizard', {
    extend:     'Ext.window.Window',
    alias :     'widget.winProxieAddWizard',
    closable:   true,
    draggable:  true,
    resizable:  true,
    title:      i18n ('sNew_Proxie'),
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
    user_id: '',
    owner: '',
    startScreen: 'scrnApTree', //Default start screen
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',
        'Rd.view.components.cmbHomeServerPools',
    ],
    initComponent: function() {
        var me = this;
        // title Reset in current language
        me.setTitle( i18n('sNew_Proxie') );

        var scrnApTree      = me.mkScrnApTree();
        var scrnData        = me.mkScrnData();
        me.items = [
            scrnApTree,
            scrnData
        ];
        this.callParent(arguments);
        me.getLayout().setActiveItem(me.startScreen);
    },

    //____ AccessProviders tree SCREEN ____
    mkScrnApTree: function(){
        var pnlTree = Ext.create('Rd.view.components.pnlAccessProvidersTree',{
            itemId: 'scrnApTree'
        });
        return pnlTree;
    },

    //_______ Data for ssids  _______
    mkScrnData: function(){


        var me      = this;
        var buttons = [
                {
                    itemId: 'btnDataPrev',
                    text: i18n('sPrev'),
                    scale: 'large',
                    iconCls: 'b-prev',
                    glyph: Rd.config.icnBack,
                    margin: '0 20 40 0'
                },
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

        if(me.no_tree == true){
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
        }

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
                    itemId  : 'user_id',
                    xtype   : 'textfield',
                    name    : "user_id",
                    hidden  : true,
                    value   : me.user_id
                },
                {
                    itemId      : 'owner',
                    xtype       : 'displayfield',
                    fieldLabel  : i18n('sOwner'),
                    value       : me.owner,
                    labelClsExtra: 'lblRdReq'
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
                    xtype       	: 'cmbHomeServerPools',
                    fieldLabel  	: i18n('sHome_server_pool'),
                    name        	: 'home_server_pool_id',
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
                }
            ],
            buttons: buttons
        });
        return frmData;
    }   
});
