Ext.define('Rd.view.softEtherLocalBridges.winSoftEtherLocalBridgeAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherLocalBridgeAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Local_bridge'),
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
        me.setTitle(i18n('sAdd_Local_bridge'));

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
                    xtype: 'cmbLbVirtualHub',
                    fieldLabel: i18n('sVirtual_Hub'),
                    name: 'hub_name',
                    itemId:'hub_name',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    valueField:'hub_name'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: i18n('sTap_mode'),
                    name: 'tap_mode',
                    itemId:'tap_mode',
                    inputValue: 1,
                    uncheckedValue: 0,
                    cls: 'lblRd',
                    listeners: {
                        change: function (ck, newValue, oldvalue) {
                            var form = ck.up('form');
                            var device_name = form.down('#device_name');
                            device_name.setDisabled(newValue);
                            var tap_device_name = form.down('#tap_device_name');
                            tap_device_name.setDisabled(!newValue);
                        }
                    }
                },
                {
                    xtype: 'cmbDevice',
                    fieldLabel: i18n('sDevice'),
                    name: 'device_name',
                    itemId: 'device_name',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    valueField:'if_name'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sTap_device'),
                    name: "tap_device_name",
                    itemId:'tap_device_name',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq',
                    disabled: true,
                },

            ],
            buttons: buttons
        });
        return frmData;
    }
});
