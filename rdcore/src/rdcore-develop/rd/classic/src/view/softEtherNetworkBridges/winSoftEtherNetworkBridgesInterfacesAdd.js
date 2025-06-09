Ext.define('Rd.view.softEtherNetworkBridges.winSoftEtherNetworkBridgesInterfacesAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherNetworkBridgesInterfacesAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Interface'),
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

        'Rd.view.softEtherNetworkBridges.cmbEtherNetworkBridgeInterfaces',
    ],
    initComponent: function () {
        var me = this;
        // title Reset in current language
        me.setTitle(i18n('sAdd_Interface'));

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
                    xtype: 'hiddenfield',
                    itemId: 'hub_id',
                    name: "bridge_id",
                    value: me.network_bridge_id,
                },
                {
                    xtype: 'cmbEtherNetworkBridgeInterfaces',
                    fieldLabel: i18n('sInterface'),
                    name: 'if_name',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    valueField:'if_name'
                },
               

            ],
            buttons: buttons
        });
        return frmData;
    }
});
