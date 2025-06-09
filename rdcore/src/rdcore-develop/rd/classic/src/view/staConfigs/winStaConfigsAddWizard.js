Ext.define('Rd.view.staConfigs.winStaConfigAddWizard', {
    extend: 'Ext.window.Window',
    alias: 'widget.winStaConfigAddWizard',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_STA_config'),
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

        'Rd.view.staConfigs.cmbEapMethods',
        'Rd.view.staConfigs.cmbEncodingSchemes'
    ],
    initComponent: function () {
        var me = this;
        me.setTitle(i18n('sAdd_STA_config'));

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

        //Set default values for from and to:
        var dtTo = new Date();
        dtTo.setYear(dtTo.getFullYear() + 1);

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
                    xtype: 'textfield',
                    fieldLabel: i18n('sSSID'),
                    name: "ssid",
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sHome_domain'),
                    name: "home_domain",
                    allowBlank: false,
                    blankText: i18n("sEnter_a_value"),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sFriendly_name'),
                    name: "friendly_name",
                    allowBlank: false,
                    blankText: i18n("sEnter_a_value"),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sRCOI'),
                    name: "rcoi",
                    labelClsExtra: 'lblRdReq'
                },
                {
                    fieldLabel: i18n('sAuthentication_method'),
                    xtype: 'cmbEapMethods',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    itemId: 'eap_method',
                    name: 'eap_method',
                },
             
                {
                    fieldLabel: i18n('sHMAC_Key'),
                    xtype: 'cmbEncodingSchemes',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    itemId: 'encoding_scheme',
                    name: 'encoding_scheme_id',
                },
                {
                    xtype: 'datefield',
                    fieldLabel: i18n('sExpire'),
                    name: 'expire',
                    itemId: 'expire',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    format: 'Y/m/d',
                    minValue: new Date(), // limited to the current date or after
                    value: dtTo
                },
                {
                    xtype: 'checkbox',
                    boxLabel: i18n('sMake_available_to_sub_providers'),
                    // fieldLabel    : i18n('sMake_available_to_sub_providers'),
                    name: 'available_to_siblings',
                    inputValue: 'available_to_siblings',
                    itemId: 'a_to_s',
                    checked: false,
                    cls: 'lblRd'
                },
            ]
        });
        return frmData;
    }
});
