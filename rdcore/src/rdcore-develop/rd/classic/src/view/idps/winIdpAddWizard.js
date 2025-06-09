Ext.define('Rd.view.idps.winIdpAddWizard', {
    extend: 'Ext.window.Window',
    alias: 'widget.winIdpAddWizard',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Institution'),
    width: 450,
    height: 500,
    plain: true,
    border: false,
    layout: 'card',
    glyph: Rd.config.icnAdd,
    autoShow: false,
    startScreen: 'scrnApTree',
    owner: '',
    user_id: '',
    no_tree: false,
    defaults: {
        border: false
    },
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',

        'Rd.store.sAccessProvidersTree',
        'Rd.model.mAccessProviderTree',
        'Rd.view.components.btnDataPrev',
        'Rd.view.components.btnDataNext',
    ],
    initComponent: function () {
        var me = this;
        me.setTitle(i18n('sAdd_Institution'));

        var scrnApTree = me.mkScrnApTree();
        var scrnData = me.mkScrnData();
        me.items = [
            scrnApTree,
            scrnData
        ];
        me.callParent(arguments);
        me.getLayout().setActiveItem(me.startScreen);
    },

    //____ AccessProviders tree SCREEN ____
    mkScrnApTree: function () {
        var me = this;
        var pnlTree = Ext.create('Rd.view.components.pnlAccessProvidersTree', {
            itemId: 'scrnApTree'
        });
        return pnlTree;
    },

    mkScrnData: function () {
        var me = this;

        var buttons = [{
                xtype: 'btnDataPrev'
            },
            {
                xtype: 'btnDataNext'
            }
        ];

        if (me.no_tree == true) {
            buttons = [{
                xtype: 'btnDataNext'
            }];
        }


        var frmData = Ext.create('Ext.form.Panel', {
            border: false,
            layout: 'fit',
            itemId: 'scrnData',
            autoScroll: true,
            fieldDefaults: {
                msgTarget: 'under',
                labelClsExtra: 'lblRd',
                labelAlign: 'left',
                labelSeparator: '',
                labelClsExtra: 'lblRd',
                labelWidth: Rd.config.labelWidth,
                maxWidth: Rd.config.maxWidth,
                margin: Rd.config.fieldMargin
            },
            defaultType: 'textfield',
            buttons: buttons,
            items: [{
                xtype: 'tabpanel',
                layout: 'fit',
                margins: '0 0 0 0',
                plain: true,
                tabPosition: 'bottom',
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
                        items: [{
                                itemId: 'user_id',
                                xtype: 'textfield',
                                name: "user_id",
                                hidden: true,
                                value: me.user_id
                            },
                            {
                                itemId: 'owner',
                                xtype: 'displayfield',
                                fieldLabel: i18n('sOwner'),
                                value: me.owner,
                                labelClsExtra: 'lblRdReq'
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
                                value: "google_workspace",
                                allowBlank: false,
                                blankText: i18n('sSupply_a_value'),
                                listeners   : {
                                    change: me.onChangeType,
                                },
                            },
                            {
                                fieldLabel: i18n('sAuth_type'),
                                xtype: 'cmbIdpAuthType',
                                name: 'auth_type',
                                //value: "oauth",
                                allowBlank: false,
                                blankText: i18n('sSupply_a_value'),
                                listeners   : {
                                     beforerender: me.onBeforeRenderAuthType,
                                },
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
            }]
        });
        return frmData;
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
