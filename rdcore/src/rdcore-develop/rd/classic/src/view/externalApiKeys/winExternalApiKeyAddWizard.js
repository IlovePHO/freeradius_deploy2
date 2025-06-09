Ext.define('Rd.view.externalApiKeys.winExternalApiKeyAddWizard', {
    extend: 'Ext.window.Window',
    alias: 'widget.winExternalApiKeyAddWizard',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_API_Key'),
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
        'Rd.view.components.btnDataNext'
    ],
    initComponent: function () {
        var me = this;
        me.setTitle(i18n('sAdd_API_Key'));

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
            layout: 'anchor',
            itemId: 'scrnData',
            autoScroll: true,
            defaults: {
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
            buttons: buttons,
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
            ]
        });
        return frmData;
    }
});
