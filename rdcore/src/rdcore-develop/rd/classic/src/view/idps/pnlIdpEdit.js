Ext.define('Rd.view.idps.pnlIdpEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlIdpEdit',
    autoScroll: true,
    plain: true,
    itemId: 'pnlIdpEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },

    idp_id: null,
    realm_name:null,

    fieldDefaults: {
        msgTarget: 'under',
        labelClsExtra: 'lblRd',
        labelAlign: 'left',
        labelSeparator: '',
        labelClsExtra: 'lblRd',
        labelWidth: Rd.config.labelWidth,
        margin: Rd.config.fieldMargin
    },
    defaultType: 'textfield',

    config: {
        urlView: '/cake3/rd_cake/idps/view.json'
    },
    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        afterrender: 'loadSettings',
    },
    loadSettings: function (panel) {
        var me = this;

         me.load({
             url         :me.getUrlView(),
             method      :'GET',
             params      :{idp_id : me.idp_id},
             success     : function(a,b,c){
                 //console.log(b.result.data);
                 var data = b.result.data;
                 if(data.realm && data.realm.name ){
                     me.realm_name = data.realm.name;
                     me.down('#realmName').setValue(me.realm_name);
                 }
             }
         });

    },
    initComponent: function () {
        var me = this;

        me.buttons = [{
            itemId: 'save',
            text: 'SAVE',
            scale: 'large',
            formBind: true,
            glyph: Rd.config.icnYes,
            margin: Rd.config.buttonMargin,
            ui: 'button-teal'
        }];

        me.items = [{
            xtype: 'tabpanel',
            layout: 'fit',
            margins: '0 0 0 0',
            plain: true,
            tabPosition: 'top',
            border: false,
            cls: 'subTab',
            items: [
                {
                    'title': i18n('sBasic_info'),
                    'layout': 'anchor',
                    itemId: 'tabRequired',
                    defaults: {
                        anchor: '100%'
                    },
                    autoScroll: true,
                    items: [
                        {
                            itemId: 'id',
                            xtype: 'textfield',
                            name: "id",
                            hidden: true
                        },
                        {
                            xtype: 'fieldcontainer',
                            itemId: 'fcPickOwner',
                            layout: {
                                type: 'hbox',
                                align: 'begin',
                                pack: 'start'
                            },
                            items: [{
                                    itemId: 'owner',
                                    xtype: 'displayfield',
                                    fieldLabel: i18n('sOwner'),
                                    name: 'username',
                                    itemId: 'displUser',
                                    margin: 0,
                                    padding: 0,
                                    width: 360,
                                },
                                {
                                    xtype: 'button',
                                    text: 'Pick Owner',
                                    margin: 5,
                                    padding: 5,
                                    ui: 'button-green',
                                    itemId: 'btnPickOwner',
                                    width: 100,
                                    listeners: {
                                        click: me.onBtnPickOwnerClick
                                    }
                                },
                                {
                                    xtype: 'textfield',
                                    name: "user_id",
                                    itemId: 'hiddenUser',
                                    hidden: true
                                }
                            ]
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: i18n('sName'),
                            name: "name",
                            allowBlank: false,
                            blankText: i18n("sEnter_a_value"),
                            labelClsExtra: 'lblRdReq'
                        },

                        {
                            fieldLabel: i18n('sType'),
                            xtype: 'cmbIdpType',
                            name: 'type',
                            allowBlank: false,
                            blankText: i18n('sSupply_a_value'),
                            labelClsExtra: 'lblRdReq',
                            listeners   : {
                                change: me.onChangeType,
                            },
                        },
                        {
                            fieldLabel: i18n('sAuth_type'),
                            xtype: 'cmbIdpAuthType',
                            name: 'auth_type',
                            allowBlank: false,
                            blankText: i18n('sSupply_a_value'),
                            labelClsExtra: 'lblRdReq',
                            listeners   : {
                                beforerender: me.onBeforeRenderAuthType,
                            },
                        },
                        {
                            // realm名の表示のみ、 nameで割り当てると送信対象になるのでIDでload success後に再セット
                            // Only the realm name is displayed. If you assign it with name, it will be send, so reset it after load success with ID
                            fieldLabel: i18n('sRealm'),
                            xtype: 'displayfield',
                            labelClsExtra: 'lblRdReq',
                            value: me.realm_name,
                            itemId: 'realmName',
                            
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: i18n('sDomain'),
                            name: "domain",
                            allowBlank: false,
                            blankText: i18n("sSupply_a_value"),
                            labelClsExtra: 'lblRdReq'
                        },
                        {
                            xtype: 'checkbox',
                            //fieldLabel  : i18n('sMake_available_to_sub_providers'),
                            boxLabel: i18n('sMake_available_to_sub_providers'),
                            name: 'available_to_siblings',
                            inputValue: 'available_to_siblings',
                            checked: false,
                            labelClsExtra: 'lblRdReq'
                        }
                    ]
                },
                {
                    'title': i18n('sOAuth_setting'),
                    'layout': 'anchor',
                    itemId: 'tabContact',
                    defaults: {
                        anchor: '100%'
                    },
                    autoScroll: true,
                    items: [{
                            xtype: 'textareafield',
                            fieldLabel: i18n('sCredentials_(JSON_format)'),
                            name: "credential",
                            maxRows: 4,
                            allowBlank: true,
                        },

                    ]
                },
            ]
        }];

        me.callParent(arguments);
    },

    onBtnPickOwnerClick: function(button){
        var me 		        = this;
        var pnl             = button.up('panel');
        var updateDisplay  = pnl.down('#displUser');
        var updateValue    = pnl.down('#hiddenUser'); 
        
        console.log("Clicked Change Owner");
        if(!Ext.WindowManager.get('winSelectOwnerId')){
            var w = Ext.widget('winSelectOwner',{id:'winSelectOwnerId',updateDisplay:updateDisplay,updateValue:updateValue});
            w.show();
        }
    },

    onChangeType: function(cmb){
        var me 		        = this;
        var form            = cmb.up('form');
        var cmbIdpAuthType  = form.down('cmbIdpAuthType');

        //console.log("call onChangeType");

        if (cmbIdpAuthType.type != cmb.getValue()) {
            cmbIdpAuthType.type = cmb.getValue();
            cmbIdpAuthType.initComponent();
        }
    },

    onBeforeRenderAuthType: function(cmb){
        var me 		        = this;
        var form            = cmb.up('form');
        var cmbIdpType      = form.down('cmbIdpType');

        //console.log("call onBeforeRenderAuthType");

        if (cmb.type != cmbIdpType.getValue()) {
            cmb.type = cmbIdpType.getValue();
            cmb.initComponent();
        }
    }

});
