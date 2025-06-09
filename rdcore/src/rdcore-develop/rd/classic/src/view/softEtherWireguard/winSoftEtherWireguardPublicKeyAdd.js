Ext.define('Rd.view.softEtherWireguard.winSoftEtherWireguardPublicKeyAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherWireguardPublicKeyAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Client_Public_key'),
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

        'Rd.view.softEtherWireguard.cmbWireguardVirtualHub',
        'Rd.view.softEtherWireguard.cmbWireguardUsers'
    ],
    initComponent: function () {
        var me = this;
        // title Reset in current language
        me.setTitle(i18n('sAdd_Client_Public_key'));

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
                    fieldLabel: i18n('sPublic_key'),
                    name: "public_key",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq',
                },
                {
                    xtype: 'cmbWireguardVirtualHub',
                    fieldLabel: i18n('sVirtual_Hub'),
                    name: 'hub_name',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    valueField:'hub_name',
                    listeners:{
                      change : me.onCmbVirtualHubChange
                    }
                },
                {
                    xtype: 'cmbWireguardUsers',
                    fieldLabel: i18n('sVPN_users'),
                    name: 'user_name',
                    allowBlank: false,
                    blankText: i18n('sSupply_a_value'),
                    labelClsExtra: 'lblRdReq',
                    valueField:'user_name'
                },

            ],
            buttons: buttons
        });
        return frmData;
    },
    onCmbVirtualHubChange:function(cmb, newValue, oldValue){
        var value = cmb.getValue();
        var form = cmb.up('form');
        var cmbUsers = form.down('cmbWireguardUsers');
        var hub_id = newValue ? cmb.getSelection().id : 0;

        if( oldValue ){
            cmbUsers.clearValue()
        }
        cmbUsers.getStore().getProxy().setExtraParam('virtual_hub_id', hub_id);
        cmbUsers.getStore().load();
    },
});
