Ext.define('Rd.view.subGroups.winSubGroupsAddWizard', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSubGroupsAddWizard',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Organization'),
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
    realm_id: '',
    idp_id: '',
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
        me.setTitle(i18n('sAdd_Organization'));

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
                                itemId: 'realm_id',
                                xtype: 'textfield',
                                name: "realm_id",
                                hidden: true,
                                value: me.realm_id,
                            },
                            {
                                itemId: 'idp_id',
                                xtype: 'textfield',
                                name: "idp_id",
                                hidden: true,
                                value: me.idp_id,
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
                                xtype: 'textfield',
                                fieldLabel: i18n('sPath'),
                                name: 'path',
                                allowBlank: false,
                                blankText: i18n("sEnter_a_value"),
                                labelClsExtra: 'lblRdReq'
                            },
                            {
                                fieldLabel: i18n('sProfile'),
                                xtype:'combobox',
                                store: 'sProfiles',
                                valueField      : 'id',
                                displayField    : 'name',
                                typeAhead       : true,
                                name: 'profile_id',
                                allowBlank: true,
                                blankText: i18n('sSupply_a_value'),
                                labelClsExtra: 'lblRdReq',
                                getSubmitValue:function(){
                                    //Do not send when this field is empty 
                                    var value = this.getValue();
                                    if(Ext.isEmpty(value)) {
                                        return null;
                                    }
                                    return value;
                                }
                            },
                            {
                                xtype       	: 'textfield',
                                fieldLabel  	: i18n('sDescription'),
                                name        	: 'description',
                                allowBlank  	: true,
                                labelClsExtra	: 'lblRdReq'
                            },
                        ]
                    }
                ]
            }]
        });
        return frmData;
    }

});
