Ext.define('Rd.view.softEtherNetworkBridges.winSoftEtherNetworkBridgeAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherNetworkBridgeAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Network_bridge'),
    width: 500,
    height: 470,
    plain: true,
    border: false,
    layout: 'card',
    iconCls: 'add',
    glyph: Rd.config.icnAdd,
    autoShow: false,
    defaults: {
        border: false
    },
    no_tree: false, //If the user has no children we don't bother giving them a branchless tree
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',

        'Rd.view.softEtherLocalBridges.cmbLbVirtualHub',
        'Rd.view.softEtherLocalBridges.cmbDevice'
    ],
    initComponent: function () {
        var me = this;
        // title Reset in current language
        me.setTitle(i18n('sAdd_Network_bridge'));

        var scrnData = me.mkScrnData();
        me.items = [
            scrnData
        ];
        this.callParent(arguments);
    },

    //_______ Data form _______
    mkScrnData: function () {

        var me = this;
        var buttons = [{
            itemId: 'btnDataNext',
            text: i18n('sNext'),
            scale: 'large',
            iconCls: 'b-next',
            glyph: Rd.config.icnNext,
            formBind: true,
            margin: '0 20 40 0'
        }];


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
                labelWidth: Rd.config.labelWidth,
                margin: Rd.config.fieldMargin,
            },
            defaultType: 'textfield',
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sName'),
                    name: "bridge_name",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'radiogroup',
                    //  fieldLabel  : i18n('sStatus'),
                    //   labelWidth  : 50,
                    columns: 2,
                    vertical: false,
                    items: [{
                            boxLabel: '<span class="lblRdReq">'+i18n('sRunning')+'<span>',
                            name: 'status',
                            inputValue: 1,
                            checked: true
                        },
                        {
                            boxLabel: '<span class="lblRdReq">'+i18n('sStop')+'<span>',
                            name: 'status',
                            inputValue: 0
                        },
                    ]
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sIP_Address'),
                    name: "ip_address",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sSubnet_mask'),
                    name: "subnet_mask",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },

            ],
            buttons: buttons
        });
        return frmData;
    }
});
