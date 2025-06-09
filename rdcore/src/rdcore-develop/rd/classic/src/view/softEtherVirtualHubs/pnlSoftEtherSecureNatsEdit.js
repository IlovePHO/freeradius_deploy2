Ext.define('Rd.view.softEtherVirtualHubs.pnlSoftEtherSecureNatsEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherSecureNatsEdit',
    autoScroll: true,
    plain: true,
    frame: false,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    margin: 5,
    vHub_id: null,
    fieldDefaults: {
        msgTarget: 'under',
        labelAlign: 'left',
        labelSeparator: '',
        labelWidth: Rd.config.labelWidth,
        margin: Rd.config.fieldMargin,
        labelClsExtra: 'lblRdReq'
    },
    requires: [
        'Ext.form.field.Text',
    ],
    config: {
        urlView: '/cake3/rd_cake/soft-ether-secure-nats/view.json'
    },
    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        afterrender: 'loadSettings',
    },
    loadSettings: function (panel) {
        var me = this;
        me.togleEnabled(false, 'container field:not(#enabled)');
        me.load({
            url: me.getUrlView(),
            method: 'GET',
            params: {
                virtual_hub_id: me.vHub_id
            },
            success: function (a, b, c) {
                //console.log(b.result.data);
                var data = b.result.data;
                var enabled = data.enabled ? data.enabled : false;
                me.togleEnabled(enabled, 'field:not(#enabled)');
                me.togleEnabled((enabled && data.dhcp_enabled), '#cntVirtualDHCP field:not(#dhcp_enabled)');
            }
        });
    },
    initComponent: function () {
        var me = this;
        var w_prim = 550;

        me.buttons = [{
            itemId: 'save',
            text: 'SAVE',
            scale: 'large',
            formBind: true,
            glyph: Rd.config.icnYes,
            margin: Rd.config.buttonMargin,
            ui: 'button-teal'
        }];


        // SecureNAT設定
        var cntSecureNAT = {
            itemId: 'cntSecureNAT',
            xtype: 'container',
            width: w_prim,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            items: [
                {
                    xtype: 'checkbox',
                    fieldLabel: i18n('sEnabled'),
                    name: 'enabled',
                    itemId: 'enabled',
                    checked: false,
                    inputValue: true,
                    uncheckedValue: false,
                    labelClsExtra: 'lblRdReq',
                    listeners: {
                        change: function (ck, newValue, oldvalue) {
                            var p = ck.up('form');
                            me.togleEnabled(newValue, 'field:not(#enabled)');
                            enabled2 = ck.up('form').query('#dhcp_enabled')[0].getValue();
                            me.togleEnabled((newValue && enabled2), '#cntVirtualDHCP field:not(#dhcp_enabled)');
                        }
                    },
                },
            ]
        };

        // 仮想ホスト設定 
        var cntVirtualHost = {
            itemId: 'cntVirtualHost',
            xtype: 'container',
            width: w_prim,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            items: [{
                    xtype: 'textfield',
                    fieldLabel: i18n('sIP_Address'),
                    name: "ip_address",
                    itemId: "ip_address",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sSubnet_mask'),
                    name: "subnet_mask",
                    itemId: "subnet_mask",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sMAC_address'),
                    name: "mac_address",
                    labelClsExtra: 'lblRdReq'
                },
            ]
        };
        // 仮想DHCPサーバー設定 
        var cntVirtualDHCP = {
            itemId: 'cntVirtualDHCP',
            xtype: 'container',
            width: w_prim,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            items: [{
                    xtype: 'checkbox',
                    fieldLabel: i18n('sEnabled'),
                    name: 'dhcp_enabled',
                    itemId: 'dhcp_enabled',
                    checked: false,
                    inputValue: true,
                    uncheckedValue: false,
                    labelClsExtra: 'lblRdReq',
                    listeners: {
                        change: function (ck, newValue, oldvalue) {
                            var enabled = ck.up('form').query('#enabled')[0].getValue();
                            me.togleEnabled((newValue && enabled), '#cntVirtualDHCP field:not(#dhcp_enabled)');
                        }
                    },
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sLease_ip_start'),
                    name: 'dhcp_lease_ip_start',
                    itemId: 'dhcp_lease_ip_start',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sLease_ip_end'),
                    name: 'dhcp_lease_ip_end',
                    itemId: 'dhcp_lease_ip_end',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sLease_ip_Subnet_mask'),
                    name: 'dhcp_subnet_mask',
                    itemId: 'dhcp_subnet_mask',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },

                {
                    xtype: 'numberfield',
                    fieldLabel: i18n('sExpire'),
                    name: 'dhcp_expire',
                    itemId: 'dhcp_expire',
                    value: 7200,
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sDefault_gateway_address'),
                    name: 'dhcp_gateway_address',
                    itemId: 'dhcp_gateway_address',
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sDns_server_address1'),
                    name: 'dhcp_dns_server_address1',
                    itemId: 'dhcp_dns_server_address1',
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sDns_server_address2'),
                    name: 'dhcp_dns_server_address2',
                    itemId: 'dhcp_dns_server_address2',
                    vtype: 'IPAddress',
                    labelClsExtra: 'lblRdReq'
                },
            ]
        };
        // 仮想NAT設定 
        var cntVirtualNat = {
            itemId: 'cntVirtualNat',
            xtype: 'container',
            width: w_prim,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            items: [{
                xtype: 'checkbox',
                fieldLabel: i18n('sEnabled'),
                name: 'nat_enabled',
                itemId: 'nat_enabled',
                checked: false,
                inputValue: true,
                uncheckedValue: false,
                labelClsExtra: 'lblRdReq'
            }, ]
        };


        me.items = [
            {
                xtype: 'hiddenfield',
                itemId: 'hub_id',
                name: "hub_id",
                value: me.vHub_id,
            },
            {
                xtype: 'panel',
                autoScroll: true,
                width: '100%',
                layout: {
                    type: 'vbox',
                    pack: 'start',
                    align: 'start'
                },
                items: [{
                        xtype: 'panel',
                        title: i18n('sSecureNAT_settings'),
                        glyph: Rd.config.icnRealm,
                        ui: 'panel-blue',
                        width: '100%',
                        bodyPadding: 10,
                        items: cntSecureNAT
                    },
                    {
                        xtype: 'panel',
                        title: i18n('sVirtual_Host_settings'),
                        glyph: Rd.config.icnRealm,
                        ui: 'panel-blue',
                        width: '100%',
                        bodyPadding: 10,
                        items: cntVirtualHost
                    },
                    {
                        xtype: 'panel',
                        title: i18n('sVirtual_DHCP_Server_settings'),
                        glyph: Rd.config.icnRealm,
                        ui: 'panel-blue',
                        width: '100%',
                        bodyPadding: 10,
                        items: cntVirtualDHCP
                    },
                    {
                        xtype: 'panel',
                        title: i18n('sVirtual_NAT_settings'),
                        glyph: Rd.config.icnRealm,
                        ui: 'panel-blue',
                        width: '100%',
                        bodyPadding: 10,
                        items: cntVirtualNat
                    },
            ]
        }];

        this.callParent(arguments);
    },
    togleEnabled: function (flg, selcter) {
        var me = this,
            fields = me.query(selcter + ':not(hiddenfield)');
        for (var i = 0; i < fields.length; i++) {
            fields[i].setDisabled(!flg);
        }
    }

});
