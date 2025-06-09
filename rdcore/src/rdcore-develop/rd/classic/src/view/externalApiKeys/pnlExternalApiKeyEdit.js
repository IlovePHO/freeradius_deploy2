Ext.define('Rd.view.externalApiKeys.pnlExternalApiKeyEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlExternalApiKeyEdit',
    autoScroll: true,
    plain: true,
    itemId: 'pnlExternalApiKeyEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    eApiKey_id: null,
    //record      : null, //We will supply each instance with a reference to the selected record.
    defaults: {
        border: false
    },
    fieldDefaults: {
        msgTarget: 'under',
        labelAlign: 'left',
        labelSeparator: '',
        labelWidth: Rd.config.labelWidth,
        margin: Rd.config.fieldMargin,
        labelClsExtra: 'lblRdReq'
    },
    config: {
        urlView: '/cake3/rd_cake/external-api-keys/view.json',
    },
    requires: [
        'Ext.form.field.Text',
    ],
    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        afterrender: 'loadSettings',

    },
    loadSettings: function (panel) {
        var me = this;

        me.load({
            url: me.getUrlView(),
            method: 'GET',
            params: {
                external_api_key_id: me.eApiKey_id
            },
            success: function (a, b, c) {
                // console.log(b.result.data);
                var data = b.result.data;
                
                if(data.realm && data.realm.name ){
                    var realm = me.down("#realm");
                    realm.getStore().getProxy().setExtraParam('ap_id', data.user_id);
                    var mr    = Ext.create('Rd.model.mRealm', {name: data.realm.name, id: data.realm_id});
                    realm.getStore().loadData([mr],false);
                    realm.setValue(data.realm_id);
                }

                if(data.profile && data.profile.name ){
                    var profile = me.down("#profile");
                    profile.getStore().getProxy().setExtraParam('ap_id', data.user_id);
                    var mp      = Ext.create('Rd.model.mProfile', {name: data.profile.name, id: data.profile_id});
                    profile.getStore().loadData([mp],false);
                    profile.setValue(data.profile_id);
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
                xtype: 'panel',
                //bodyStyle   : 'background: #f0f0f5',
                bodyPadding: 10,
                items: [{
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
                                itemId: 'user_id',
                                hidden: true
                            }
                        ]
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sName'),
                        name: "name",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                   
                    {
                        fieldLabel: i18n('sRealm'),
                        xtype: 'cmbRealm',
                        extraParam: me.user_id,
                        allowBlank: false,
                        blankText: i18n('sSupply_a_value'),
                        labelClsExtra: 'lblRdReq',
                        itemId: 'realm',
                        name: 'realm_id',
                    },
                    {
                        fieldLabel: i18n('sProfile'),
                        xtype: 'cmbProfile',
                        extraParam: me.user_id,
                        allowBlank: false,
                        blankText: i18n('sSupply_a_value'),
                        labelClsExtra: 'lblRdReq',
                        itemId: 'profile',
                        name: 'profile_id',
                    },

                ],
            },

        ];
        this.callParent(arguments);
    },
    onBtnPickOwnerClick: function (button) {
        var me = this;
        var pnl = button.up('panel');
        var updateDisplay = pnl.down('#displUser');
        var updateValue = pnl.down('#user_id');

        console.log("Clicked Change Owner");
        if (!Ext.WindowManager.get('winSelectOwnerId')) {
            var w = Ext.widget('winSelectOwner', {
                id: 'winSelectOwnerId',
                updateDisplay: updateDisplay,
                updateValue: updateValue
            });
            w.show();
        }
    },

});
